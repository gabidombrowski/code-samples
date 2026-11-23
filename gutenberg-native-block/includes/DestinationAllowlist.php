<?php
/**
 * Destination host allowlist for referrer delivery.
 *
 * @package ReferrerPostButton
 */

declare( strict_types=1 );

namespace DigitalLotus\ReferrerPostButton;

/**
 * Decides whether a destination URL may receive the referring page's URL.
 *
 * Pure: no WordPress calls, so the matching rules are covered by plain
 * PHPUnit. The WordPress wiring lives in the plugin bootstrap.
 *
 * CALLOUT: the editor mirrors these rules in destination-allowlist.ts to warn
 * authors early. Both implementations run the same cases from
 * tests/fixtures/allowlist-cases.json, and this class stays authoritative:
 * the editor only warns, the server enforces.
 *
 * Patterns are hostnames, matched case-insensitively and ignoring port:
 *
 * - 'partner.example.com'  exactly that host.
 * - '*.example.com'        any subdomain of example.com, at any depth, but
 *                          not example.com itself; list both when both apply.
 *
 * An empty allowlist is unrestricted, so the feature works out of the box;
 * a site opts in to restriction by supplying hosts.
 */
final class DestinationAllowlist {

	/**
	 * Normalized patterns: lowercase, no trailing dot, no empties.
	 *
	 * @param string[] $patterns
	 */
	private function __construct( private readonly array $patterns ) {}

	/**
	 * Build an allowlist from untrusted filter output.
	 *
	 * Anything that isn't a non-empty string, or is a malformed wildcard
	 * ('*', '*.', 'partner.*.com'), is dropped rather than widened into a
	 * match-everything rule.
	 *
	 * @param array<mixed> $patterns Raw hostnames and wildcard patterns.
	 */
	public static function from_patterns( array $patterns ): self {
		$normalized = array();

		foreach ( $patterns as $pattern ) {
			if ( ! is_string( $pattern ) ) {
				continue;
			}

			$pattern = self::normalize_host( $pattern );
			$suffix  = str_starts_with( $pattern, '*.' ) ? substr( $pattern, 2 ) : $pattern;

			if ( '' === $suffix || str_contains( $suffix, '*' ) ) {
				continue;
			}

			$normalized[] = $pattern;
		}

		return new self( array_values( array_unique( $normalized ) ) );
	}

	/**
	 * The normalized patterns, e.g. for handing to the editor script.
	 *
	 * @return string[]
	 */
	public function patterns(): array {
		return $this->patterns;
	}

	/**
	 * Whether any restriction applies.
	 */
	public function is_restricted(): bool {
		return array() !== $this->patterns;
	}

	/**
	 * Whether the URL's destination may receive the referrer.
	 *
	 * Only absolute http(s) URLs with a host can be permitted when the list is
	 * restricted; relative, scheme-relative and other-scheme URLs are refused.
	 */
	public function permits( string $url ): bool {
		if ( ! $this->is_restricted() ) {
			return true;
		}

		$parts  = parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure class, no WordPress loaded.
		$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
		$host   = self::normalize_host( (string) ( $parts['host'] ?? '' ) );

		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || '' === $host ) {
			return false;
		}

		foreach ( $this->patterns as $pattern ) {
			if ( str_starts_with( $pattern, '*.' ) ) {
				// '.example.com' suffix: rejects 'notexample.com' and 'example.com'.
				if ( str_ends_with( $host, substr( $pattern, 1 ) ) ) {
					return true;
				}
			} elseif ( $host === $pattern ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Lowercase, trimmed, without the trailing dot of a fully qualified name.
	 */
	private static function normalize_host( string $host ): string {
		return rtrim( strtolower( trim( $host ) ), '.' );
	}
}
