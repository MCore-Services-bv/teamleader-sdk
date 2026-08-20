#!/usr/bin/env node
//
// Regenerates the specification fixtures used by the *SpecContractTest suite.
//
//   npm install --no-save @teamleader/focus-api-specification js-yaml
//   node tests/Fixtures/specification/generate-spec-fixtures.mjs
//
// Writes one JSON file per resource into the directory this script lives in.
// The output is a mechanical extract of the specification — no judgement is
// applied here, so a fixture that disagrees with the SDK is a finding about the
// SDK, not about this script.
//
// Add a resource by appending to RESOURCES below.

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const yaml = require('js-yaml');
const here = path.dirname(fileURLToPath(import.meta.url));

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
function flatten(schema) {
    if (!schema || typeof schema !== 'object') {
        return {};
    }

    if (!Array.isArray(schema.allOf)) {
        return schema;
    }

    const merged = { type: undefined, properties: {} };

    for (const branch of schema.allOf) {
        const resolved = flatten(branch);
        merged.type = resolved.type ?? merged.type;
        merged.items = resolved.items ?? merged.items;
        merged.enum = resolved.enum ?? merged.enum;
        Object.assign(merged.properties, resolved.properties ?? {});
        if (resolved.nullable) {
            merged.nullable = true;
        }
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

function walk(schema, prefix, out) {
    const node = flatten(schema);

    if (node.type === 'array') {
        out[prefix] = describe(node);
        const items = flatten(node.items ?? {});
        for (const [name, child] of Object.entries(items.properties ?? {})) {
            walk(child, `${prefix}[].${name}`, out);
        }

        return out;
    }

    out[prefix] = describe(node);

    for (const [name, child] of Object.entries(node.properties ?? {})) {
        walk(child, `${prefix}.${name}`, out);
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

function requestSchema(operation) {
    const content = operation?.requestBody?.content ?? {};
    const media = content['application/json'] ?? Object.values(content)[0] ?? {};

    return flatten(media.schema ?? {});
}

function responseSchema(operation) {
    const content = operation?.responses?.['200']?.content ?? {};
    const media = content['application/json'] ?? Object.values(content)[0] ?? {};

    return flatten(media.schema ?? {});
}

// --- extraction --------------------------------------------------------------

function extract(endpoint) {
    const operation = spec.paths[`/${endpoint}`]?.post;

    if (!operation) {
        throw new Error(`No POST /${endpoint} in specification ${pkg.version}`);
    }

    const request = requestSchema(operation);
    const requestProperties = request.properties ?? {};

    const filter = flatten(requestProperties.filter ?? {});
    const sort = flatten(requestProperties.sort ?? {});
    const sortItems = flatten(sort.items ?? {});
    const sortField = flatten(sortItems.properties?.field ?? {});
    const includes = flatten(requestProperties.includes ?? {});

    const response = responseSchema(operation);
    const responseProperties = response.properties ?? {};
    const data = flatten(responseProperties.data ?? {});

    return {
        request: {
            properties: Object.keys(requestProperties).sort(),
            required: (request.required ?? []).sort(),
            filters: Object.keys(filter.properties ?? {}).sort(),
            sort_fields: (sortField.enum ?? []).slice().sort(),
            declares_pagination: 'page' in requestProperties,
            declares_sort: 'sort' in requestProperties,
            declares_includes: 'includes' in requestProperties,
            includes_example: includes.example ?? null,
        },
        response: {
            properties: Object.keys(responseProperties).sort(),
            declares_meta: 'meta' in responseProperties,
            returns_collection: data.type === 'array',
            fields: fieldPaths(responseProperties.data ?? {}),
        },
    };
}

// --- write -------------------------------------------------------------------

for (const [resource, endpoints] of Object.entries(RESOURCES)) {
    const fixture = {
        _comment:
            'Generated — do not edit by hand. See generate-spec-fixtures.mjs.',
        specification_version: pkg.version,
        endpoints: {},
    };

    for (const endpoint of endpoints) {
        fixture.endpoints[endpoint] = extract(endpoint);
    }

    const target = path.join(here, `${resource}.json`);
    fs.writeFileSync(target, `${JSON.stringify(fixture, null, 2)}\n`);
    console.log(`${path.basename(target)} — specification ${pkg.version}`);
}
