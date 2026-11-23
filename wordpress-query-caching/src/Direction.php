<?php
declare( strict_types=1 );

namespace DigitalLotus\CachedEventListings;

/**
 * Which side of "today" a listing shows.
 *
 * Backed by the strings used in URLs and shortcode attributes, so request
 * input is validated by construction: anything outside the enum falls back
 * to the caller's default instead of reaching a query.
 */
enum Direction: string {
	case Upcoming = 'upcoming';
	case Past     = 'past';

	/**
	 * Whitelist untrusted input (a query arg, a shortcode attribute).
	 *
	 * @param string|null $input   Raw request value.
	 * @param Direction   $default Returned when the input is not a direction.
	 */
	public static function from_input( ?string $input, Direction $default = self::Upcoming ): self {
		return self::tryFrom( strtolower( trim( (string) $input ) ) ) ?? $default;
	}

	/**
	 * The listing this one links across to ("View past events" and back).
	 */
	public function opposite(): self {
		return self::Upcoming === $this ? self::Past : self::Upcoming;
	}
}
