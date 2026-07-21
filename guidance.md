Follow these guidelines:
1. The folder named "Important" in this project must never be modified
2. Only read the files inside "Important" when told to

AI generation setup (Admin panel):
1. Ensure your DB schema includes the latest `database.sql` updates (`source`, `ai_prompt_hash`, and `ai_generation_runs`).
2. Configure provider and token in runtime environment before starting PHP:
   - For OpenAI:
     - `AI_PROVIDER=openai`
     - `OPENAI_API_KEY=your_openai_key`
     - Optional: `OPENAI_MODEL=gpt-4.1-mini`
   - For GitHub Models (PAT):
     - `AI_PROVIDER=github`
     - `GITHUB_TOKEN=your_github_pat`
     - Optional: `GITHUB_MODEL=openai/gpt-4.1-mini`
3. On MAMP/Apache, set secrets as server environment variables (`SetEnv` or equivalent), never hardcode them in repository files.
4. Login as admin and open `?page=admin`.
5. Use the "Generate Forum with AI" form to create a draft, review and edit every category, thread and comment, then choose "Pubblica bozza" to publish the complete draft.
6. Current safeguards:
   - Admin-only endpoint
   - CSRF validation
   - Server-side range checks
   - Short per-session rate limit
   - Transactional DB writes during publication (all-or-nothing)
