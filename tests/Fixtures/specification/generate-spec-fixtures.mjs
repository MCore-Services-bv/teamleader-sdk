#!/usr/bin/env node
//
// Regenerates the specification fixtures used by the spec-contract suite.
//
//   npm install
//   npm run spec:fixtures
//
// Writes, into the directory this script lives in:
//
//   contract.json   Every endpoint in the specification — the input for
//                   SpecAuditor, SpecParityTest and bin/spec-audit.
//   <resource>.json One file per entry in RESOURCES below, for the hand-written
//                   *SpecContractTest classes that assert a single resource in
//                   more depth (orders.json for OrdersSpecContractTest).
//
// and, at the repository root:
//
//   API-list-endpoint-contract.md  Human-readable table of every `.list`
//                   endpoint's filters, sort fields and includes.
//
// The output is a mechanical extract of the specification — no judgement is
// applied here, so a fixture that disagrees with the SDK is a finding about the
// SDK, not about this script.
//
// The specification version is pinned in package.json. Bumping it is a
// deliberate commit: regenerate, run `php bin/spec-audit`, review the diff.

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const yaml = require('js-yaml');
const here = path.dirname(fileURLToPath(import.meta.url));

// Per-resource fixtures for the dedicated contract tests. contract.json covers
// every endpoint regardless of what is listed here.
const RESOURCES = {
    orders: ['orders.list', 'orders.info'],
};

// --- specification loading ---------------------------------------------------

const pkgPath = require.resolve('@teamleader/focus-api-specification/package.json');
const pkg = JSON.parse(fs.readFileSync(pkgPath, 'utf8'));
const distDir = path.join(path.dirname(pkgPath), 'dist');
const spec = yaml.load(
    fs.readFileSync(path.join(distDir, 'api.focus.teamleader.eu.dereferenced.yaml'), 'utf8')
);

// --- schema helpers ----------------------------------------------------------

// The dereferenced spec still uses allOf, both to attach an `example` block and
// to mark a $ref'd object nullable. Merge the branches into one schema.
//
// With `deep`, each allOf branch is itself resolved through branches(), so a
// oneOf nested inside an allOf (expected_payment_method: allOf[oneOf[...]])
// is merged too. Request-side extraction uses that; the response field map
// does not, so polymorphic response fields stay visibly `unknown`.
function flatten(schema, deep = false) {
    if (!schema || typeof schema !== 'object') {
        return {};
    }

    if (!Array.isArray(schema.allOf)) {
        return schema;
    }

    const merged = { type: undefined, properties: {} };
    const required = new Set();

    for (const branch of schema.allOf) {
        const resolved = deep ? branches(branch) : flatten(branch);
        merged.type = resolved.type ?? merged.type;
        merged.items = resolved.items ?? merged.items;
        merged.enum = resolved.enum ?? merged.enum;
        merged.example = resolved.example ?? merged.example;
        merged.description = resolved.description ?? merged.description;
        Object.assign(merged.properties, resolved.properties ?? {});
        (resolved.required ?? []).forEach((name) => required.add(name));
        if (resolved.nullable) {
            merged.nullable = true;
        }
    }

    if (required.size > 0) {
        merged.required = [...required];
    }

    if (Object.keys(merged.properties).length === 0) {
        delete merged.properties;
    }

    return merged;
}

// oneOf / anyOf branches (used for polymorphic filters such as `customer`)
// are merged property-wise so every key any branch accepts is visible.
function branches(schema) {
    const node = flatten(schema, true);
    const alternatives = node.oneOf ?? node.anyOf;

    if (!Array.isArray(alternatives)) {
        return node;
    }

    const merged = { ...node, properties: { ...(node.properties ?? {}) } };
    const enums = new Set(node.enum ?? []);

    for (const alternative of alternatives) {
        const resolved = branches(alternative);
        merged.type = merged.type ?? resolved.type;

        if (resolved.items) {
            merged.items = merged.items ? { anyOf: [merged.items, resolved.items] } : resolved.items;
        }

        // A property several alternatives declare (billing_cycle.periodicity:
        // `unit` is week in one branch, month in another, year in the third)
        // is kept as an anyOf of every version, so its enum is the union
        // rather than whichever branch happened to come last.
        for (const [name, child] of Object.entries(resolved.properties ?? {})) {
            merged.properties[name] = name in merged.properties
                ? { anyOf: [merged.properties[name], child] }
                : child;
        }
        (resolved.enum ?? []).forEach((value) => enums.add(value));
        if (resolved.nullable) {
            merged.nullable = true;
        }
    }

    if (enums.size > 0) {
        merged.enum = [...enums];
    }

    if (Object.keys(merged.properties).length === 0) {
        delete merged.properties;
    }

    return merged;
}

