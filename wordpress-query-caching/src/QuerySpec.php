<?php
declare( strict_types=1 );

namespace DigitalLotus\CachedEventListings;

use InvalidArgumentException;

/**
 * Builds WP_Query argument arrays for event listings.
 *
 * Pure: no WordPress calls, so the exact shape of every query the plugin
 * runs is asserted in plain PHPUnit tests. The repository owns execution
 * and caching; this class owns correctness of the query itself.
 */
final class QuerySpec {

	/**
	 * Arguments for one page of a listing.
	 *
	 * The date comparison and the sort direction move together: upcoming
	 * events read forward from today (soonest first), past events read
	 * backward (most recent first). `meta_type => DATE` makes MySQL compare
	 * dates, not strings, and events on today's date count as upcoming.
	 *
	 * @param Direction $direction Which side of today to list.
	 * @param string    $today     Site-local date, `Y-m-d`.
	 * @param int       $page      1-based page number; values below 1 clamp to 1.
	 * @param int       $per_page  Events per page; values below 1 clamp to 1.
	 *
	 * @throws InvalidArgumentException When $today is not a Y-m-d date.
	 */
	public static function list_args( Direction $direction, string $today, int $page, int $per_page ): array {
		self::assert_ymd( $today );
		$upcoming = Direction::Upcoming === $direction;

		return array(
			'post_type'              => EventPostType::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => max( 1, $per_page ),
			'paged'                  => max( 1, $page ),
			'ignore_sticky_posts'    => true,
			'meta_key'               => EventPostType::DATE_META_KEY,
			'meta_type'              => 'DATE',
			'orderby'                => 'meta_value',
			'order'                  => $upcoming ? 'ASC' : 'DESC',
			'meta_query'             => array(
				array(
					'key'     => EventPostType::DATE_META_KEY,
					'value'   => $today,
					'compare' => $upcoming ? '>=' : '<',
					'type'    => 'DATE',
				),
			),
			// The repository caches IDs and primes post caches itself.
			'fields'                 => 'ids',
			'update_post_term_cache' => false,
		);
	}

	/**
	 * Arguments for an existence probe: "are there any past events at all?"
	 *
	 * One row, IDs only, no pagination count — the cheapest question the
	 * database can be asked, used to decide whether to render the link to
	 * the opposite listing.
	 *
	 * @param Direction $direction Which side of today to probe.
	 * @param string    $today     Site-local date, `Y-m-d`.
	 */
	public static function probe_args( Direction $direction, string $today ): array {
		return array_merge(
			self::list_args( $direction, $today, 1, 1 ),
			array(
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
			)
		);
	}

	private static function assert_ymd( string $today ): void {
		if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $today ) ) {
			// The offending value stays out of the message: exception text
			// can end up in error output, and this class has no WordPress
			// escaping functions by design.
			throw new InvalidArgumentException( 'Expected a Y-m-d date.' );
		}
	}
}
