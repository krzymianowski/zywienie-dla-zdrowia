# Repository Instructions for Agents

This file is the authoritative instruction set for agents working in this repository. Follow it for every change unless a more recent, explicit user instruction overrides it.

## Project identity and privacy boundary

- The project is the generic, public WordPress plugin **Żywienie dla Zdrowia**.
- Slug and text domain: `zywienie-dla-zdrowia`.
- Prefix global technical identifiers with `ZFDZ_` or `zfdz_` as appropriate. Avoid global functions when possible.
- Never add real facility names or abbreviations, domains, IP addresses, email addresses, usernames, deployment paths, credentials, API keys, SFTP configuration, patient or staff data, private documents, logos, or deployment screenshots.
- Apply this privacy boundary to code, comments, documentation, tests, fixtures, examples, configuration, CI, release notes, and commit messages.
- Prefer examples without organization names. When an example is necessary, make it obviously fictional and non-sensitive.

## Scope and architecture

- Keep changes minimal and tied to the active task. Prevent scope creep and unrelated refactoring.
- Do not create speculative directories, interfaces, abstractions, service containers, repositories, routers, or runtime autoloaders.
- Do not add Custom Post Types, custom database tables, REST endpoints, JavaScript frameworks, build pipelines, or other architecture until an approved task requires them.
- Do not introduce dependencies without a documented need. Avoid runtime dependencies unless they are essential and explicitly justified.
- Do not add placeholder tests or empty directories. Add test infrastructure with the first component that requires it.

## Code standards and language

- Follow WordPress Coding Standards and the configured PHPCS ruleset.
- Use English for technical names and developer comments.
- Write user-facing text primarily in Polish and make it translatable from the start with the `zywienie-dla-zdrowia` text domain.
- Use the `zfdz-` prefix for plugin CSS classes.
- Public-facing basic functionality must work without JavaScript.

## Security by default

- Validate input, then sanitize it for the intended type and context.
- Escape output as late as possible and for the correct output context.
- Enforce WordPress capabilities for privileged operations.
- Require and verify nonces for administrative forms and state-changing requests.
- Protect every filesystem operation against path traversal.
- Treat filenames and uploaded content as untrusted.
- Never execute content from the uploads directory.
- Never use `eval`.
- Never use dynamic `include` or `require` paths sourced from uploads.
- Do not store credentials in the repository.
- Do not add external runtime dependencies without clear justification.
- The plugin must not send telemetry.
- Do not make external requests without an explicit functional requirement and documented privacy behavior.

## Privacy by default

- Do not store patient data or survey responses.
- Do not install plugin-owned cookies.
- Do not send data to external services.
- The planned survey module is only an administrator-configured link. Never claim that an external form is anonymous.
- Collect and retain no data unless an approved requirement makes it necessary and its privacy impact is documented.

## Accessibility and frontend

- Use semantic HTML and keyboard-operable controls with visible focus.
- Do not convey information by color alone.
- Keep layouts responsive and CSS neutral: no global reset and no imposed `font-family`.
- Do not claim formal WCAG conformance without appropriate testing and evidence.

## Documentation and versions

- Update relevant documentation whenever behavior, configuration, requirements, security assumptions, or privacy assumptions change.
- Clearly distinguish implemented functionality from planned functionality.
- Do not automatically change the plugin version. Version changes require an explicit task.
- Do not describe unreleased functionality as available or stable.

## Git and quality controls

- Do not stage, commit, tag, push, publish releases, or modify remotes unless explicitly instructed.
- Preserve unrelated existing files and local changes.
- Run all quality checks available and relevant to the change, including Composer validation, PHP syntax checking, PHPCS, and `git diff --check` when configured.
- Report every check that was not run or did not pass, including the exact reason.
- Review public changes for accidentally included secrets and real-deployment information before finishing.
