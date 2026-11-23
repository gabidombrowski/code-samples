<?php
/**
 * Plugin Name:       Cached Event Listings
 * Description:       An events post type with a cached upcoming/past listing — object caching with generation-based invalidation, built for high-traffic sites.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Gabi Dombrowski
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cached-event-listings
 *
 * @package CachedEventListings
 */

declare( strict_types=1 );

namespace DigitalLotus\CachedEventListings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Composer's autoloader when developing; a minimal PSR-4 fallback so the
// plugin also runs from a plain copy of the folder with no build step.
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix = __NAMESPACE__ . '\\';
			if ( ! str_starts_with( $class, $prefix ) ) {
				return;
			}
			$relative = str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) );
			$file     = __DIR__ . '/src/' . $relative . '.php';
			if ( is_readable( $file ) ) {
				require $file;
			}
		}
	);
}

add_action( 'init', array( EventPostType::class, 'register' ) );

add_action(
	'init',
	static function (): void {
		$repository = new EventRepository();
		( new Shortcode( $repository ) )->register();

		// Any status change or deletion of an event bumps the cache
		// generation; see EventRepository::invalidate() for why no
		// individual keys need deleting.
		add_action(
			'transition_post_status',
			static function ( string $new_status, string $old_status, \WP_Post $post ) use ( $repository ): void {
				if ( EventPostType::POST_TYPE === $post->post_type && $new_status !== $old_status ) {
					$repository->invalidate();
				}
			},
			10,
			3
		);
		add_action(
			'deleted_post',
			static function ( int $post_id, \WP_Post $post ) use ( $repository ): void {
				if ( EventPostType::POST_TYPE === $post->post_type ) {
					$repository->invalidate();
				}
			},
			10,
			2
		);
		add_action(
			'updated_postmeta',
			static function ( int $meta_id, int $post_id, string $meta_key ) use ( $repository ): void {
				if ( EventPostType::DATE_META_KEY === $meta_key ) {
					$repository->invalidate();
				}
			},
			10,
			3
		);
	}
);
