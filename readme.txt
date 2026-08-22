=== Żywienie dla Zdrowia ===
Requires at least: 6.8
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Development-stage tooling for publishing information related to a “Żywienie dla zdrowia” section on medical-facility websites.

== Description ==

Żywienie dla Zdrowia is an open-source WordPress plugin under development. It is planned to support menus, laboratory test results, educational materials, and a configurable link to an external feedback form or survey.

The minimum supported environment is WordPress 6.8 and PHP 8.2. PHP 8.3 or later is recommended.

Version 0.1.0 contains standalone validated menu and laboratory-result catalog pipelines, a deterministic standalone latest laboratory-result selector, a standalone technical public-presentation policy, and a WordPress public-presentation resolver/service. During activation, the plugin resolves the current uploads base directory through WordPress API and idempotently creates `zywienie-dla-zdrowia/jadlospisy/` and `zywienie-dla-zdrowia/badania/`. The menu catalog service caches successful results for approximately five minutes under `zfdz_menu_catalog_v1`. A coordinated laboratory-result service obtains that menu catalog, distinguishes menu and laboratory directory failures, and caches only successful laboratory-result catalogs for 300 seconds under `zfdz_lab_result_catalog_v1`. Its order-independent SHA-256 fingerprint depends only on menu-period start/end pairs, so changed menu periods invalidate stale laboratory matching. Successful laboratory catalogs with issues or unmatched associations remain cacheable. The technical “Status publikacji” administration page reports both menu and laboratory-result status, including matched, unmatched, issue, and derived latest-result details, and its protected refresh updates both catalogs. Public menu shortcodes and the candidate-only `[zfdz_badania]` shortcode are implemented; the aggregate frontend and Options API configuration remain planned. Deactivation does not delete documents. This version is not presented as a stable release.

For a successful laboratory catalog, the administration page invokes the standalone selector without implementing its own sorting. `EMPTY` means that no validated result is available. `MATCHED` and `UNMATCHED` display the selected document name, encoded result date, referenced menu period, and technical association state. Input order does not affect selection, a latest unmatched result does not fall back to an older match, and the derived selection is not cached separately. A matched selection is not an automatic publication decision, and unavailable catalogs are not presented as empty selections.

The standalone public presentation policy maps `EMPTY` to `NO_RESULT`, `MATCHED` to the technical `CANDIDATE` state, and `UNMATCHED` to `BLOCKED_UNMATCHED`. A blocked decision exposes no association or document and never falls back to an older match. The policy does not inspect the full catalog or unrelated issues. `CANDIDATE` is not legal, medical, administrative, or security approval. Catalog unavailability is separate from `NO_RESULT`, so the policy must not replace coordinated failure handling. The policy itself adds no cache, URL, shortcode, or frontend output.

The WordPress public-presentation resolver maps menu or laboratory catalog failures to `UNAVAILABLE` with distinct reasons and bypasses the selector and policy. For successful coordinated data it uses the existing selector and policy to return `NO_RESULT`, `BLOCKED_UNMATCHED`, or `CANDIDATE`. It never converts unavailability to no-result, never exposes a blocked document, and does not inspect unrelated issues. The thin service adds no separate cache, refresh API, or administration changes.

The parameter-free `[zfdz_badania]` shortcode consumes exactly one public-presentation result. Only `CANDIDATE` resolves the managed `badania/` uploads `baseurl` and links the exact parser-approved original filename after `rawurlencode()` encoding; visible output uses the escaped display name and dates. Unavailable, empty, blocked, or URL-resolution failures produce short public messages without diagnostic details or document URLs. The shortcode does not inspect issues or associations and never falls back to an older match.

The catalog keeps only scanner-approved documents that pass limited PDF candidate validation and combines scanner and validation issues. These checks do not provide malware scanning, PDF sanitization, full PDF structure validation, or a guarantee of document safety.

The plugin is a technical publishing tool, not legal advice. It does not guarantee legal compliance. Site administrators remain responsible for the content, completeness, and accuracy of published information.

== Installation ==

This development version provides a technical administration status page, public current/upcoming and archive menu shortcodes, and the public candidate-only laboratory-result shortcode. For development, place the repository in the WordPress plugins directory and install development dependencies with Composer. Do not use it as a substitute for a production-ready release.

== Frequently Asked Questions ==

= Does this version display documents or provide shortcodes? =

