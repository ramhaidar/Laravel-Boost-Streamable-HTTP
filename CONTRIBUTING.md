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

If you find a security issue, please report it privately via a GitHub Security Advisory on the repository instead of opening a public issue.
