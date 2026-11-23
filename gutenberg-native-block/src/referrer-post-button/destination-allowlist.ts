/**
 * Editor-side mirror of includes/DestinationAllowlist.php.
 *
 * The server is authoritative: its render_block filter strips referrer
 * delivery from any button whose destination isn't allowlisted. This copy
 * exists only so the editor can warn the author before publishing, instead of
 * the button silently becoming a plain link on the live site.
 *
 * CALLOUT: two implementations of one rule can drift, so both run the same
 * cases from tests/fixtures/allowlist-cases.json (see
 * destination-allowlist.test.ts and tests/php/DestinationAllowlistTest.php).
 *
 * Patterns arrive already normalized by PHP (lowercase, no trailing dot,
 * malformed wildcards dropped), via window.referrerPostButtonSettings.
 */

/**
 * Normalized allowlist patterns the server passed to the editor, or an empty
 * array (unrestricted) when none were provided.
 */
export function editorAllowedHosts(): string[] {
	const hosts = window.referrerPostButtonSettings?.allowedHosts;

	return Array.isArray( hosts ) ? hosts : [];
}

/**
 * Host of an absolute http(s) URL, lowercase and without a trailing dot, or
 * null for anything else (relative, scheme-relative, other schemes, invalid).
 *
 * @param url Destination URL as entered in the editor.
 */
function destinationHost( url: string ): string | null {
	let parsed: URL;

	try {
		// No base URL: relative and scheme-relative input throws, which is
		// what we want; the server refuses those too.
		parsed = new URL( url );
	} catch {
		return null;
	}

	if ( parsed.protocol !== 'http:' && parsed.protocol !== 'https:' ) {
		return null;
	}

	// `hostname` excludes port and userinfo; URL already lowercases it.
	const host = parsed.hostname.replace( /\.+$/, '' );

	return host === '' ? null : host;
}

/**
 * Whether the destination may receive the referrer under these patterns.
 *
 * @param patterns Normalized patterns; empty means unrestricted.
 * @param url      Destination URL as entered in the editor.
 */
export function permitsDestination( patterns: string[], url: string ): boolean {
	if ( patterns.length === 0 ) {
		return true;
	}

	const host = destinationHost( url );
	if ( host === null ) {
		return false;
	}

	return patterns.some( ( pattern ) =>
		pattern.startsWith( '*.' )
			? // '.example.com' suffix: rejects 'notexample.com' and 'example.com'.
			  host.endsWith( pattern.slice( 1 ) )
			: host === pattern
	);
}
