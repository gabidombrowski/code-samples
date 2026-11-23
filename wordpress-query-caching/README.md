# Cached Event Listings

A WordPress plugin: an `Event` post type with a dated upcoming/past listing, built around object caching that holds up on high-traffic sites.

## Why this exists

Event listings are the classic "cheap page, expensive query" problem: every visit runs a meta-ordered `WP_Query` with a date comparison, the result changes only when an editor touches an event — and yet most implementations either don't cache it at all or cache it in a way that serves stale data after an edit.

I first built this pattern inside a client's theme; this repo is a from-scratch reimplementation as a standalone plugin, with the query and cache-key logic factored into pure classes so the parts most likely to harbor subtle bugs are covered by plain PHPUnit tests — no WordPress test scaffold required.

## How the caching works

It uses WordPress core's `last_changed` idiom — invalidation by generation, not by deletion:

- **Cache keys embed a generation value.** Any write to an event (status change, deletion, date edit) bumps one generation entry. Every listing key includes it, so a single write invalidates every direction/page/per-page combination at once. Superseded entries age out via TTL. Deleting individual keys can't do this reliably — the object cache API has no wildcard delete, and a listing has unbounded key combinations.
- **Keys embed the site-local date**, so day rollover self-invalidates: at midnight, yesterday's "upcoming" list becomes unreachable rather than wrong.
- **IDs are cached, not post objects.** Core already caches posts individually; the repository re-hydrates via `_prime_post_caches()` + `get_post()`, so listings never serve a stale copy of an edited post and cache memory isn't spent duplicating rows.
- **The "View past events" link is driven by a probe** — a one-row, IDs-only, `no_found_rows` query, cached the same way.

## Architecture

```
cached-event-listings.php   Bootstrap: autoload, hooks, cache invalidation wiring.
src/
  Direction.php             Backed enum; untrusted input validated by construction.
  QuerySpec.php             Pure WP_Query args builder (the query's correctness).
  CacheKey.php              Pure key composition (the cache's correctness).
  EventRepository.php       Runs queries, owns the caching strategy.
  EventPostType.php         CPT + date meta with a wp_checkdate sanitizer.
  ListingPage.php           Readonly result DTO.
  Shortcode.php             [event_listings] renderer; every value escaped.
tests/                      PHPUnit over the pure classes.
phpcs.xml.dist              Security-only WPCS ruleset (rationale inside).
```

Decisions worth noting:

- **Pure core, thin shell.** `QuerySpec` and `CacheKey` make no WordPress calls, so the two things that actually go wrong in listings — a subtly wrong query, a cache key that misses a dimension and serves the wrong page — are unit-tested directly (including "changing any key dimension must change the key").
- **Input validated by construction.** `Direction` is a backed enum; `Direction::from_input()` is the only way request data reaches a query, and anything outside the enum falls back to a default.
- **`meta_type => DATE`** so MySQL compares dates, not strings, and `wp_checkdate` in the meta sanitizer rejects impossible dates (2026-02-31) the DATE cast would silently fold.
- **The PHPCS ruleset is security-only, on purpose** — escaping, input validation, nonces, prepared SQL, i18n domains. Formatting is left to the editor, so every CI finding is one worth fixing. The one nonce exemption (read-only view parameters) is annotated inline with its reasoning.

## Usage

Activate, add Events (each with an **Event date**), and place `[event_listings]` on a page. `?direction=past` and the rendered links switch views; `[event_listings direction="past"]` sets the default.

## Development

```bash
composer install
composer test    # PHPUnit
composer lint    # PHPCS (security ruleset)
composer check   # both
```

No build step is required to run the plugin — a plain copy of the folder works via the PSR-4 fallback autoloader in the bootstrap.

## Requirements

- WordPress 6.5+
- PHP 8.1+
- A persistent object cache (Redis, Memcached) to get real benefit; without one, `wp_cache_*` still works per-request.
