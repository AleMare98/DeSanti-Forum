# Forum capabilities and acceptance rules

This is the behavior contract for the framework-free PHP/MySQL forum.

## Category chat

- Each category has one shared chat for authenticated students and administrators.
- Every message belongs to exactly one category and records author, body and
  creation time.
- The client loads bounded recent history for the active category and polls at a
  bounded interval. Results are deterministic and do not duplicate messages.
- The server rejects missing, empty, oversized or cross-category messages.
- Administrators may delete messages for moderation; students may not delete
  another user's messages.

## AI generation

- AI output is always a draft for the requesting user to review and edit.
- Generation never publishes a thread, comment or chat message automatically.
- Input length and request frequency are bounded by application policy.
- Empty, malformed, timed-out or unavailable responses fail safely without
  exposing provider details.
- Generated text is escaped or sanitized at display and persistence boundaries.

## Shared quality bar

- Visitors may read public content but cannot write, use chat or generate AI.
- All writes require authentication, authorization, CSRF protection and
  server-side validation.
- UI actions expose loading, success and failure states without leaking secrets.
