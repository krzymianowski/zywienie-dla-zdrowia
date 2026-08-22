# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project intends to use [Semantic Versioning](https://semver.org/) when releases begin.

## [Unreleased]

### Added

- Public parameter-free `[zywienie_dla_zdrowia]` aggregate shortcode composing the existing current/upcoming menu and laboratory-result renderers in that order.
- Public parameter-free `[zfdz_badania]` shortcode rendering only the current technical laboratory-result candidate, with state-specific safe messages and an encoded WordPress uploads URL.
- WordPress laboratory-result uploads `baseurl` resolution and a standalone candidate-only public URL resolver using the parser-approved original filename as one percent-encoded path segment.
- WordPress laboratory-result public-presentation result, deterministic resolver, and thin service preserving distinct unavailable, no-result, blocked-unmatched, and candidate states.
- Standalone laboratory-result public presentation policy with immutable no-result, candidate, and blocked-unmatched decisions, without fallback or public output.
- Latest laboratory-result details on the WordPress “Status publikacji” page, derived from the standalone selector without fallback or a separate cache.
- Standalone deterministic latest laboratory-result selector with immutable empty, matched, and unmatched outcomes and no fallback from a latest unmatched result to an older match.
- Laboratory-result technical status on the existing WordPress “Status publikacji” page, including validated, matched, unmatched, and issue counts with safe details.
- Coordinated protected administration refresh for menu and laboratory-result catalogs through the existing POST/nonce/PRG endpoint.
- Coordinated WordPress laboratory-result catalog service with distinct success, menu-unavailable, and laboratory-unavailable result states.
- Fingerprint-aware laboratory-result Transients API cache using the fixed `zfdz_lab_result_catalog_v1` key and a 300-second TTL.
- Order-independent SHA-256 menu-period fingerprinting based only on menu start/end dates, with coordinated refresh and lab-only cache clearing.
- WordPress uploads-based laboratory-result storage with activation-time, idempotent creation of the managed `zywienie-dla-zdrowia/badania/` directory and non-destructive retention behavior.
- WordPress laboratory-result catalog provider connecting explicitly supplied validated menu-period groups and the managed `badania/` directory to the standalone laboratory-result pipeline.
- Standalone, non-recursive laboratory-result directory scanner with immutable scan issues/results and deterministic entry handling.
- Standalone laboratory-result catalog pipeline reusing the bounded PDF candidate validator and exact-period matcher.
- Immutable laboratory-result catalog result containing validated candidates, matched or unmatched associations, and combined deterministic issues without source paths.
- Standalone laboratory-result filename parser with immutable document and machine-readable parse-result models for the `YYYY-MM-DD_YYYY-MM-DD_YYYY-MM-DD_name.pdf` contract.
- Deterministic exact-period matcher producing immutable matched or unmatched associations between laboratory-result documents and existing menu-period groups.
- Public parameter-free `[zfdz_jadlospisy_archiwum]` shortcode rendering grouped archived menu periods from newest to oldest through the existing validated catalog pipeline.
- Public parameter-free `[zfdz_jadlospisy]` shortcode rendering grouped current and upcoming validated menu documents without exposing catalog issues or filesystem paths.
- WordPress uploads `baseurl` resolution for encoded public menu document links and localized period labels.
- Standalone immutable menu-period classification result and deterministic classifier for current, upcoming, and archived groups.
- WordPress-site-date period counters and a separate current-menu notice on the technical “Status publikacji” administration page.
- Native WordPress “Status publikacji” administration page with menu catalog counters and safely mapped issue descriptions.
- Capability- and nonce-protected manual menu catalog refresh using an admin-post POST/Redirect/GET flow.
- WordPress Transients API cache for successful menu catalogs with a fixed versioned key and an approximately five-minute lifetime.
- WordPress menu catalog service with cache-hit reads and explicit programmatic refresh and clear operations.
- WordPress uploads-based menu storage with activation-time, idempotent directory creation and non-destructive retention behavior.
- WordPress menu catalog provider connecting the managed `jadlospisy` directory to the standalone validated catalog pipeline.
- Standalone validated menu catalog builder that orchestrates scanning and limited PDF candidate validation without coupling the underlying components.
- Immutable catalog result with filtered documents and period groups plus deterministically combined scanner and validation issues.
- Standalone PDF candidate validator with optional MIME detection and bounded header and EOF checks.
- Immutable PDF validation result and filesystem tests covering candidate validation, MIME mismatch, symlinks, bounded reads, and path-disclosure boundaries.
- Standalone, non-recursive menu directory scanner with deterministic sorting and exact-period grouping.
- Immutable scan result, scan issue, and menu period group models with machine-readable directory and entry errors.
- Filesystem test coverage for directory failures, mixed entries, non-recursive scanning, symlink rejection, sorting, grouping, and path disclosure boundaries.
- Standalone menu filename parser with calendar-date, range, extension, name, and unsafe-path validation.
- Immutable menu document model and machine-readable parse result.
- PHPUnit 11 unit-test infrastructure and parser test coverage without WordPress.
- WordPress plugin bootstrap for development version `0.1.0`.
- Initial product, security, contribution, and repository guidance.
- Composer, WordPress Coding Standards, and CI quality tooling.
