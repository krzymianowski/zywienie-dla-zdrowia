# Żywienie dla Zdrowia

Żywienie dla Zdrowia is an open-source WordPress plugin under development. It is intended to help medical-facility websites maintain a public section called “Żywienie dla zdrowia” (“Nutrition for Health”) and make the relevant documents and information easier to publish.

The repository is generic and is not tied to any particular organization or deployment.

> **Development status:** version `0.1.0` is an early development version. The business features described as planned below are not implemented.

## Status

### Implemented

- A passive WordPress plugin bootstrap with a valid plugin header and direct-access guard.
- Development-only Composer tooling for PHP_CodeSniffer and WordPress Coding Standards.
- Initial project, security, contribution, and product-specification documentation.
- A CI workflow for Composer validation, PHP syntax checking, and PHPCS.

### Planned for v1.0

- Menus (jadłospisy).
- Laboratory test results (wyniki badań laboratoryjnych).
- Educational materials (materiały edukacyjne).
- A configurable link to an external feedback form or survey.
- Filesystem-backed documents under `wp-content/uploads/zywienie-dla-zdrowia/`.
- Configuration through the WordPress Options API and caching through the Transients API.
- Shortcodes listed in the [working v1.0 specification](docs/specification-v1.0.md).

### Out of scope for v1.0

- Custom database tables and Custom Post Types.
- A REST API or a JavaScript framework.
- Storing survey responses or patient data.
- Telemetry, plugin-installed cookies, and automatic document deletion.
- Assessing, guaranteeing, or certifying legal compliance.

## Intended users and problem

The project is intended for teams responsible for public WordPress websites of medical facilities. It aims to provide a small, predictable way to present nutrition-related documents and a link to an externally managed survey without introducing a content framework or a separate data store.

## Regulatory context

The project was created as a technical aid for publishing information associated with the Polish “Żywienie dla zdrowia” section in the context of the Regulation of the Minister of Health of 12 December 2025 on the organizational standard for collective nutrition in a medical entity providing inpatient hospital services (Journal of Laws of 2025, item 1780). The [official act is available in ELI](https://eli.gov.pl/eli/DU/2025/1780/ogl).

The planned scope supports publishing the applicable meal plans, the latest laboratory test result with a reference to the relevant meal plan, educational materials, and access to a channel for anonymous feedback. The plugin will not automatically interpret or legally validate how these requirements are fulfilled.

## Security and privacy principles

Future functionality will validate and sanitize input, escape output as late as possible, enforce WordPress capabilities and nonces, and defend filesystem operations against path traversal and untrusted filenames. Uploaded content will never be included or executed as PHP.

The v1.0 design assumes that the plugin itself will not store patient data or survey responses, install cookies, send telemetry, or send data to external services. The survey feature will only link to a URL configured by an administrator; the plugin will not claim that the external form is anonymous.

The public frontend is planned to use semantic HTML, keyboard-accessible interactions, visible focus states, responsive and neutral styles, and meaningful information that does not rely on color alone. Basic features must work without JavaScript. No formal WCAG conformance is claimed without appropriate testing.

See [SECURITY.md](SECURITY.md) for vulnerability-reporting guidance.

## Development

The minimum supported environment is WordPress 6.8 and PHP 8.2. PHP 8.3 or later is recommended. Development tooling requires Composer 2. Install development dependencies and run all checks with:

```bash
composer install
composer validate --strict
composer lint
```

There are no runtime Composer dependencies and no unit-test infrastructure at this stage because the plugin has no testable business component yet.

Contributions should follow [CONTRIBUTING.md](CONTRIBUTING.md) and the repository instructions in [AGENTS.md](AGENTS.md).

## Legal notice

This plugin is a technical publishing tool. It is not legal advice and does not assess, guarantee, or certify that a medical facility or any other organization complies with applicable law. The organization’s administrator remains responsible for the content, completeness, accuracy, suitability, and manner of publication of all information.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
