<?php
declare( strict_types=1 );

namespace DigitalLotus\CachedEventListings;

/**
 * The event post type and its date meta.
 */
final class EventPostType {

	public const POST_TYPE     = 'cel_event';
	public const DATE_META_KEY = 'cel_event_date';

	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'          => __( 'Events', 'cached-event-listings' ),
					'singular_name' => __( 'Event', 'cached-event-listings' ),
					'add_new_item'  => __( 'Add New Event', 'cached-event-listings' ),
					'edit_item'     => __( 'Edit Event', 'cached-event-listings' ),
				),
				'public'       => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-calendar-alt',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => 'events' ),
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::DATE_META_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => array(
					'schema' => array(
						'type'   => 'string',
						'format' => 'date',
					),
				),
				'sanitize_callback' => array( self::class, 'sanitize_date' ),
				'auth_callback'     => static fn (): bool => current_user_can( 'edit_posts' ),
			)
		);
	}

	/**
	 * Keep only well-formed calendar dates; everything else becomes empty,
	 * which the listing queries treat as "no date, never listed".
	 *
	 * `wp_checkdate` (not just the regex) rejects shapes like 2026-02-31
	 * that MySQL's DATE cast would otherwise quietly fold into a real date.
	 *
	 * @param mixed $value Raw meta value.
	 */
	public static function sanitize_date( mixed $value ): string {
		if ( ! is_string( $value ) || 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) ) {
			return '';
		}

		return wp_checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1], $value ) ? $value : '';
	}
}
