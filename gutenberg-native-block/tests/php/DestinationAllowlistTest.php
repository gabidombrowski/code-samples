<?php
declare( strict_types=1 );

namespace DigitalLotus\ReferrerPostButton\Tests;

use DigitalLotus\ReferrerPostButton\DestinationAllowlist;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DestinationAllowlistTest extends TestCase {

	/**
	 * Cases shared with the editor's TypeScript implementation.
	 *
	 * @return array{patterns: string[], permitted: array<string, string>, refused: array<string, string>}
	 */
	private static function shared_cases(): array {
		return json_decode(
			(string) file_get_contents( dirname( __DIR__ ) . '/fixtures/allowlist-cases.json' ),
			true,
			flags: JSON_THROW_ON_ERROR
		);
	}

	private static function allowlist(): DestinationAllowlist {
		return DestinationAllowlist::from_patterns( self::shared_cases()['patterns'] );
	}

	public function test_empty_allowlist_is_unrestricted(): void {
		$allowlist = DestinationAllowlist::from_patterns( array() );

		$this->assertFalse( $allowlist->is_restricted() );
		$this->assertTrue( $allowlist->permits( 'https://anywhere.example.net/landing' ) );
	}

	public static function permitted_urls(): array {
		return array_map( fn( string $url ) => array( $url ), self::shared_cases()['permitted'] );
	}

	#[DataProvider( 'permitted_urls' )]
	public function test_permits_allowlisted_destinations( string $url ): void {
		$this->assertTrue( self::allowlist()->permits( $url ) );
	}

	public static function refused_urls(): array {
		return array_map( fn( string $url ) => array( $url ), self::shared_cases()['refused'] );
	}

	#[DataProvider( 'refused_urls' )]
	public function test_refuses_everything_else( string $url ): void {
		$this->assertFalse( self::allowlist()->permits( $url ) );
	}

	public function test_patterns_are_normalized(): void {
		$allowlist = DestinationAllowlist::from_patterns( array( '  Partner.Example.COM. ', '*.Example.ORG' ) );

		$this->assertTrue( $allowlist->permits( 'https://partner.example.com/' ) );
		$this->assertTrue( $allowlist->permits( 'https://go.example.org/' ) );
		$this->assertSame( array( 'partner.example.com', '*.example.org' ), $allowlist->patterns() );
	}

	/**
	 * Bad filter output must never widen into a match-everything rule.
	 */
	public static function invalid_patterns(): array {
		return array(
			'bare wildcard'      => array( '*' ),
			'wildcard dot'       => array( '*.' ),
			'inner wildcard'     => array( 'partner.*.com' ),
			'double wildcard'    => array( '*.*.example.com' ),
			'empty string'       => array( '' ),
			'whitespace'         => array( '   ' ),
			'only a dot'         => array( '.' ),
			'not a string'       => array( 42 ),
			'null'               => array( null ),
			'nested array'       => array( array( 'partner.example.com' ) ),
		);
	}

	#[DataProvider( 'invalid_patterns' )]
	public function test_invalid_patterns_are_dropped( mixed $pattern ): void {
		$allowlist = DestinationAllowlist::from_patterns( array( $pattern ) );

		$this->assertFalse( $allowlist->is_restricted() );
	}

	/**
	 * An invalid entry next to a valid one leaves the valid restriction intact.
	 */
	public function test_invalid_pattern_does_not_lift_restriction(): void {
		$allowlist = DestinationAllowlist::from_patterns( array( '*', 'partner.example.com' ) );

		$this->assertTrue( $allowlist->is_restricted() );
		$this->assertFalse( $allowlist->permits( 'https://attacker.example.net/' ) );
	}
}
