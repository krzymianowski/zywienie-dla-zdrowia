=== Żywienie dla Zdrowia ===
Requires at least: 6.8
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Development-stage tooling for publishing information related to a “Żywienie dla zdrowia” section on medical-facility websites.

== Description ==

Żywienie dla Zdrowia is an open-source WordPress plugin under development. It is planned to support menus, laboratory test results, educational materials, and a configurable link to an external feedback form or survey.

The minimum supported environment is WordPress 6.8 and PHP 8.2. PHP 8.3 or later is recommended.

Version 0.1.0 contains a standalone validated menu catalog pipeline, a deterministic current/upcoming/archive period classifier, and their first WordPress integration. During activation, the plugin resolves the current uploads base directory through WordPress API and idempotently creates `zywienie-dla-zdrowia/jadlospisy/`. A WordPress catalog provider connects that directory to the standalone pipeline only when explicitly requested. A catalog service caches successful results for approximately five minutes. The technical “Status publikacji” administration page displays catalog counters and issues, classifies periods against the current WordPress site date, reports whether a menu period applies today, and provides a capability- and nonce-protected manual refresh. Classification is calculated after catalog cache access and is not stored in the transient. Deactivation does not delete documents. Public user-facing features are not implemented, and this version is not presented as a stable release.

The catalog keeps only scanner-approved documents that pass limited PDF candidate validation and combines scanner and validation issues. These checks do not provide malware scanning, PDF sanitization, full PDF structure validation, or a guarantee of document safety.

The plugin is a technical publishing tool, not legal advice. It does not guarantee legal compliance. Site administrators remain responsible for the content, completeness, and accuracy of published information.

== Installation ==

This development version has a technical administration status page but no public-facing document display. For development, place the repository in the WordPress plugins directory and install development dependencies with Composer. Do not use it as a substitute for a production-ready release.

== Frequently Asked Questions ==

= Does this version display documents or provide shortcodes? =

No. These features are planned for v1.0 and are not implemented in version 0.1.0.

= Does the plugin store survey responses or patient data? =

No. The v1.0 design plans only a configurable external survey link and does not plan to store survey responses or patient data.

= Does the plugin guarantee that an external survey is anonymous? =

No. The privacy properties of an external form are outside the plugin's control and must be assessed by the site administrator.

== Changelog ==

= Unreleased =

* Added a standalone menu filename parser, document model, parse result, and unit tests.
* Added a standalone menu directory scanner with issue reporting, deterministic sorting, exact-period grouping, and filesystem tests.
* Added a standalone PDF candidate validator, validation result, optional MIME checks, bounded header and EOF checks, and unit tests.
* Added a standalone validated menu catalog builder with filtered period groups and combined deterministic issues.
* Added a standalone menu period classifier for current, upcoming, and archived groups with unit tests.
* Added WordPress uploads path resolution, activation-time menu directory creation, and a catalog provider.
* Added a WordPress Transients API cache and menu catalog service with programmatic refresh and clear operations.
* Added a technical “Status publikacji” administration page with WordPress-site-date period counters, a current-menu notice, and protected manual catalog refresh.
* Added the initial plugin bootstrap, project documentation, and development quality tooling.
