# Workspace boundaries

Agents may inspect the whole repository, but may modify only files explicitly
within the current task. Application code is out of scope for harness-only tasks.

Protected files (never edit):

- `/includes/ai_client.php`
- `/includes/ai_forum_generator.php`

Preserve unrelated user changes in the working tree.
