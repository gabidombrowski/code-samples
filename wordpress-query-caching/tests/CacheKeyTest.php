<?php
declare( strict_types=1 );

namespace DigitalLotus\CachedEventListings\Tests;

use DigitalLotus\CachedEventListings\CacheKey;
use DigitalLotus\CachedEventListings\Direction;
use PHPUnit\Framework\TestCase;

final class CacheKeyTest extends TestCase {

	public function test_same_inputs_produce_the_same_key(): void {
		$this->assertSame(
			CacheKey::for_list( Direction::Upcoming, '2026-09-21', 2, 10, 'gen-a' ),
			CacheKey::for_list( Direction::Upcoming, '2026-09-21', 2, 10, 'gen-a' )
		);
	}

	/**
	 * Every dimension that changes the result must change the key —
	 * otherwise two different listings could collide on one cache entry.
	 */
	public function test_every_dimension_varies_the_key(): void {
		$base = CacheKey::for_list( Direction::Upcoming, '2026-09-21', 1, 10, 'gen-a' );

		$variants = array(
			'direction'  => CacheKey::for_list( Direction::Past, '2026-09-21', 1, 10, 'gen-a' ),
			'today'      => CacheKey::for_list( Direction::Upcoming, '2026-09-22', 1, 10, 'gen-a' ),
			'page'       => CacheKey::for_list( Direction::Upcoming, '2026-09-21', 2, 10, 'gen-a' ),
			'per_page'   => CacheKey::for_list( Direction::Upcoming, '2026-09-21', 1, 25, 'gen-a' ),
			'generation' => CacheKey::for_list( Direction::Upcoming, '2026-09-21', 1, 10, 'gen-b' ),
		);

		foreach ( $variants as $dimension => $key ) {
			$this->assertNotSame( $base, $key, "Changing {$dimension} must change the key." );
		}
	}

	public function test_probe_keys_never_collide_with_list_keys(): void {
		$this->assertNotSame(
			CacheKey::for_probe( Direction::Upcoming, '2026-09-21', 'gen-a' ),
			CacheKey::for_list( Direction::Upcoming, '2026-09-21', 1, 1, 'gen-a' )
		);
	}
}
