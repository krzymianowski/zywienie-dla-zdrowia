# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project intends to use [Semantic Versioning](https://semver.org/) when releases begin.

## [Unreleased]

### Added

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
- Passive WordPress plugin bootstrap for development version `0.1.0`.
- Initial product, security, contribution, and repository guidance.
- Composer, WordPress Coding Standards, and CI quality tooling.
