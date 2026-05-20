# Changelog

All notable changes to `ramhaidar/laravel-boost-streamable-http` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial implementation. Registers Laravel Boost's MCP server (`Laravel\Boost\Mcp\Boost`) on a Laravel MCP web (Streamable HTTP) endpoint via `Mcp::web(...)`.
- `enabled`, `path`, `middleware`, `domain`, `prefix`, `as` config keys.
- Optional production warning log when enabled without middleware (`warn_unprotected_in_production`).
- `class_exists` guards with informative `RuntimeException` if `laravel/boost` or `laravel/mcp` is missing.
- HTTP functional test posting JSON-RPC `initialize` against the registered endpoint.
- Test coverage: disabled-by-default, default path, custom path, middleware applied to all verbs, prefix, name prefix, domain, production warn-log toggling, JSON-RPC initialize, stdio compatibility.
- GitHub Actions CI matrix (PHP × Laravel × MCP).
- Laravel Pint config.
