/**
 * WordPress dependencies
 */
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import metadata from './block.json';
import type { BlockAttributes } from './types';
import './style.css';

// Registers the editor side of the block. See "ABOUT THIS SAMPLE" in
// referrer-post-button.php for how the pieces fit together.
//
// block.json is the source of truth for attributes; the cast is needed
// because importing JSON widens literal types like "string" to string.
registerBlockType< BlockAttributes >( metadata.name, {
	title: metadata.title,
	category: metadata.category,
	attributes:
		metadata.attributes as BlockConfiguration< BlockAttributes >[ 'attributes' ],
	edit: Edit,
	// CALLOUT: dynamic block. Rendering happens server-side in render.php
	// and save() persists nothing, so markup changes never invalidate
	// saved content.
	save: () => null,
} );
