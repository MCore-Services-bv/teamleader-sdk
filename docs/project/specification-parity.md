# Specification Parity

Teamleader publishes a machine-readable description of the Focus API:
[`@teamleader/focus-api-specification`](https://www.npmjs.com/package/@teamleader/focus-api-specification).
The SDK is checked against it on every push. The pinned version is currently
**1.221.0**.

## Why it matters

The API answers `200 OK` to a filter, sort field, include or body field it
does not recognise, and ignores it. Nothing tells you a name was wrong: a
mistyped filter returns every record, a mistyped field on an update changes
nothing. A hand-written SDK that drifts from the API fails in exactly this
silent way.

Between v2.2.4 and v2.3.0 every resource was read against the specification,
one category per release. The audit started with **111 divergences** and ended
with none open. Test methods went from 227 to 608.

## What is checked

**Spec contract tests.** For each resource, the field lists, required fields
and enum values the SDK validates against are compared with a fixture generated
from the specification. These are the public constants shown under **Accepted
values** in the [API reference](../reference/README.md).

**The spec audit.** Every registered resource's endpoints, filters, sort
fields, includes and pagination are compared with the specification.
Differences are recorded in a baseline; CI fails on anything not in it, and on
any baseline entry that no longer applies. One difference is recorded as
accepted: `users.getWeekSchedule` is not wrapped, because Teamleader deprecated
it. `userSchedules()->forUser()` wraps its successor.

**Fixture freshness.** CI regenerates the fixtures from the pinned
specification and fails if they differ from what is committed.

**Reference freshness.** CI regenerates this documentation's API reference
from the code and fails if it differs from what is committed, so the reference
describes the SDK as released.

**Weekly watch.** A scheduled workflow audits the SDK against the newest
published specification and opens an issue when it finds a difference. API
changes arrive as a list of findings rather than as bug reports.

## When the specification and the API disagree

The specification is treated as authoritative until the live API is shown to
behave differently. Where it has been, such as an undeclared filter the API
honours or an include the specification mentions only in a response
description, the resource says so in its docblock, the reference page carries that note, and the
decision is recorded in the audit baseline with a reason.

If you find a call that behaves differently from its reference page, please
[open an issue](https://github.com/MCore-Services-bv/teamleader-sdk/issues).
Reports of calls that return *more* than expected, or ignore something passed
to them, are especially useful.

## Running the checks

From a clone of the repository:

```bash
npm install                      # the pinned specification
npm run spec:fixtures            # regenerate the fixtures

composer spec:audit -- --summary # counts per category
composer spec:audit -- --new     # anything not in the baseline
composer spec:check              # the CI gate
composer docs:check              # the API reference matches the code
```
