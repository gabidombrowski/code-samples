/**
 * Internal dependencies
 */
import {
	editorAllowedHosts,
	permitsDestination,
} from './destination-allowlist';
import cases from '../../tests/fixtures/allowlist-cases.json';

// The same cases PHPUnit runs against includes/DestinationAllowlist.php.
describe( 'permitsDestination (shared cases)', () => {
	it.each( Object.entries( cases.permitted ) )(
		'permits %s',
		( _label, url ) => {
			expect( permitsDestination( cases.patterns, url ) ).toBe( true );
		}
	);

	it.each( Object.entries( cases.refused ) )(
		'refuses %s',
		( _label, url ) => {
			expect( permitsDestination( cases.patterns, url ) ).toBe( false );
		}
	);
} );

describe( 'permitsDestination', () => {
	it( 'treats an empty allowlist as unrestricted', () => {
		expect(
			permitsDestination( [], 'https://anywhere.example.net/' )
		).toBe( true );
		expect( permitsDestination( [], 'not a url' ) ).toBe( true );
	} );
} );

describe( 'editorAllowedHosts', () => {
	afterEach( () => {
		delete window.referrerPostButtonSettings;
	} );

	it( 'returns the hosts the server provided', () => {
		window.referrerPostButtonSettings = {
			allowedHosts: [ 'partner.example.com' ],
		};

		expect( editorAllowedHosts() ).toEqual( [ 'partner.example.com' ] );
	} );

	it( 'falls back to unrestricted when settings are missing', () => {
		expect( editorAllowedHosts() ).toEqual( [] );
	} );
} );