// Walk the `data` container into dotted paths matching the SDK's
// getResponseStructure() convention: `data[].name` for a list endpoint,
// `data.name` for an info endpoint. The container itself is not emitted — the
// SDK describes it in prose ("Array of order objects") rather than as a field.
//
// Polymorphic (oneOf/anyOf) response nodes are deliberately not merged: they
// are reported as `unknown` so a field whose shape varies is visibly flagged
// rather than documented as one of its alternatives.
function fieldPaths(container, out = {}) {
    const node = flatten(container);
    const isList = node.type === 'array';
    const record = isList ? flatten(node.items ?? {}) : node;
    const prefix = isList ? 'data[]' : 'data';

    for (const [name, child] of Object.entries(record.properties ?? {})) {
        walk(child, `${prefix}.${name}`, out);
    }

    return out;
}

function walk(schema, prefix, out, depth = 0) {
    const node = flatten(schema);

    // Guard against pathological recursion in self-referencing schemas.
    if (depth > 8) {
        return out;
    }

    out[prefix] = describe(node);

    if (node.type === 'array') {
        const items = flatten(node.items ?? {});
        for (const [name, child] of Object.entries(items.properties ?? {})) {
            walk(child, `${prefix}[].${name}`, out, depth + 1);
        }

        return out;
    }

    for (const [name, child] of Object.entries(node.properties ?? {})) {
        walk(child, `${prefix}.${name}`, out, depth + 1);
    }

    return out;
}

function describe(node) {
    const parts = [node.type ?? 'unknown'];
    if (node.nullable) {
        parts.push('nullable');
    }
    if (node.enum) {
        parts.push(`enum: ${node.enum.join('|')}`);
    }

    return parts.join(', ');
}

// Every enum anywhere in a request body, keyed by dotted path. Arrays use `[]`
// so `items[].type` reads the same way as in the response field map.
function requestEnums(schema, prefix = '', out = {}, depth = 0) {
    const node = branches(schema);

    if (depth > 8) {
        return out;
    }

    if (node.enum && prefix !== '') {
        out[prefix] = node.enum.slice();
    }

    if (node.type === 'array') {
        const items = branches(node.items ?? {});
        if (items.enum) {
            out[`${prefix}[]`] = items.enum.slice();
        }
        for (const [name, child] of Object.entries(items.properties ?? {})) {
            requestEnums(child, `${prefix}[].${name}`, out, depth + 1);
        }

        return out;
    }

    for (const [name, child] of Object.entries(node.properties ?? {})) {
        requestEnums(child, prefix === '' ? name : `${prefix}.${name}`, out, depth + 1);
    }

    return out;
}

// `includes` is typed as a free-form comma-separated string, so the accepted
// values are not enumerated. Derive them from the two places the specification
// names them: the example, and backticked tokens in the description.
function declaredIncludes(schema) {
    const node = flatten(schema);
    const values = new Set();

    if (typeof node.example === 'string') {
        node.example.split(',').map((v) => v.trim()).filter(Boolean).forEach((v) => values.add(v));
    }

    for (const match of (node.description ?? '').matchAll(/`([a-z0-9_.]+)`/g)) {
        values.add(match[1]);
    }

    return [...values].sort();
}

