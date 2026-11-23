# Referrer POST Button

A native Gutenberg block: a button that can deliver the referring page's URL to its destination **in the POST body** instead of relying on the `Referer` header.

## Why this exists

Safari's Intelligent Tracking Prevention trims the cross-site `Referer` header to its origin. A destination that needs the *full* referring URL — for example, a partner landing flow that sets a first-party attribution cookie from it — never receives it, and there is no header-side fix a site owner controls.

This block solves it at the interaction layer: when **Deliver referrer via POST** is enabled, a small view script intercepts the click and submits a hidden form to the destination, carrying `window.location.href` as a base64-encoded `referrer` field. The value travels in the body of a user-initiated navigation, which tracking prevention does not strip.

I originally built this pattern as an ACF block inside a client's block-theme child theme; this repo is a from-scratch reimplementation as a native block with `block.json`, TypeScript, a dynamic PHP render, and unit tests.

**Reviewing this as a code sample?** Start with the `ABOUT THIS SAMPLE` comment at the top of [`referrer-post-button.php`](referrer-post-button.php). It walks a click through every file, and `CALLOUT:` comments throughout the code mark the decisions worth a look.

## Restricting destinations

The full referring URL can carry query-string data (campaign IDs, preview links, tokens in emailed URLs). A site can limit which hosts receive it:

```php
add_filter( 'referrer_post_button_allowed_hosts', fn() => array(
	'partner.example.com', // exactly this host
	'*.example.org',       // any subdomain of example.org (not example.org itself)
) );
```

With no hosts supplied, delivery is unrestricted. When a button's destination isn't allowed, a `render_block` filter strips the referrer-delivery attributes from its rendered markup, so it degrades to a plain link that still works. The editor shows a warning on any such button, so authors find out before publishing.

## Architecture

```
referrer-post-button.php          Plugin bootstrap: registers the built block,
                                  enforces the destination allowlist.
includes/
  DestinationAllowlist.php        Pure host-matching rules for the allowlist.
tests/
  fixtures/allowlist-cases.json   Allowlist cases shared by PHPUnit and Jest.
  php/DestinationAllowlistTest.php
                                  PHPUnit tests for the allowlist rules.
src/referrer-post-button/
  block.json                      Single source of block config (apiVersion 3).
  index.ts                        Registers the block; save() is null (dynamic).
  edit.tsx                        Editor UI: RichText label + InspectorControls,
                                  plus the non-allowlisted destination warning.
  destination-allowlist.ts        Editor mirror of the allowlist rules (advisory).
  destination-allowlist.test.ts   Jest tests, run against the shared cases.
  render.php                      Server render; escapes every attribute.
  view.ts                         Front-end click interceptor (viewScript).
  referrer-form.ts                Pure form-building logic, unit-testable.
  referrer-form.test.ts           Jest tests for the POST form contract.
  style.css                       Minimal; visual treatment defers to the theme.
```

Design decisions worth noting:

- **Dynamic render, not static save.** The markup contract (data attributes the view script reads) lives in one place, `render.php`, so it can evolve without block deprecations.
- **Block supports instead of hardcoded styles.** Colors, typography, padding, and border radius come from `supports` in `block.json`; the button inherits any theme's `wp-element-button` styling. (The ACF original hardcoded inline border radii — this is the fix.)
- **`viewScript` in block.json.** The interceptor is enqueued only on pages that actually contain the block, replacing the original's unconditional theme-wide enqueue.
- **The form builder is a pure function.** `buildReferrerForm(document, url, referrer, newTab)` takes its document as a parameter and returns the form, so the encoding and form contract are plain Jest assertions — no browser automation needed to cover the part that matters.
- **`btoa` is safe here.** It throws on non-Latin-1 input, but `window.location.href` is always percent-encoded ASCII; the comment in `referrer-form.ts` documents the reasoning.
- **Delegated listener at the document level.** One handler covers every block instance, including any injected after load.
- **Allowlist enforced on rendered output.** The `render_block_digitallotus/referrer-post-button` filter reads the markup with `WP_HTML_Tag_Processor`, not a regex and not the block attributes, so it checks exactly what `view.ts` will act on. It runs at a late priority to have the last word.
- **One rule, two runtimes.** The editor warning needs the same host matching as the server, so `destination-allowlist.ts` mirrors `DestinationAllowlist.php`. PHP passes its already-normalized patterns to the editor through an inline script, and both test suites run the same cases from `tests/fixtures/allowlist-cases.json`, so a divergence fails a test. The server stays authoritative; the editor only warns.
- **Allowlist rules are pure PHP.** `DestinationAllowlist` makes no WordPress calls, so PHPUnit covers its edge cases directly: look-alike domains, userinfo tricks, scheme-relative URLs, and malformed wildcards that must never widen into "allow everything".

## Development

```bash
npm install
npm run build       # production build into build/
npm start           # watch mode
npm test            # Jest unit tests
composer install
composer test       # PHPUnit tests
npm run typecheck   # tsc --noEmit
npm run lint:js
npm run plugin-zip  # distributable zip
```

To try it in a WordPress site, symlink or copy the plugin folder into `wp-content/plugins/` after building, then activate **Referrer POST Button**.

## Requirements

- WordPress 6.5+
- PHP 8.1+
