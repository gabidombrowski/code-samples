<?php
declare( strict_types=1 );

namespace DigitalLotus\CachedEventListings;

use WP_Query;

/**
 * Runs and caches event listing queries.
 *
 * Caching strategy (WordPress core's `last_changed` pattern):
 *
 * - Results are cached as post IDs, not post objects. Post objects are
 *   already individually cached by core; duplicating them inside a listing
 *   entry wastes cache memory and risks serving stale revisions.
 * - Invalidation bumps a single generation value instead of deleting keys.
 *   Every listing key embeds the generation, so one write invalidates every
 *   direction/page/per-page combination at once, and the superseded entries
 *   age out via TTL. Deleting individual keys can't do this reliably —
 *   there is no wildcard delete in the object cache API.
 * - Keys also embed the site-local date, so day rollover self-invalidates
 *   (yesterday's "upcoming" list is unreachable after midnight).
 */
final class EventRepository {

	public const CACHE_GROUP = 'cel_events';

	private const GENERATION_KEY = 'generation';
	private const TTL            = HOUR_IN_SECONDS;

	public function __construct( private readonly int $per_page = 10 ) {}

	/**
	 * One page of events for a direction.
	 */
	public function find( Direction $direction, int $page = 1 ): ListingPage {
		$today = current_time( 'Y-m-d' );
		$key   = CacheKey::for_list( $direction, $today, $page, $this->per_page, $this->generation() );

		$cached = wp_cache_get( $key, self::CACHE_GROUP );
		if ( ! is_array( $cached ) ) {
			$query  = new WP_Query( QuerySpec::list_args( $direction, $today, $page, $this->per_page ) );
			$cached = array(
				'ids'       => array_map( 'intval', $query->posts ),
				'max_pages' => (int) $query->max_num_pages,
			);
			wp_cache_set( $key, $cached, self::CACHE_GROUP, self::TTL );
		}

		// Re-hydrate from the (primed) post cache rather than storing
		// whole WP_Post objects in the listing entry.
		_prime_post_caches( $cached['ids'], false, true );
		$posts = array_values( array_filter( array_map( 'get_post', $cached['ids'] ) ) );

		return new ListingPage( $posts, $cached['max_pages'] );
	}

	/**
	 * Whether any event exists in a direction — drives the link across to
	 * the opposite listing without paying for a full query.
	 */
	public function has_any( Direction $direction ): bool {
		$today = current_time( 'Y-m-d' );
		$key   = CacheKey::for_probe( $direction, $today, $this->generation() );

		$cached = wp_cache_get( $key, self::CACHE_GROUP );
		if ( ! is_string( $cached ) ) {
			$query  = new WP_Query( QuerySpec::probe_args( $direction, $today ) );
			$cached = array() === $query->posts ? 'no' : 'yes';
			wp_cache_set( $key, $cached, self::CACHE_GROUP, self::TTL );
		}

		return 'yes' === $cached;
	}

	/**
	 * Invalidate every cached listing at once by bumping the generation.
	 */
	public function invalidate(): void {
		wp_cache_set( self::GENERATION_KEY, (string) microtime( true ), self::CACHE_GROUP );
	}

	private function generation(): string {
		$generation = wp_cache_get( self::GENERATION_KEY, self::CACHE_GROUP );
		if ( ! is_string( $generation ) || '' === $generation ) {
			$generation = (string) microtime( true );
			wp_cache_set( self::GENERATION_KEY, $generation, self::CACHE_GROUP );
		}

		return $generation;
	}
}
