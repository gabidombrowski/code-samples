<?php
declare( strict_types=1 );

namespace DigitalLotus\CachedEventListings\Tests;

use DigitalLotus\CachedEventListings\Direction;
use DigitalLotus\CachedEventListings\EventPostType;
use DigitalLotus\CachedEventListings\QuerySpec;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class QuerySpecTest extends TestCase {

	private const TODAY = '2026-09-21';

	public function test_upcoming_reads_forward_from_today_soonest_first(): void {
		$args = QuerySpec::list_args( Direction::Upcoming, self::TODAY, 1, 10 );

		$this->assertSame( 'ASC', $args['order'] );
		$this->assertSame( '>=', $args['meta_query'][0]['compare'] );
		$this->assertSame( self::TODAY, $args['meta_query'][0]['value'] );
	}

	public function test_past_reads_backward_from_today_most_recent_first(): void {
		$args = QuerySpec::list_args( Direction::Past, self::TODAY, 1, 10 );

		$this->assertSame( 'DESC', $args['order'] );
		$this->assertSame( '<', $args['meta_query'][0]['compare'] );
	}

	public function test_dates_compare_as_dates_not_strings(): void {
		$args = QuerySpec::list_args( Direction::Upcoming, self::TODAY, 1, 10 );

		$this->assertSame( 'DATE', $args['meta_type'] );
		$this->assertSame( 'DATE', $args['meta_query'][0]['type'] );
		$this->assertSame( EventPostType::DATE_META_KEY, $args['meta_key'] );
	}

	public function test_page_and_per_page_clamp_to_one(): void {
		$args = QuerySpec::list_args( Direction::Upcoming, self::TODAY, 0, -5 );

		$this->assertSame( 1, $args['paged'] );
		$this->assertSame( 1, $args['posts_per_page'] );
	}

	public function test_only_published_events_are_listed(): void {
		$args = QuerySpec::list_args( Direction::Upcoming, self::TODAY, 1, 10 );

		$this->assertSame( EventPostType::POST_TYPE, $args['post_type'] );
		$this->assertSame( 'publish', $args['post_status'] );
	}

	public function test_probe_is_the_cheapest_possible_query(): void {
		$args = QuerySpec::probe_args( Direction::Past, self::TODAY );

		$this->assertSame( 1, $args['posts_per_page'] );
		$this->assertSame( 'ids', $args['fields'] );
		$this->assertTrue( $args['no_found_rows'] );
		$this->assertFalse( $args['update_post_meta_cache'] );
	}

	public function test_a_malformed_date_is_rejected_before_reaching_a_query(): void {
		$this->expectException( InvalidArgumentException::class );

		QuerySpec::list_args( Direction::Upcoming, '21/09/2026', 1, 10 );
	}
}
