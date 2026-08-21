# Żywienie dla Zdrowia

Żywienie dla Zdrowia is an open-source WordPress plugin under development. It is intended to help medical-facility websites maintain a public section called “Żywienie dla zdrowia” (“Nutrition for Health”) and make the relevant documents and information easier to publish.

The repository is generic and is not tied to any particular organization or deployment.

> **Development status:** version `0.1.0` is an early development version. The standalone menu pipeline and period classifier, WordPress uploads integration, transient-backed catalog service, technical administration page, public menu shortcodes, and the standalone laboratory-result filesystem catalog pipeline are implemented. WordPress integration and public presentation of laboratory results remain planned. This version is not production-ready.

## Status

### Implemented

- A WordPress plugin bootstrap with a valid plugin header, direct-access guard, and activation lifecycle for menu storage.
- A standalone parser for menu filenames using the `YYYY-MM-DD_YYYY-MM-DD_name.pdf` convention, with an immutable document model and machine-readable parse errors.
- A standalone, non-recursive menu directory scanner that rejects symlinks, reports unrecognized entries, sorts documents by filename dates, and groups exact periods deterministically.
- A standalone PDF candidate validator with symlink, regular-file, readability, optional MIME, header, and bounded EOF checks.
- A standalone validated menu catalog builder that keeps only scanner-approved documents that pass limited PDF candidate validation, filters their period groups, and combines deterministic issues.
- A standalone, deterministic period classifier that divides validated menu groups into current, upcoming, and archived collections for an explicitly supplied calendar date.
- A standalone laboratory-result filename parser for `YYYY-MM-DD_YYYY-MM-DD_YYYY-MM-DD_name.pdf`, with immutable document and parse-result models plus machine-readable validation errors.
- A deterministic standalone matcher that associates laboratory-result documents with menu-period groups only when both period dates match exactly, while representing missing groups as valid unmatched results.
- A standalone, non-recursive laboratory-result directory scanner that rejects symlinks, preserves parser errors, and returns deterministically sorted recognized documents and issues.
- A standalone laboratory-result catalog pipeline that reuses the bounded PDF candidate validator, excludes rejected files, combines deterministic scanner and validation issues, and returns validated documents with matched or unmatched menu-period associations.
- WordPress uploads path resolution, activation-time creation of `zywienie-dla-zdrowia/jadlospisy/`, and a catalog provider connecting that directory to the standalone pipeline.
- A WordPress Transients API cache with an approximately five-minute lifetime and a catalog service providing cached reads plus programmatic refresh and clear operations.
- A native WordPress “Status publikacji” administration page with technical catalog status, current/upcoming/archived period counts based on the WordPress site date, safe issue descriptions, and a capability- and nonce-protected manual refresh using POST/Redirect/GET.
- The parameter-free `[zfdz_jadlospisy]` shortcode, which groups validated PDF candidates by exact period and renders current and upcoming menu links using the WordPress uploads base URL.
- The parameter-free `[zfdz_jadlospisy_archiwum]` shortcode, which renders archived menu periods newest first while preserving exact-period grouping.
- PHPUnit tests for the filename parser, filesystem scanner, PDF candidate validator, validated catalog pipeline, and period classifier without loading WordPress.
- Development-only Composer tooling for PHP_CodeSniffer and WordPress Coding Standards.
- Initial project, security, contribution, and product-specification documentation.
- A CI workflow for Composer validation, PHP syntax checking, PHPCS, and PHPUnit.

### Planned for v1.0

- Menus (jadłospisy).
- The `badania/` directory and its activation lifecycle, WordPress storage/cache/admin integration for laboratory results, latest-result policy, public shortcode, and frontend links.
- Educational materials (materiały edukacyjne).
- A configurable link to an external feedback form or survey.
- Any additional server-side, malware-detection, or document-sanitization controls required by a deployment.
- Configuration through the WordPress Options API.
- The aggregate `[zywienie_dla_zdrowia]` shortcode, remaining module shortcodes, an expanded administration dashboard, and optional frontend styling.
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

The implemented scanner accepts a trusted directory path from application configuration, examines only its direct entries, rejects symlinks, and never reads or executes document content. The catalog builder validates only filename candidates approved by that scanner and combines scanner and validator issues without exposing source paths. Its final catalog contains candidates that passed filename validation, entry-type validation, and limited PDF candidate validation. WordPress storage rejects direct symlinks and conflicting files at the two plugin-managed directory paths. This does not constitute malware scanning, PDF sanitization, full PDF structure validation, or a guarantee of document safety, and it does not replace server security, antivirus controls, or administrator review. The administration page escapes untrusted entry names, and its refresh handler independently enforces `manage_options` plus a WordPress nonce. Future WordPress-facing functionality will follow the same validation, late escaping, capability, and nonce rules. Uploaded content will never be included or executed as PHP.

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

Loading the plugin or creating the service performs no transient or filesystem operations. Work begins only when a consumer explicitly requests, refreshes, or clears the catalog. The implemented administration button performs an explicit protected refresh; ordinary page rendering uses the cached `get_catalog()` path.

Files may later be delivered by an externally configured restricted SFTP account. The plugin does not implement SFTP, and server administrators remain responsible for limiting that account to the document directory. Credentials must never be stored in this repository. Files delivered outside WordPress become visible after the short cache expires or earlier after an explicit programmatic refresh.

## Administration status page

Administrators with the `manage_options` capability can open **Żywienie dla Zdrowia → Status publikacji**. The page obtains its data only through `ZFDZ_WordPress_Menu_Catalog_Service`, displays the technical menu-module status, document/period/issue counts, and safe Polish descriptions of detected issues. It does not display filesystem paths or document links.