// Response field descriptions name includes too — "Only included with
// request parameter `includes=custom_fields`". On a few endpoints this is the
// only place an include is documented (quotations.list/info name `expiry`
// while declaring no includes request property at all), so both sources are
// extracted and kept apart: the auditor treats their union as the vocabulary.
function responseIncludes(operation) {
    const found = new Set();

    const visit = (node) => {
        if (Array.isArray(node)) {
            node.forEach(visit);
        } else if (node && typeof node === 'object') {
            for (const [key, value] of Object.entries(node)) {
                if (key === 'description' && typeof value === 'string') {
                    for (const match of value.matchAll(/includes=([a-z0-9_.]+)/g)) {
                        found.add(match[1]);
                    }
                } else {
                    visit(value);
                }
            }
        }
    };

    visit(operation.responses ?? {});

    return [...found].sort();
}

// The sort field enum. A few endpoints (dealSources.list) declare no enum but
// a `default`, which is then the one documented field.
function sortFields(field) {
    if (Array.isArray(field.enum)) {
        return field.enum.slice().sort();
    }

    return typeof field.default === 'string' ? [field.default] : [];
}

function describeFilter(schema) {
    const node = branches(schema);
    const out = { type: node.type ?? 'unknown' };

    if (node.nullable) {
        out.nullable = true;
    }
    if (node.enum) {
        out.enum = node.enum.slice();
    }
    if (node.type === 'array') {
        const items = branches(node.items ?? {});
        out.items = items.type ?? 'unknown';
        if (items.enum) {
            out.items_enum = items.enum.slice();
        }
        if (items.nullable) {
            out.items_nullable = true;
        }
    }
    if (node.properties) {
        out.properties = Object.keys(node.properties).sort();
    }

    return out;
}

function mediaSchema(content, deep = false) {
    const media = content?.['application/json'] ?? Object.values(content ?? {})[0] ?? {};

    return deep ? branches(media.schema ?? {}) : flatten(media.schema ?? {});
}

// --- extraction --------------------------------------------------------------

function extract(endpoint) {
    const operation = spec.paths[`/${endpoint}`]?.post;

    if (!operation) {
        throw new Error(`No POST /${endpoint} in specification ${pkg.version}`);
    }

    // Requests are resolved deep: a oneOf beside the top-level properties
    // (milestones.create's "With budget" / "With price", timeTracking.add's
    // duration / end-time variants) contributes its keys to the body.
    const request = mediaSchema(operation.requestBody?.content, true);
    const requestProperties = request.properties ?? {};

    const filter = branches(requestProperties.filter ?? {});
    const sort = flatten(requestProperties.sort ?? {});
    const sortItems = flatten(sort.items ?? {});
    const sortField = flatten(sortItems.properties?.field ?? {});
    const mentioned = responseIncludes(operation);
    const sortOrder = flatten(sortItems.properties?.order ?? {});
    const includes = flatten(requestProperties.includes ?? {});

    const filters = {};
    for (const [name, child] of Object.entries(filter.properties ?? {})) {
        filters[name] = describeFilter(child);
    }

    const response = mediaSchema(operation.responses?.['200']?.content);
    const responseProperties = response.properties ?? {};
    const data = flatten(responseProperties.data ?? {});
    const responseStatus = Object.keys(operation.responses ?? {}).sort();

    return {
        tags: operation.tags ?? [],
        deprecated: operation.deprecated === true,
        request: {
            properties: Object.keys(requestProperties).sort(),
            required: (request.required ?? []).slice().sort(),
            filters,
            filter_keys: Object.keys(filters).sort(),
            filter_required: (filter.required ?? []).slice().sort(),
            sort_fields: sortFields(sortField),
            sort_orders: (sortOrder.enum ?? []).slice().sort(),
            declares_pagination: 'page' in requestProperties,
            declares_sort: 'sort' in requestProperties,
            declares_includes: 'includes' in requestProperties,
            includes: declaredIncludes(requestProperties.includes ?? {}),
            // Includes named only in response field descriptions; `pagination`
            // (which adds a meta block) is reported separately below.
            response_includes: mentioned.filter((include) => include !== 'pagination'),
            declares_pagination_meta: mentioned.includes('pagination'),
            includes_example: includes.example ?? null,
            enums: requestEnums(request),
        },
        response: {
            statuses: responseStatus,
            properties: Object.keys(responseProperties).sort(),
            declares_meta: 'meta' in responseProperties,
            returns_collection: data.type === 'array',
            fields: fieldPaths(responseProperties.data ?? {}),
        },
    };
}

