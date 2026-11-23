<?php
declare( strict_types=1 );

namespace DigitalLotus\CachedEventListings;

/**
 * Builds cache keys for event listings.
 *
 * Every input that changes the result is part of the key:
 *
 * - `$generation` implements invalidation-by-generation (WordPress core's
 *   `last_changed` pattern): writes bump one generation value instead of
 *   hunting down every direction/page/per-page combination, and superseded
 *   entries simply age out of the cache.
 * - `$today` makes day rollover self-invalidating — at midnight site time
 *   the key changes, so yesterday's "upcoming" list can never be served.
 *
 * Pure, so key composition is covered by unit tests.
 */
final class CacheKey {

	public static function for_list( Direction $direction, string $today, int $page, int $per_page, string $generation ): string {
		return sprintf( 'list:%s:%s:%d:%d:%s', $direction->value, $today, $page, $per_page, $generation );
	}

	public static function for_probe( Direction $direction, string $today, string $generation ): string {
		return sprintf( 'probe:%s:%s:%s', $direction->value, $today, $generation );
	}
}
