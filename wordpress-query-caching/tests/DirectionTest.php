<?php
declare( strict_types=1 );

namespace DigitalLotus\CachedEventListings\Tests;

use DigitalLotus\CachedEventListings\Direction;
use PHPUnit\Framework\TestCase;

final class DirectionTest extends TestCase {

	public function test_recognises_directions_case_insensitively(): void {
		$this->assertSame( Direction::Past, Direction::from_input( 'past' ) );
		$this->assertSame( Direction::Past, Direction::from_input( ' PAST ' ) );
		$this->assertSame( Direction::Upcoming, Direction::from_input( 'Upcoming' ) );
	}

	public function test_anything_else_falls_back_to_the_default(): void {
		$this->assertSame( Direction::Upcoming, Direction::from_input( null ) );
		$this->assertSame( Direction::Upcoming, Direction::from_input( '' ) );
		$this->assertSame( Direction::Upcoming, Direction::from_input( "past'; DROP TABLE wp_posts;--" ) );
		$this->assertSame( Direction::Past, Direction::from_input( 'nonsense', Direction::Past ) );
	}

	public function test_opposite_is_symmetric(): void {
		$this->assertSame( Direction::Past, Direction::Upcoming->opposite() );
		$this->assertSame( Direction::Upcoming, Direction::Past->opposite() );
	}
}
