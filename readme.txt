=== Żywienie dla Zdrowia ===
Requires at least: 6.8
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Development-stage tooling for publishing information related to a “Żywienie dla zdrowia” section on medical-facility websites.

== Description ==

Żywienie dla Zdrowia is an open-source WordPress plugin under development. It is planned to support menus, laboratory test results, educational materials, and a configurable link to an external feedback form or survey.

The minimum supported environment is WordPress 6.8 and PHP 8.2. PHP 8.3 or later is recommended.

Version 0.1.0 contains a standalone validated menu catalog pipeline, a deterministic current/upcoming/archive period classifier, and their WordPress integration. During activation, the plugin resolves the current uploads base directory through WordPress API and idempotently creates `zywienie-dla-zdrowia/jadlospisy/` and `zywienie-dla-zdrowia/badania/`. A WordPress menu catalog provider connects the menu directory to the standalone menu pipeline only when explicitly requested. A menu catalog service caches successful results for approximately five minutes. The technical “Status publikacji” administration page displays menu catalog counters and issues, classifies periods against the current WordPress site date, reports whether a menu period applies today, and provides a capability- and nonce-protected manual refresh. The parameter-free `[zfdz_jadlospisy]` shortcode publicly renders grouped current and upcoming validated PDF candidates, while `[zfdz_jadlospisy_archiwum]` renders grouped archived periods newest first. Both use WordPress uploads URLs and the same cached menu catalog. Classification is calculated after catalog cache access and is not stored in the transient. Version 0.1.0 also contains a standalone laboratory-result filesystem catalog pipeline: a filename parser, non-recursive scanner, reused bounded PDF candidate validator, and exact-menu-period matcher producing matched or unmatched associations. A WordPress laboratory-result provider resolves the managed `badania/` directory and passes explicitly supplied validated menu groups to that standalone pipeline. Laboratory-result caching, administration, latest-result policy, and public display remain planned. Deactivation does not delete documents. This version is not presented as a stable release.

The catalog keeps only scanner-approved documents that pass limited PDF candidate validation and combines scanner and validation issues. These checks do not provide malware scanning, PDF sanitization, full PDF structure validation, or a guarantee of document safety.

The plugin is a technical publishing tool, not legal advice. It does not guarantee legal compliance. Site administrators remain responsible for the content, completeness, and accuracy of published information.

== Installation ==

This development version provides a technical administration status page and public current/upcoming and archive menu shortcodes. For development, place the repository in the WordPress plugins directory and install development dependencies with Composer. Do not use it as a substitute for a production-ready release.

== Frequently Asked Questions ==

= Does this version display documents or provide shortcodes? =

The parameter-free `[zfdz_jadlospisy]` shortcode displays grouped current and upcoming validated menu candidates. The separate parameter-free `[zfdz_jadlospisy_archiwum]` shortcode displays grouped archived periods newest first. `[zywienie_dla_zdrowia]` and the remaining module shortcodes are still planned.

= Are archived documents private because they use a separate shortcode? =

No. Separating current/upcoming and archived links is presentation behavior only. A file in public WordPress uploads may remain accessible through its direct URL whether or not either shortcode currently links it. This version does not implement access control or private storage.

= Does the plugin store survey responses or patient data? =

No. The v1.0 design plans only a configurable external survey link and does not plan to store survey responses or patient data.

= Does the laboratory-result foundation interpret test content? =

No. It parses technical filename metadata, scans only direct directory entries, performs limited PDF candidate validation, and matches the two encoded menu-period dates exactly. It does not interpret laboratory content, assess a result, or confirm compliance with norms or legal requirements. Activation creates the managed `badania/` directory, and an explicitly invoked WordPress provider can pass supplied validated menu groups into the standalone pipeline. Laboratory-result cache, administration, latest-result policy, and a public shortcode remain planned.

= Does the plugin guarantee that an external survey is anonymous? =

No. The privacy properties of an external form are outside the plugin's control and must be assessed by the site administrator.

== Changelog ==

= Unreleased =

* Added a standalone menu filename parser, document model, parse result, and unit tests.
* Added a standalone menu directory scanner with issue reporting, deterministic sorting, exact-period grouping, and filesystem tests.
* Added a standalone PDF candidate validator, validation result, optional MIME checks, bounded header and EOF checks, and unit tests.
* Added a standalone validated menu catalog builder with filtered period groups and combined deterministic issues.
* Added a standalone menu period classifier for current, upcoming, and archived groups with unit tests.
* Added a standalone laboratory-result filename parser, immutable document and parse-result models, and unit tests for the `YYYY-MM-DD_YYYY-MM-DD_YYYY-MM-DD_name.pdf` contract.
* Added deterministic exact-period matching with immutable matched and unmatched laboratory-result associations.
* Added a standalone non-recursive laboratory-result directory scanner with immutable scan issues and results.
* Added a standalone validated laboratory-result catalog pipeline reusing the bounded PDF candidate validator and exact-period matcher.
* Added WordPress uploads-based laboratory-result storage with activation-time creation of `zywienie-dla-zdrowia/badania/` and non-destructive retention.
* Added a WordPress laboratory-result catalog provider that passes explicitly supplied validated menu groups to the standalone pipeline.
* Added WordPress uploads path resolution, activation-time menu directory creation, and a catalog provider.
* Added a WordPress Transients API cache and menu catalog service with programmatic refresh and clear operations.
* Added a technical “Status publikacji” administration page with WordPress-site-date period counters, a current-menu notice, and protected manual catalog refresh.
* Added the public `[zfdz_jadlospisy]` shortcode for grouped current and upcoming menu links using the WordPress uploads base URL.
* Added the public `[zfdz_jadlospisy_archiwum]` shortcode for grouped archived menu links ordered from newest to oldest.
* Added the initial plugin bootstrap, project documentation, and development quality tooling.
