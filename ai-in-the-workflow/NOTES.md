# AI in the development workflow

Excerpt from [recipe-generator](https://github.com/gabidombrowski/recipe-generator), which generates recipes with Claude under constraints that are verified in code, not trusted from the model.

- **`tool-schema.ts`** (`src/server/llm/tool-schema.ts`) — Zod 4 → JSON Schema conversion for forced tool calls. The emitted schema is a model-facing contract, so it's snapshot-tested: an accidental schema change fails CI before it changes model behavior in production.

Worth seeing in the repo itself (too large to excerpt):

- **`evals/`** — a weekly LLM eval suite that scores generation quality and reports to Prometheus, so model or prompt drift is measured rather than noticed.
- **`.github/workflows/`** — lint, typecheck, unit, and Playwright e2e gates, plus the eval schedule.
- **`docs/honesty-notes.md`** — a written list of what the project's architecture *doesn't* justify at its current scale.
