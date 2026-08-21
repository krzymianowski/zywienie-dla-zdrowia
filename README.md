# Żywienie dla Zdrowia

Żywienie dla Zdrowia is an open-source WordPress plugin under development. It is intended to help medical-facility websites maintain a public section called “Żywienie dla zdrowia” (“Nutrition for Health”) and make the relevant documents and information easier to publish.

The repository is generic and is not tied to any particular organization or deployment.

> **Development status:** version `0.1.0` is an early development version. The standalone menu pipeline, WordPress uploads integration, and transient-backed catalog service are implemented, but all administration and user-facing features remain planned. This version is not production-ready.

## Status

### Implemented

- A WordPress plugin bootstrap with a valid plugin header, direct-access guard, and activation lifecycle for menu storage.
- A standalone parser for menu filenames using the `YYYY-MM-DD_YYYY-MM-DD_name.pdf` convention, with an immutable document model and machine-readable parse errors.
- A standalone, non-recursive menu directory scanner that rejects symlinks, reports unrecognized entries, sorts documents by filename dates, and groups exact periods deterministically.
- A standalone PDF candidate validator with symlink, regular-file, readability, optional MIME, header, and bounded EOF checks.
- A standalone validated menu catalog builder that keeps only scanner-approved documents that pass limited PDF candidate validation, filters their period groups, and combines deterministic issues.
- WordPress uploads path resolution, activation-time creation of `zywienie-dla-zdrowia/jadlospisy/`, and a catalog provider connecting that directory to the standalone pipeline.
- A WordPress Transients API cache with an approximately five-minute lifetime and a catalog service providing cached reads plus programmatic refresh and clear operations.
- PHPUnit tests for the filename parser, filesystem scanner, PDF candidate validator, and validated catalog pipeline without loading WordPress.
- Development-only Composer tooling for PHP_CodeSniffer and WordPress Coding Standards.
- Initial project, security, contribution, and product-specification documentation.
- A CI workflow for Composer validation, PHP syntax checking, PHPCS, and PHPUnit.

### Planned for v1.0

- Menus (jadłospisy).
- Laboratory test results (wyniki badań laboratoryjnych).
- Educational materials (materiały edukacyjne).
- A configurable link to an external feedback form or survey.
- Any additional server-side, malware-detection, or document-sanitization controls required by a deployment.
- Configuration through the WordPress Options API and an administration control for manual catalog refresh.
- An administration panel, publication status view, shortcodes, and public frontend.
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

The implemented scanner accepts a trusted directory path from application configuration, examines only its direct entries, rejects symlinks, and never reads or executes document content. The catalog builder validates only filename candidates approved by that scanner and combines scanner and validator issues without exposing source paths. Its final catalog contains candidates that passed filename validation, entry-type validation, and limited PDF candidate validation. WordPress storage rejects direct symlinks and conflicting files at the two plugin-managed directory paths. This does not constitute malware scanning, PDF sanitization, full PDF structure validation, or a guarantee of document safety, and it does not replace server security, antivirus controls, or administrator review. Future WordPress-facing functionality will validate and sanitize input, escape output as late as possible, and enforce capabilities and nonces. Uploaded content will never be included or executed as PHP.

The v1.0 design assumes that the plugin itself will not store patient data or survey responses, install cookies, send telemetry, or send data to external services. The survey feature will only link to a URL configured by an administrator; the plugin will not claim that the external form is anonymous.

The public frontend is planned to use semantic HTML, keyboard-accessible interactions, visible focus states, responsive and neutral styles, and meaningful information that does not rely on color alone. Basic features must work without JavaScript. No formal WCAG conformance is claimed without appropriate testing.

See [SECURITY.md](SECURITY.md) for vulnerability-reporting guidance.

## WordPress storage lifecycle

During activation, the plugin resolves the current uploads base directory through `wp_get_upload_dir()` and idempotently ensures this structure with `wp_mkdir_p()`:

```text
<WordPress uploads basedir>/
└── zywienie-dla-zdrowia/
    └── jadlospisy/
```

Normal plugin loading does not create directories, scan documents, or build a catalog. Deactivation does not remove directories or documents, and this stage adds no uninstall cleanup. The WordPress catalog provider resolves the directory only when its `get_catalog()` method is explicitly called.

## Menu catalog cache

The WordPress catalog service caches only successful `ZFDZ_Menu_Catalog_Result` objects for approximately five minutes under the fixed, versioned transient key `zfdz_menu_catalog_v1`. Successful catalogs may include entry-level issues. Directory failures are returned without being cached. An unexpected transient value is deleted and treated as a cache miss. Programmatic refresh deletes the previous value before rebuilding the catalog, while cache clearing only deletes the transient and never scans or changes the filesystem.

Loading the plugin or creating the service performs no transient or filesystem operations. Work begins only when a consumer explicitly requests, refreshes, or clears the catalog. An administration button for manual refresh is still planned and is not implemented.

Files may later be delivered by an externally configured restricted SFTP account. The plugin does not implement SFTP, and server administrators remain responsible for limiting that account to the document directory. Credentials must never be stored in this repository. Files delivered outside WordPress become visible after the short cache expires or earlier after an explicit programmatic refresh.

## Development

The minimum supported environment is WordPress 6.8 and PHP 8.2. PHP 8.3 or later is recommended. Development tooling requires Composer 2. Install development dependencies and run all checks with:

```bash
composer install
composer validate --strict
composer lint
composer test
```

There are no runtime Composer dependencies. The unit tests run without WordPress and cover the standalone menu filename parser, directory scanner, PDF candidate validator, and validated catalog pipeline. The WordPress-specific storage lifecycle and transient cache layer are covered by lint and PHPCS at this stage and require manual smoke tests after review. Administration, a manual-refresh UI, shortcodes, frontend rendering, and current/upcoming/archive classification remain planned.

Contributions should follow [CONTRIBUTING.md](CONTRIBUTING.md) and the repository instructions in [AGENTS.md](AGENTS.md).

## Legal notice

This plugin is a technical publishing tool. It is not legal advice and does not assess, guarantee, or certify that a medical facility or any other organization complies with applicable law. The organization’s administrator remains responsible for the content, completeness, accuracy, suitability, and manner of publication of all information.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
