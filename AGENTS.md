# Agent instructions

## Before working

Read this file, then `Important/sandbox.md` and the skill relevant to the task:
`Important/frontend/SKILL.md`, `Important/backend/SKILL.md`,
`Important/database/SKILL.md` or `Important/roles/SKILL.md`. For feature behavior,
also read `Important/functional.md`.

## Project constraints

- Use only HTML, CSS, vanilla JavaScript, PHP and MySQL; no framework.
- Keep changes maintainable and readable by junior developers.
- Enforce authentication, authorization, prepared statements, output escaping
  and CSRF protection server-side for every mutation.
- Follow the role and category-chat rules in `Important/roles/SKILL.md` and
  `Important/functional.md`.
- AI results are drafts only and must be validated, bounded and safely rendered.

## Change protocol

- Explain intent before writing code and ask permission when the applicable
  skill requires it.
- Never edit `/includes/ai_client.php` or `/includes/ai_forum_generator.php`.
- Preserve unrelated user work and verify affected behavior afterward.
