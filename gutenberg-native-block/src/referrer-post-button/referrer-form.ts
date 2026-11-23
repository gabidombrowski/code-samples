/**
 * Build a hidden POST form that delivers the referring page URL to a
 * destination in the request body.
 *
 * Step 6 of the click flow described in "ABOUT THIS SAMPLE" in
 * referrer-post-button.php. Covered by referrer-form.test.ts.
 *
 * Why not the Referer header? Safari's Intelligent Tracking Prevention trims
 * the cross-site Referer to its origin, so a destination that needs the full
 * referring URL (for example, to set a first-party attribution cookie) never
 * sees it. Submitting the URL as a form field survives that policy because
 * it travels in the body of a navigation the user initiated.
 *
 * The value is base64-encoded. `btoa()` throws on characters outside
 * Latin-1, but `window.location.href` is always percent-encoded ASCII, so
 * it is safe for this input.
 *
 * CALLOUT: the document is injected rather than read from the global, so
 * the form contract is tested with plain Jest assertions; no browser
 * automation needed.
 *
 * @param doc            Document to create the form in (a parameter so tests
 *                       can supply their own).
 * @param destinationUrl Where the form POSTs to.
 * @param referrer       Referring page URL to deliver.
 * @param opensInNewTab  Whether the navigation should target a new tab.
 *
 * @return The detached, hidden form element.
 */
export function buildReferrerForm(
	doc: Document,
	destinationUrl: string,
	referrer: string,
	opensInNewTab: boolean
): HTMLFormElement {
	const form = doc.createElement( 'form' );
	form.method = 'POST';
	form.action = destinationUrl;
	if ( opensInNewTab ) {
		form.target = '_blank';
	}
	form.style.display = 'none';

	const input = doc.createElement( 'input' );
	input.type = 'hidden';
	input.name = 'referrer';
	input.value = doc.defaultView
		? doc.defaultView.btoa( referrer )
		: btoa( referrer );
	form.appendChild( input );

	return form;
}

/**
 * Build the referrer-carrying form, attach it, and submit it.
 *
 * @param doc            Document to submit within.
 * @param destinationUrl Where the form POSTs to.
 * @param referrer       Referring page URL to deliver.
 * @param opensInNewTab  Whether the navigation should target a new tab.
 */
export function submitReferrerForm(
	doc: Document,
	destinationUrl: string,
	referrer: string,
	opensInNewTab: boolean
): void {
	const form = buildReferrerForm(
		doc,
		destinationUrl,
		referrer,
		opensInNewTab
	);
	doc.body.appendChild( form );
	form.submit();
}
