# Contributing

Contributions are welcome. The full guide is
[CONTRIBUTING.md](https://github.com/MCore-Services-bv/teamleader-sdk/blob/main/CONTRIBUTING.md)
in the repository; the short version:

- **Bug reports** are the most valuable contribution. Include what you expected,
  what happened and how you worked around it. A call that returns *more* than
  expected, or ignores something you passed, is worth reporting even without an
  error.
- **Changes are checked against the specification**, not against the
  developer portal or the existing code. See
  [Specification parity](specification-parity.md).
- **Tests are expected** for new behaviour and every bug fix.

## Documentation

These docs live in the repository's `docs/` folder and are published from
`main`.

- The **guides** are written by hand; change them in the same pull request as
  the behaviour they describe.
- The **API reference** is generated from the resource classes. To improve a
  reference page, improve the code it comes from — a filter description, a
  docblock, a usage example — and run:

```bash
composer docs:build
```

CI fails when the committed reference does not match the code.

## Security

Report security issues by email to **help@mcore-services.be**, not in the issue
tracker. See [SECURITY.md](https://github.com/MCore-Services-bv/teamleader-sdk/blob/main/SECURITY.md).