The parameter-free `[zfdz_jadlospisy]` shortcode displays grouped current and upcoming validated menu candidates. The separate parameter-free `[zfdz_jadlospisy_archiwum]` shortcode displays grouped archived periods newest first. `[zfdz_badania]` displays only a latest matched technical candidate and otherwise a safe state-specific message. `[zywienie_dla_zdrowia]` and the remaining module shortcodes are still planned.

= Are archived documents private because they use a separate shortcode? =

No. Separating current/upcoming and archived links is presentation behavior only. A file in public WordPress uploads may remain accessible through its direct URL whether or not either shortcode currently links it. This version does not implement access control or private storage.

= Does the plugin store survey responses or patient data? =

No. The v1.0 design plans only a configurable external survey link and does not plan to store survey responses or patient data.

= Does the laboratory-result foundation interpret test content? =

No. It parses technical filename metadata, scans only direct directory entries, performs limited PDF candidate validation, and matches the two encoded menu-period dates exactly. It does not interpret laboratory content, assess a result, or confirm compliance with norms or legal requirements. A standalone selector identifies the latest association by result date and deterministic metadata tie-breakers. If that latest association is unmatched, it does not fall back to an older match. A separate standalone policy can identify a matched latest result as a technical presentation candidate, but this is not an approval or publication decision. The administration page displays the latest selection, and `[zfdz_badania]` links only a technical candidate without interpreting the result.

= Does the plugin guarantee that an external survey is anonymous? =

No. The privacy properties of an external form are outside the plugin's control and must be assessed by the site administrator.

== Changelog ==

= Unreleased =

* Added the public `[zfdz_badania]` shortcode with candidate-only WordPress uploads URL resolution, encoded filenames, escaped metadata, and safe messages for every non-candidate state.
* Added a WordPress laboratory-result public-presentation result, deterministic resolver, and thin service preserving unavailable, no-result, blocked-unmatched, and candidate states.
* Added a standalone laboratory-result public presentation policy with immutable no-result, candidate, and blocked-unmatched decisions without fallback or public output.
* Added latest laboratory-result details to “Status publikacji”, derived from the standalone selector without fallback or a separate cache.
* Added a standalone menu filename parser, document model, parse result, and unit tests.
* Added a standalone menu directory scanner with issue reporting, deterministic sorting, exact-period grouping, and filesystem tests.
* Added a standalone PDF candidate validator, validation result, optional MIME checks, bounded header and EOF checks, and unit tests.
* Added a standalone validated menu catalog builder with filtered period groups and combined deterministic issues.
* Added a standalone menu period classifier for current, upcoming, and archived groups with unit tests.
* Added a standalone laboratory-result filename parser, immutable document and parse-result models, and unit tests for the `YYYY-MM-DD_YYYY-MM-DD_YYYY-MM-DD_name.pdf` contract.
* Added deterministic exact-period matching with immutable matched and unmatched laboratory-result associations.
* Added standalone deterministic latest laboratory-result selection with immutable empty, matched, and unmatched outcomes and no fallback to an older match.
* Added a standalone non-recursive laboratory-result directory scanner with immutable scan issues and results.
* Added a standalone validated laboratory-result catalog pipeline reusing the bounded PDF candidate validator and exact-period matcher.
* Added WordPress uploads-based laboratory-result storage with activation-time creation of `zywienie-dla-zdrowia/badania/` and non-destructive retention.
* Added a WordPress laboratory-result catalog provider that passes explicitly supplied validated menu groups to the standalone pipeline.
* Added coordinated menu/laboratory availability results and a laboratory-result catalog service.
* Added the fingerprint-aware `zfdz_lab_result_catalog_v1` transient cache with a 300-second TTL and coordinated refresh.
* Extended “Status publikacji” with laboratory-result technical status, matched/unmatched counts, safe issue details, and coordinated protected refresh.
* Added WordPress uploads path resolution, activation-time menu directory creation, and a catalog provider.
* Added a WordPress Transients API cache and menu catalog service with programmatic refresh and clear operations.
* Added a technical “Status publikacji” administration page with WordPress-site-date period counters, a current-menu notice, and protected manual catalog refresh.
* Added the public `[zfdz_jadlospisy]` shortcode for grouped current and upcoming menu links using the WordPress uploads base URL.
* Added the public `[zfdz_jadlospisy_archiwum]` shortcode for grouped archived menu links ordered from newest to oldest.
* Added the initial plugin bootstrap, project documentation, and development quality tooling.
