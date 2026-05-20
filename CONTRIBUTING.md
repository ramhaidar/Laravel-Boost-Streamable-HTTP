# Contributing

Thanks for your interest in improving `laravel-boost-streamable-http`.

This is a small, focused community package. Keep contributions narrow and aligned with the project's stated scope: an opt-in Streamable HTTP / web MCP transport for Laravel Boost.

## Reporting issues

When opening an issue, please include:

- Package version (`composer show ramhaidar/laravel-boost-streamable-http`).
- `laravel/boost` and `laravel/mcp` versions.
- Laravel and PHP versions.
- The contents of your published `config/laravel-boost-streamable-http.php` (redact secrets).
- Steps to reproduce, expected behavior, and what actually happened.
- Stack trace if applicable.

## Development setup

```bash
git clone https://github.com/ramhaidar/laravel-boost-streamable-http.git
cd laravel-boost-streamable-http
composer install
```

## Running tests

```bash
composer test
```

Or directly:

```bash
vendor/bin/phpunit
```

The package is tested against the latest stable `laravel/boost` and `laravel/mcp` releases by default. CI runs the matrix declared in `.github/workflows/tests.yml`.

## Code style

The project uses [Laravel Pint](https://github.com/laravel/pint) with the default Laravel preset. Run before submitting:

```bash
vendor/bin/pint
```

CI verifies style on every PR.

## Pull request guidelines

- Open against `main`.
- One logical change per PR.
- Add or update tests for any behavior change.
- Update `CHANGELOG.md` under the `Unreleased` section.
- Update `README.md` if you add or change configuration keys.
- Do not modify or fork `laravel/boost`. The package's whole point is to stay external.
- Do not introduce heavy dependencies. The package should remain a thin opt-in adapter.
- Do not enable the HTTP endpoint by default in any change.

## What is in scope

- Bug fixes against current stable `laravel/boost` and `laravel/mcp`.
- Compatibility updates for new Laravel, Boost, or MCP releases.
- Small, well-scoped configuration options for route registration (middleware, domain, prefix, route name).
- Documentation, tests, security warnings.

## What is out of scope

- Anything that requires modifying `laravel/boost` itself.
- Authentication strategies beyond passing arbitrary middleware names from config.
- MCP-client-specific installers or generators.
- Replicating Laravel Boost's stdio transport.

## Security

If you find a security issue, please follow [SECURITY.md](.github/SECURITY.md). Do not open a public issue.

## Release process (maintainers)

1. Update `CHANGELOG.md`: rename `[Unreleased]` to `[X.Y.Z] - YYYY-MM-DD` and start a fresh `[Unreleased]` heading above.
2. Verify locally:
   ```bash
   composer validate --strict
   composer test
   composer test:lint
   composer analyse
   ```
3. Commit the changelog change: `git commit -am "chore: release vX.Y.Z"`.
4. Tag and push: `git tag vX.Y.Z && git push origin main --tags`.
5. Verify CI is green for the tag.
6. If submitting to Packagist for the first time, register the repo at https://packagist.org/packages/submit and enable the GitHub webhook for auto-update.
7. Draft a GitHub Release for the tag with the CHANGELOG section as the body.
