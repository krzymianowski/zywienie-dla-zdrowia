=== Żywienie dla Zdrowia ===
Requires at least: 6.8
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Development-stage tooling for publishing information related to a “Żywienie dla zdrowia” section on medical-facility websites.

== Description ==

Żywienie dla Zdrowia is an open-source WordPress plugin under development. It is planned to support menus, laboratory test results, educational materials, and a configurable link to an external feedback form or survey.

The minimum supported environment is WordPress 6.8 and PHP 8.2. PHP 8.3 or later is recommended.

Version 0.1.0 contains a passive plugin bootstrap, a standalone menu filename parser, a non-recursive directory scanner with deterministic sorting and grouping, related value objects, and development tooling. The scanner identifies candidates from entry types and filenames only; it does not validate MIME or PDF content. WordPress integration and user-facing features are not implemented, and this version is not presented as a stable release.

The plugin is a technical publishing tool, not legal advice. It does not guarantee legal compliance. Site administrators remain responsible for the content, completeness, and accuracy of published information.

== Installation ==

This development version has no user-facing functionality. For development, place the repository in the WordPress plugins directory and install development dependencies with Composer. Do not use it as a substitute for a production-ready release.

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
* Added the initial passive plugin bootstrap, project documentation, and development quality tooling.
