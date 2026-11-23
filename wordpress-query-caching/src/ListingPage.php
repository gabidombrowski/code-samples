<?php
declare( strict_types=1 );

namespace DigitalLotus\CachedEventListings;

use WP_Post;

/**
 * One page of an event listing, as returned by the repository.
 */
final class ListingPage {

	/**
	 * @param WP_Post[] $posts     Events on this page, in listing order.
	 * @param int       $max_pages Total pages available for this direction.
	 */
	public function __construct(
		public readonly array $posts,
		public readonly int $max_pages,
	) {}

	public function is_empty(): bool {
		return array() === $this->posts;
	}
}