// --- write -------------------------------------------------------------------

function write(name, payload) {
    const target = path.join(here, name);
    fs.writeFileSync(target, `${JSON.stringify(payload, null, 2)}\n`);
    console.log(`${name} — specification ${pkg.version}`);
}

const header = {
    _comment: 'Generated — do not edit by hand. See generate-spec-fixtures.mjs.',
    specification_version: pkg.version,
};

// contract.json: every endpoint the specification declares.
const contract = { ...header, endpoint_count: 0, endpoints: {} };

for (const [route, methods] of Object.entries(spec.paths).sort(([a], [b]) => a.localeCompare(b))) {
    if (!methods.post) {
        continue;
    }
    const endpoint = route.replace(/^\//, '');
    contract.endpoints[endpoint] = extract(endpoint);
}

contract.endpoint_count = Object.keys(contract.endpoints).length;
write('contract.json', contract);

// Per-resource fixtures, kept for the dedicated contract tests.
for (const [resource, endpoints] of Object.entries(RESOURCES)) {
    const fixture = { ...header, endpoints: {} };

    for (const endpoint of endpoints) {
        const full = contract.endpoints[endpoint] ?? extract(endpoint);

        // The dedicated tests were written against the original, flatter shape.
        fixture.endpoints[endpoint] = {
            request: {
                properties: full.request.properties,
                required: full.request.required,
                filters: full.request.filter_keys,
                sort_fields: full.request.sort_fields,
                declares_pagination: full.request.declares_pagination,
                declares_sort: full.request.declares_sort,
                declares_includes: full.request.declares_includes,
                includes_example: full.request.includes_example,
            },
            response: {
                properties: full.response.properties,
                declares_meta: full.response.declares_meta,
                returns_collection: full.response.returns_collection,
                fields: full.response.fields,
            },
        };
    }

    write(`${resource}.json`, fixture);
}

// API-list-endpoint-contract.md: the human-readable view of the `.list` rows.
const repoRoot = path.resolve(here, '../../..');
const listRows = Object.entries(contract.endpoints)
    .filter(([endpoint]) => endpoint.endsWith('.list'))
    .map(([endpoint, contractRow]) => {
        const { request } = contractRow;
        const code = (values) => (values.length ? values.map((v) => `\`${v}\``).join(', ') : '—');
        const filters = request.properties.includes('filter')
            ? code(request.filter_keys)
            : '**none**';
        const vocabulary = [...new Set([...request.includes, ...request.response_includes])].sort();
        const includes = vocabulary.length ? code(vocabulary) : '—';
        const flags = [
            request.declares_pagination ? 'page' : null,
            request.declares_pagination_meta ? 'meta via `includes=pagination`' : null,
            !request.declares_includes && request.response_includes.length ? 'includes named in response only' : null,
            contractRow.deprecated ? 'deprecated' : null,
        ].filter(Boolean).join(', ') || '—';

        return `| \`${endpoint.replace(/\.list$/, '')}\` | ${filters} | ${code(request.sort_fields)} | ${includes} | ${flags} |`;
    });

fs.writeFileSync(
    path.join(repoRoot, 'API-list-endpoint-contract.md'),
    [
        '# API list-endpoint contract',
        '',
        `Generated from \`@teamleader/focus-api-specification\` v${pkg.version} by`,
        '`tests/Fixtures/specification/generate-spec-fixtures.mjs`. Do not edit by hand.',
        '',
        `${listRows.length} \`.list\` endpoints. The specification types \`includes\` as a free-form`,
        'string, so the values are collected from the request example and description and',
        'from response fields documented as "only included with `includes=...`".',
        '',
        '| Endpoint | Filters | Sort fields | Includes | Notes |',
        '|---|---|---|---|---|',
        ...listRows,
        '',
    ].join('\n')
);
console.log(`API-list-endpoint-contract.md — specification ${pkg.version}`);
