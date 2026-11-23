# API design and streaming

Excerpts from [recipe-generator](https://github.com/gabidombrowski/recipe-generator). Copied verbatim from `main`; original paths below.

- **`sse.ts`** + **`sse.test.ts`** (`src/lib/sse.ts`) — a server-sent-events encoder/parser written as a pure `(buffer, chunk)` function *specifically* so the split-frame bug — a frame delimiter arriving across two network chunks — is a plain unit test instead of a flaky integration repro.
- **`trpc-init.ts`** (`src/server/trpc/init.ts`) — tRPC context and middleware: request timing, a `protectedProcedure` guard kept as deliberate defense-in-depth behind the route middleware, and a Zod error formatter that logs field *paths* only — never submitted values.
- **`trpc-react.tsx`** (`src/trpc/react.tsx`) — the client provider, wiring tRPC through TanStack Query with an app-wide `MutationCache.onError` so a failed write can never silently look like a success. The comment documents the incident that motivated it.
