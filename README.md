# Code Samples — Gabi Dombrowski

Six folders, one concept each. The two WordPress plugins are complete and runnable; the others are curated excerpts from production projects, each with a `NOTES.md` giving the source and what to look at.

| Folder | Concept | Source |
| --- | --- | --- |
| [`gutenberg-native-block/`](gutenberg-native-block/) | A native Gutenberg block plugin: `block.json`, TypeScript editor UI, dynamic PHP render, a front-end view script, and one rule (a destination allowlist) enforced in PHP and mirrored in the editor, with shared test fixtures keeping the two in sync. Jest + PHPUnit. | Complete plugin, this repo |
| [`wordpress-query-caching/`](wordpress-query-caching/) | WordPress PHP under load: a date-ordered `WP_Query` behind core's `last_changed` object-caching idiom — generation-based invalidation, self-invalidating day rollover, IDs cached instead of post objects. Pure query/cache-key classes under PHPUnit. | Complete plugin, this repo |
| [`typescript-type-driven-design/`](typescript-type-driven-design/) | Zod schemas as a single source of truth across DB, API, UI, and an LLM contract; discriminated-union constraint modeling; a small generic concurrency utility. | [recipe-generator](https://github.com/gabidombrowski/recipe-generator) |
| [`api-and-streaming/`](api-and-streaming/) | tRPC middleware with privacy-conscious error logging; an app-wide mutation error boundary; an SSE parser written as a pure function so its edge case is a unit test. | [recipe-generator](https://github.com/gabidombrowski/recipe-generator) |
| [`ai-in-the-workflow/`](ai-in-the-workflow/) | A model-facing tool schema treated as a versioned contract — snapshot-tested Zod→JSON Schema — from an app with a weekly LLM eval suite in CI. | [recipe-generator](https://github.com/gabidombrowski/recipe-generator) |
| [`accessible-react-ui/`](accessible-react-ui/) | Accessibility as engineering, not checklist: focus management, keyboard behavior, ARIA wiring, and an app-wide live-region channel, from two shipped apps. | [digitallotusdev.com](https://digitallotusdev.com) (live) + recipe-generator |

## Running the plugins

Each plugin folder has its own README with build/test commands (`npm`/`composer`). Both are standalone WordPress plugins: build, then copy into `wp-content/plugins/` and activate.

## Context

The WordPress patterns here originated in client work (an agency block-theme build and an events listing); both plugins are from-scratch reimplementations with no client code or identifiers. The TypeScript excerpts are taken verbatim from [recipe-generator](https://github.com/gabidombrowski/recipe-generator), a public production app — the excerpts are for convenience, and the repo shows them in context with their full test suite and CI.
