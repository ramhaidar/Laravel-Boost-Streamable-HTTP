# Security Policy

## Reporting a vulnerability

If you discover a security issue in `ramhaidar/laravel-boost-streamable-http`, please report it privately. Do **not** open a public GitHub issue.

Use one of the following channels:

- GitHub Security Advisory: https://github.com/ramhaidar/laravel-boost-streamable-http/security/advisories/new
- Direct email to the repository owner if you cannot use GitHub's advisory flow.

When reporting, include:

- Package version (`composer show ramhaidar/laravel-boost-streamable-http`).
- Affected `laravel/boost` and `laravel/mcp` versions.
- A minimal reproduction (steps, request payload, configuration).
- Impact assessment (data exposure, RCE, auth bypass, etc.).

You can expect:

- Acknowledgement within a few business days.
- A fix or mitigation plan, with credit to the reporter (unless you ask to remain anonymous).
- Coordinated disclosure once a patched release is available.

## Scope

This package is a thin opt-in adapter that exposes Laravel Boost over Laravel MCP's web (Streamable HTTP) transport. It is intended for **local developer use** behind authentication and HTTPS.

In-scope vulnerabilities (examples):

- Default behavior that exposes the endpoint without explicit opt-in.
- Configuration patterns that silently weaken Laravel's middleware stack.
- Information leaks from the package itself (not from `laravel/boost` or `laravel/mcp` upstream).

Out-of-scope:

- Vulnerabilities in `laravel/boost` or `laravel/mcp` themselves — please report those upstream.
- Misuse such as enabling the endpoint in production with no middleware. The README warns against this; it is not a vulnerability in the package.
- Issues that require an attacker to already have local code execution against the developer's machine.

Thanks for keeping the ecosystem safe.
