<?php
/**
 * Server render for the Referrer POST Button block.
 *
 * Step 3 of the click flow described in "ABOUT THIS SAMPLE" in
 * referrer-post-button.php. This file owns the markup contract with view.ts:
 * the data-referrer-post-* attributes below are the only thing the front-end
 * script reads.
 *
 * @package ReferrerPostButton
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused; the block is dynamic).
 * @var WP_Block $block      Block instance.
 */

$text           = $attributes['text'] ?? '';
$url            = $attributes['url'] ?? '';
$opens_new_tab  = ! empty( $attributes['opensInNewTab'] );
$posts_referrer = ! empty( $attributes['postReferrer'] );

// Nothing meaningful to render without a label.
if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
	return;
}

// CALLOUT: the label is validated after stripping tags, so a label that is
// only markup or whitespace renders nothing rather than an empty button.

$anchor_attributes = array(
	'class' => 'wp-element-button',
	'href'  => esc_url( $url ),
);

if ( $opens_new_tab ) {
	$anchor_attributes['target'] = '_blank';
	$anchor_attributes['rel']    = 'noopener';
}

/*
 * Referrer delivery does not use the Referer header: Safari's ITP trims the
 * cross-site Referer to its origin, so a destination that needs the full
 * referring URL can't rely on it. Instead, mark the link so view.js
 * intercepts the click and POSTs a base64 'referrer' field carrying
 * window.location.href, which the destination reads from the request body.
 */
if ( $posts_referrer && '' !== $url ) {
	$anchor_attributes['data-referrer-post-endpoint'] = esc_url( $url );
	$anchor_attributes['data-referrer-post-newtab']   = $opens_new_tab ? '1' : '0';
}

// CALLOUT: every value is escaped for its context exactly once: esc_url()
// for URLs above, esc_attr() for each attribute here, esc_html() for the label.
$anchor_html = '';
foreach ( $anchor_attributes as $name => $value ) {
	$anchor_html .= sprintf( ' %s="%s"', $name, esc_attr( $value ) );
}
?>
<div <?php echo wp_kses_data( get_block_wrapper_attributes() ); ?>>
	<a<?php echo $anchor_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each value escaped above. ?>>
		<?php echo esc_html( $text ); ?>
	</a>
</div>
