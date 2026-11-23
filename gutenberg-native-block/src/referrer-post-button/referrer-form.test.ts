/**
 * Internal dependencies
 */
import { buildReferrerForm, submitReferrerForm } from './referrer-form';

const DESTINATION = 'https://destination.example/landing';
const REFERRER = 'https://origin.example/pricing?utm_source=partner#plans';

describe( 'buildReferrerForm', () => {
	it( 'builds a hidden POST form aimed at the destination', () => {
		const form = buildReferrerForm(
			document,
			DESTINATION,
			REFERRER,
			false
		);

		expect( form.method.toUpperCase() ).toBe( 'POST' );
		expect( form.action ).toBe( DESTINATION );
		expect( form.style.display ).toBe( 'none' );
		expect( form.target ).toBe( '' );
	} );

	it( 'carries the referrer as a base64-encoded hidden field', () => {
		const form = buildReferrerForm(
			document,
			DESTINATION,
			REFERRER,
			false
		);
		const input = form.querySelector< HTMLInputElement >(
			'input[name="referrer"]'
		);

		expect( input ).not.toBeNull();
		expect( input?.type ).toBe( 'hidden' );
		expect( atob( input!.value ) ).toBe( REFERRER );
	} );

	it( 'targets a new tab only when asked to', () => {
		const newTabForm = buildReferrerForm(
			document,
			DESTINATION,
			REFERRER,
			true
		);
		expect( newTabForm.target ).toBe( '_blank' );
	} );
} );

describe( 'submitReferrerForm', () => {
	it( 'appends the form to the document and submits it', () => {
		const submit = jest
			.spyOn( HTMLFormElement.prototype, 'submit' )
			.mockImplementation( () => undefined );

		submitReferrerForm( document, DESTINATION, REFERRER, false );

		const form = document.querySelector< HTMLFormElement >(
			`form[action="${ DESTINATION }"]`
		);
		expect( form ).not.toBeNull();
		expect( submit ).toHaveBeenCalledTimes( 1 );

		submit.mockRestore();
		form?.remove();
	} );
} );