For a successful catalog, the page obtains the reference date once from WordPress `current_datetime()` and classifies existing period groups as current (`start_date <= today <= end_date`), upcoming (`start_date > today`), or archived (`end_date < today`). The boundaries are inclusive. The page displays the reference date, the three period counts, and a separate notice stating whether at least one menu period applies today. It does not yet display period or document lists.

Manual refresh is a classic POST/Redirect/GET flow through `admin-post.php`. The handler checks `manage_options` and a WordPress nonce before calling `refresh_catalog()`, then redirects with only a whitelisted success or error status. The page has no custom CSS or JavaScript.

At this stage, technical status **OK** means only that the catalog is technically accessible and contains no scanner or validator issues. A missing current period is reported separately and does not turn the technical status into an error. Neither status means that all required materials have been published or that the organization complies with legal requirements.

Period classification is calculated after the catalog is read from cache and is not stored in the transient. Consequently, a change of the WordPress site date immediately affects classification during the next page render without invalidating the cached catalog.

## Public menu shortcodes

Place the parameter-free current/upcoming shortcode on a WordPress page:

```text
[zfdz_jadlospisy]
```

It reads the existing cached validated catalog, classifies groups against the current WordPress site date, and renders **Aktualne jadłospisy** followed by **Nadchodzące jadłospisy**. Documents sharing an exact date range remain grouped under one period heading. Upcoming periods are displayed nearest first. Empty sections remain visible with a clear message, while a technical catalog or uploads-URL failure produces a short public unavailable message without diagnostic details.

Place the separate parameter-free archive shortcode on an archive page:

```text
[zfdz_jadlospisy_archiwum]
```

It renders only archived periods (`end_date < today`) under **Archiwum jadłospisów**, newest first. Documents from the same exact period remain grouped, and an empty archive remains visible with the message **Brak archiwalnych jadłospisów.** The existing `[zfdz_jadlospisy]` shortcode continues to render only current and upcoming periods.

Links in both shortcodes are generated from the `baseurl` returned by `wp_get_upload_dir()` and a `rawurlencode()`-encoded original filename. The visible label uses the parsed document name. Entry-level issues and their filenames are never rendered, and valid documents remain available when the catalog also contains issues. Archived periods are linked only by `[zfdz_jadlospisy_archiwum]`, not by `[zfdz_jadlospisy]`.

Separating archived documents into their own shortcode is presentation behavior, not access control. A file stored in public WordPress uploads may remain reachable through its direct URL if that URL is known, regardless of whether either shortcode currently links it. This stage does not add private storage, download proxying, URL blocking, or web-server rules. Linked files are validated PDF candidates only; the existing checks do not provide malware scanning, sanitization, full PDF parsing, or a safety guarantee.

Both shortcodes use `get_catalog()` and never force a refresh. Files delivered outside WordPress become visible when the approximately five-minute cache expires or after an administrator uses the protected manual refresh. Period classification uses a fresh WordPress site date on every render and is not cached.

## Standalone laboratory-result filesystem pipeline

Stage 11 defines the filename contract `YYYY-MM-DD_YYYY-MM-DD_YYYY-MM-DD_name.pdf`. The first two dates identify the exact menu period, the third is the laboratory-result date, and the remaining non-empty part is the display name. The result date must be a real calendar date but may fall before, inside, or after the referenced menu period.

The standalone parser rejects path input, non-PDF extensions, malformed or impossible dates, reversed menu periods, invalid UTF-8, control characters, and invalid names. It returns an immutable document or one stable machine-readable error. The non-recursive scanner rejects symlinks and unsupported entry types, passes only regular-file basenames to the parser, and never opens document content. The catalog builder validates only scanner-approved filenames with the existing bounded PDF candidate validator, combines scanner and validator issues deterministically, and sends validated candidates to the exact-period matcher.

The matcher compares only `menu_start_date` and `menu_end_date` with existing `ZFDZ_Menu_Period_Group` dates. An absent exact period produces an unmatched association, not a parse, PDF-validation, or catalog issue. Associations and final documents are ordered deterministically by result date descending, menu dates descending, and original filename using binary `strcmp()` ascending. No latest-result selection policy is implemented.

The plugin does not yet create a `badania/` directory and does not provide WordPress storage, cache, administration UI, shortcode, or public links for laboratory results. The standalone pipeline accepts a trusted directory path from a future application layer. Associating a result with a menu period from filename dates is a technical mechanism only. The plugin does not interpret laboratory content, assess the result, or confirm compliance with norms or legal requirements.

## Development

The minimum supported environment is WordPress 6.8 and PHP 8.2. PHP 8.3 or later is recommended. Development tooling requires Composer 2. Install development dependencies and run all checks with:

```bash
composer install
composer validate --strict
composer lint
composer test
```

There are no runtime Composer dependencies. The unit tests run without WordPress and cover the standalone menu filename parser, directory scanner, PDF candidate validator, validated catalog pipeline, current/upcoming/archive period classifier, laboratory-result filename parser, exact-period matcher, non-recursive scanner, and validated catalog pipeline. The WordPress-specific storage lifecycle, transient cache layer, administration page, and public shortcodes are covered by lint and PHPCS at this stage and require manual smoke tests after review. The aggregate shortcode, remaining module frontend, expanded administration, and configuration are still planned.

Contributions should follow [CONTRIBUTING.md](CONTRIBUTING.md) and the repository instructions in [AGENTS.md](AGENTS.md).

## Legal notice

This plugin is a technical publishing tool. It is not legal advice and does not assess, guarantee, or certify that a medical facility or any other organization complies with applicable law. The organization’s administrator remains responsible for the content, completeness, accuracy, suitability, and manner of publication of all information.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
