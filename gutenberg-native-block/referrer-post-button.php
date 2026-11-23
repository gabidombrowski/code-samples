<?php
/**
 * Plugin Name:       Referrer POST Button
 * Description:       A button block that delivers the referring page URL in the POST body — for destinations the Referer header no longer reaches.
 * Version:           1.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Gabi Dombrowski
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       referrer-post-button
 *
 * @package ReferrerPostButton
 */

/*
 * ABOUT THIS SAMPLE
 * =================
 *
 * What it is
 *   A WordPress plugin that adds one native Gutenberg block: a button that can
 *   hand the destination the full URL of the page the visitor clicked from.
 *   It covers PHP in a plugin, a block registered from block.json, and
 *   front-end JavaScript (TypeScript, built with @wordpress/scripts).
 *
 * Where it came from
 *   I first built this as an ACF block inside a client's block-theme child
 *   theme. This is a from-scratch reimplementation as a standalone plugin:
 *   native block, dynamic PHP render, typed JS, and unit tests in both
 *   languages. No client code or identifiers are included.
 *
 * The problem
 *   A partner landing flow needed the full referring URL to set a first-party
 *   attribution cookie. Safari's Intelligent Tracking Prevention trims the
 *   cross-site Referer header to the origin only (https://site.com/, not the
 *   page), and no header setting on the site can change that.
 *
 * What it does
 *   With "Deliver referrer via POST" on, a click is intercepted and the
 *   browser submits a hidden form to the destination with the current page
 *   URL in a base64 'referrer' field. It's a normal user-initiated navigation,
 *   so tracking prevention leaves the request body alone. With the toggle
 *   off, it's an ordinary themed button link.
 *
 * How a click flows through the files
 *   1. block.json            Declares attributes, supports, and every asset.
 *   2. src/.../edit.tsx      Editor UI: label, URL, two toggles, and a
 *                            warning when the destination isn't allowlisted.
 *   3. src/.../render.php    Server render. Outputs an <a> and, when
 *                            enabled, data-referrer-post-* attributes.
 *   4. this file             render_block filter strips those attributes
 *                            when the destination isn't allowlisted.
 *   5. src/.../view.ts       Front-end listener. Sees the attributes,
 *                            cancels the link, calls referrer-form.ts.
 *   6. referrer-form.ts      Builds and submits the hidden POST form.
 *
 * Callouts worth a look
 *   - Dynamic block (save returns null). The markup contract between PHP and
 *     JS lives in one place, render.php, so it can change without block
 *     deprecations or invalid-block warnings in saved posts.
 *   - block.json is the single source of config. PHP registration is one
 *     line, and viewScript means the JS loads only on pages with the block.
 *   - Escaping: render.php escapes each attribute value on output;
 *     wrapper attributes go through get_block_wrapper_attributes().
 *   - Destination allowlist (below): uses WP_HTML_Tag_Processor on the
 *     rendered HTML rather than regex or trusting block attributes.
 *   - One rule, two runtimes: the editor warning re-implements the host
 *     matching in TypeScript, and PHPUnit and Jest both run the shared
 *     cases in tests/fixtures/allowlist-cases.json, so drift between them
 *     shows up as a failing test.
 *   - Testable seams: host matching (includes/DestinationAllowlist.php) and
 *     form building (referrer-form.ts) have no WordPress or browser
 *     dependencies, so PHPUnit and Jest cover them directly.
 *
 * Trade-offs and known limits
 *   - Visitors without JavaScript get a plain link: they still reach the
 *     destination, just without the referrer.
 *   - The editor warning is advisory. The server filter is what actually
 *     withholds the referrer, so a hand-edited post or a different editor
 *     still can't bypass the allowlist.
 *   - The destination must accept a POST and read the 'referrer' field.
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/DestinationAllowlist.php';

use DigitalLotus\ReferrerPostButton\DestinationAllowlist;

/**
 * Register the block from its build output.
 *
 * All block configuration lives in block.json; this call wires up the
 * editor script, front-end view script, style, and render callback.
 */
function referrer_post_button_init(): void {
	register_block_type( __DIR__ . '/build/referrer-post-button' );
}
add_action( 'init', 'referrer_post_button_init' );

/**
 * The site's destination allowlist, shared by the server check and the editor.
 */
function referrer_post_button_allowlist(): DestinationAllowlist {
	/**
	 * Hosts allowed to receive the referring page URL in the POST body.
	 *
	 * Exact hostnames ('partner.example.com') or subdomain wildcards
	 * ('*.example.com'). An empty array, the default, leaves delivery
	 * unrestricted.
	 *
	 * @param string[] $hosts Allowed hostnames and wildcard patterns.
	 */
	$hosts = apply_filters( 'referrer_post_button_allowed_hosts', array() );

	return DestinationAllowlist::from_patterns( is_array( $hosts ) ? $hosts : array() );
}

/**
 * Strip referrer delivery from buttons whose destination isn't allowlisted.
 *
 * The full referring URL can carry query-string data (campaign IDs, preview
 * links, tokens in emailed URLs), so a site can limit which hosts receive it:
 *
 *     add_filter( 'referrer_post_button_allowed_hosts', fn() => array(
 *         'partner.example.com',
 *         '*.example.org',
 *     ) );
 *
 * CALLOUT: enforcement runs on the rendered markup, not the block attributes,
 * so it checks exactly what view.ts will read, whichever code produced it. A
 * blocked button keeps its href and still works as a plain link; only the
 * POSTed referrer is withheld.
 *
 * @param string $block_content Rendered block HTML.
 * @return string
 */
function referrer_post_button_enforce_allowlist( string $block_content ): string {
	if ( '' === $block_content ) {
		return $block_content;
	}

	$allowlist = referrer_post_button_allowlist();

	if ( ! $allowlist->is_restricted() ) {
		return $block_content;
	}

	// CALLOUT: the HTML API parses tags properly, so attribute order, quoting
	// and entity encoding can't trip it up the way a regex could.
	$processor = new WP_HTML_Tag_Processor( $block_content );

	while ( $processor->next_tag( 'a' ) ) {
		// get_attribute() decodes entities, so esc_url()'s &#038; reads as &.
		$endpoint = $processor->get_attribute( 'data-referrer-post-endpoint' );

		if ( null === $endpoint || ( is_string( $endpoint ) && $allowlist->permits( $endpoint ) ) ) {
			continue;
		}

		$processor->remove_attribute( 'data-referrer-post-endpoint' );
		$processor->remove_attribute( 'data-referrer-post-newtab' );
	}

	return $processor->get_updated_html();
}
// Late priority: have the last word over other filters on this block's output.
add_filter( 'render_block_digitallotus/referrer-post-button', 'referrer_post_button_enforce_allowlist', 999 );

/**
 * Hand the allowlist to the editor so it can warn about blocked destinations.
 *
 * Printed before the block's editor script, which reads it in
 * destination-allowlist.ts. The patterns are already normalized here, so the
 * editor applies exactly the rules the server enforces.
 */
function referrer_post_button_editor_settings(): void {
	$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'digitallotus/referrer-post-button' );
	$handle     = $block_type?->editor_script_handles[0] ?? null;

	if ( null === $handle ) {
		return;
	}

	$settings = array( 'allowedHosts' => referrer_post_button_allowlist()->patterns() );

	wp_add_inline_script(
		$handle,
		'window.referrerPostButtonSettings = ' . wp_json_encode( $settings ) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'referrer_post_button_editor_settings' );
