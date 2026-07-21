# Roles and authorization

Authorization is enforced server-side for every write operation; hiding a UI
control is not an authorization check.

## Visitor

- May read public categories, threads and comments.
- May not write content, use category chat or invoke AI generation.

## Authenticated student

- May create a thread only in an existing category.
- May comment only in an existing thread.
- May read and post only in the chat belonging to the selected category.
- May not delete another user's content or access another category's chat by
  changing an identifier in a request.

## Administrator

- May create categories.
- May delete threads, comments and chat messages for moderation.
- Must still pass authentication, authorization and CSRF checks.

Every endpoint verifies the session, target existence and resource relationship
before changing data, then returns a safe user-facing error.
