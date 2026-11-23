<?php
declare( strict_types=1 );

namespace DigitalLotus\CachedEventListings;

use WP_Post;

/**
 * `[event_listings]` — renders one direction of the listing with pagination
 * and a link across to the opposite direction when it has content.
 */
final class Shortcode {

	public const TAG = 'event_listings';

	public function __construct( private readonly EventRepository $repository ) {}

	public function register(): void {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * @param array|string $atts Shortcode attributes.
	 */
	public function render( array|string $atts ): string {
		$atts = shortcode_atts( array( 'direction' => Direction::Upcoming->value ), $atts, self::TAG );

		// Read-only view parameters: they select what to display and change
		// no state, so they are whitelisted rather than nonce-checked.
		// Direction is validated by enum construction; page is cast to int.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$requested = isset( $_GET['direction'] )
			? sanitize_key( wp_unslash( $_GET['direction'] ) )
			: null;
		$page      = isset( $_GET['event_page'] ) ? absint( wp_unslash( $_GET['event_page'] ) ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$direction = Direction::from_input( $requested, Direction::from_input( $atts['direction'] ) );
		$listing   = $this->repository->find( $direction, max( 1, $page ) );

		$html = '<div class="cel-events cel-events--' . esc_attr( $direction->value ) . '">';

		if ( $listing->is_empty() ) {
			$html .= '<p class="cel-events__empty">' . esc_html( $this->empty_message( $direction ) ) . '</p>';
		} else {
			$html .= '<ul class="cel-events__list">';
			foreach ( $listing->posts as $event ) {
				$html .= $this->render_item( $event );
			}
			$html .= '</ul>';
			$html .= $this->render_pagination( $direction, max( 1, $page ), $listing->max_pages );
		}

		$html .= $this->render_direction_link( $direction );
		$html .= '</div>';

		return $html;
	}

	private function render_item( WP_Post $event ): string {
		$date  = get_post_meta( $event->ID, EventPostType::DATE_META_KEY, true );
		$stamp = is_string( $date ) && '' !== $date ? strtotime( $date ) : false;

		$html = '<li class="cel-events__item">';
		if ( false !== $stamp ) {
			$html .= sprintf(
				'<time class="cel-events__date" datetime="%s">%s</time> ',
				esc_attr( $date ),
				esc_html( date_i18n( (string) get_option( 'date_format' ), $stamp ) )
			);
		}
		$html .= sprintf(
			'<a class="cel-events__link" href="%s">%s</a>',
			esc_url( (string) get_permalink( $event ) ),
			esc_html( get_the_title( $event ) )
		);

		return $html . '</li>';
	}

	private function render_pagination( Direction $direction, int $page, int $max_pages ): string {
		if ( $max_pages <= 1 ) {
			return '';
		}

		$link = function ( int $to, string $label ) use ( $direction ): string {
			$url = add_query_arg(
				array(
					'direction'  => $direction->value,
					'event_page' => $to,
				)
			);
			return sprintf(
				'<a class="cel-events__page-link" href="%s">%s</a>',
				esc_url( $url ),
				esc_html( $label )
			);
		};

		$html = '<nav class="cel-events__pagination" aria-label="' . esc_attr__( 'Events pagination', 'cached-event-listings' ) . '">';
		if ( $page > 1 ) {
			$html .= $link( $page - 1, __( 'Newer', 'cached-event-listings' ) );
		}
		$html .= sprintf(
			'<span class="cel-events__page-current">%s</span>',
			esc_html(
				sprintf(
					/* translators: 1: current page, 2: total pages. */
					__( 'Page %1$d of %2$d', 'cached-event-listings' ),
					$page,
					$max_pages
				)
			)
		);
		if ( $page < $max_pages ) {
			$html .= $link( $page + 1, __( 'Older', 'cached-event-listings' ) );
		}

		return $html . '</nav>';
	}

	private function render_direction_link( Direction $direction ): string {
		$opposite = $direction->opposite();
		if ( ! $this->repository->has_any( $opposite ) ) {
			return '';
		}

		$url = add_query_arg( 'direction', $opposite->value, remove_query_arg( 'event_page' ) );

		return sprintf(
			'<p class="cel-events__toggle"><a href="%s">%s</a></p>',
			esc_url( $url ),
			esc_html(
				Direction::Past === $opposite
					? __( 'View past events', 'cached-event-listings' )
					: __( 'View upcoming events', 'cached-event-listings' )
			)
		);
	}

	private function empty_message( Direction $direction ): string {
		return Direction::Upcoming === $direction
			? __( 'No upcoming events are scheduled.', 'cached-event-listings' )
			: __( 'No past events yet.', 'cached-event-listings' );
	}
}
