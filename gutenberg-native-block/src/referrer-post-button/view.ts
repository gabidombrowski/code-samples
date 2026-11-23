/**
 * Front-end behavior for the Referrer POST Button block.
 *
 * Step 5 of the click flow described in "ABOUT THIS SAMPLE" in
 * referrer-post-button.php.
 *
 * CALLOUT: this file stays deliberately thin (event wiring only). The logic
 * worth testing lives in referrer-form.ts, which has no globals.
 *
 * Loaded (via block.json `viewScript`) only on pages that contain the block.
 * Listens at the document level so a single handler covers every instance,
 * including any injected after initial load.
 */

/**
 * Internal dependencies
 */
import { submitReferrerForm } from './referrer-form';

document.addEventListener( 'click', ( event ) => {
	const target = event.target as Element | null;
	const link = target?.closest< HTMLAnchorElement >(
		'a[data-referrer-post-endpoint]'
	);
	if ( ! link ) {
		return;
	}

	const endpoint = link.getAttribute( 'data-referrer-post-endpoint' );
	if ( ! endpoint ) {
		return;
	}

	// CALLOUT: the default is only cancelled once a usable endpoint is
	// confirmed, so any failure above leaves a working plain link.
	event.preventDefault();
	submitReferrerForm(
		document,
		endpoint,
		window.location.href,
		link.getAttribute( 'data-referrer-post-newtab' ) === '1'
	);
} );
