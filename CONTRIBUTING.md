# Contributing

Thank you for helping build Żywienie dla Zdrowia. The project is in an early development stage, so changes should remain small, focused, and easy to review.

## Before making a change

- Read and follow [AGENTS.md](AGENTS.md); it is the authoritative repository guidance.
- Keep the repository generic. Never include data, credentials, domains, paths, screenshots, or documents from a real deployment.
- Confirm that the change belongs to the current task and avoid unrelated refactoring.
- Explain and justify every new dependency. Runtime dependencies require especially strong justification.

## Changes and documentation

- Follow WordPress Coding Standards for PHP.
- Use English for technical identifiers and developer comments. User-facing text should primarily be Polish and ready for WordPress internationalization with the `zywienie-dla-zdrowia` text domain.
- Update documentation whenever behavior, configuration, security assumptions, privacy assumptions, or supported requirements change.
- Do not combine an unrelated cleanup or refactor with a functional change.
- Do not change the plugin version automatically or as part of an unrelated contribution.

## Quality checks

Run checks appropriate to the change. The current baseline is:

```bash
composer validate --strict
composer install
composer lint
git diff --check
```

If a relevant check cannot be run, state exactly which check was skipped and why. New behavior should receive suitable automated tests when a testable component is introduced; do not add placeholder test infrastructure.

## Security reports

Do not disclose vulnerability details in a public issue. Follow [SECURITY.md](SECURITY.md), and never include credentials or real-organization data in a report.
