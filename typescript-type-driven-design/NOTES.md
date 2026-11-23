# TypeScript type-driven design

Excerpts from [recipe-generator](https://github.com/gabidombrowski/recipe-generator), a Next.js 16 / React 19 / tRPC / Drizzle app (~270 tests, 4 CI workflows). Copied verbatim from `main`; original paths below.

- **`schemas.ts`** (`src/lib/schemas.ts`) — one Zod module as the single source of truth: the DB layer, every tRPC procedure, React components (via `z.infer`), the LLM tool schema, and the eval assertions all consume these definitions, so a shape can't drift between layers.
- **`constraints.ts`** (`src/lib/constraints.ts`) — discriminated-union constraint model with an explicit enforceable-vs-advisory split: constraints the scheduler can verify in code versus preferences the LLM is asked to honor.
- **`concurrency.ts`** (`src/lib/concurrency.ts`) — a small generic worker pool (`mapWithConcurrency<T, R>`) using a shared cursor, bounded concurrency without a dependency.
