## Dev environment tips
- If a change modifies application code, you MUST run `composer test` and ensure all tests pass.
- If a change affects only non-code files (e.g. documentation, Markdown, static content), running tests is optional.
- You MUST avoid code duplication. Before adding new code, search for existing implementations and extend or refactor them instead of creating duplicates.
