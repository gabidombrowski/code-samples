/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Notice,
	PanelBody,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	editorAllowedHosts,
	permitsDestination,
} from './destination-allowlist';
import type { BlockAttributes } from './types';

/*
 * Editor UI for the block: step 2 of the click flow described in
 * "ABOUT THIS SAMPLE" in referrer-post-button.php.
 *
 * CALLOUT: only content and behavior are edited here. Colors, typography,
 * padding and radius come from `supports` in block.json, so the button picks
 * up theme.json styles and the standard design panels without custom code.
 */

interface EditProps {
	attributes: BlockAttributes;
	setAttributes: ( attrs: Partial< BlockAttributes > ) => void;
}

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const { text, url, opensInNewTab, postReferrer } = attributes;
	const blockProps = useBlockProps();

	// CALLOUT: mirrors the server's render_block check so the author learns
	// at edit time, not after publishing, that the referrer won't be sent.
	// Advisory only; the server still enforces.
	const referrerBlocked =
		postReferrer &&
		url !== '' &&
		! permitsDestination( editorAllowedHosts(), url );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Link', 'referrer-post-button' ) }>
					<TextControl
						label={ __( 'URL', 'referrer-post-button' ) }
						type="url"
						value={ url }
						onChange={ ( value ) =>
							setAttributes( { url: value } )
						}
						help={ __(
							'Destination the button links (or, with referrer delivery on, POSTs) to.',
							'referrer-post-button'
						) }
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __(
							'Open in new tab',
							'referrer-post-button'
						) }
						checked={ opensInNewTab }
						onChange={ ( value ) =>
							setAttributes( { opensInNewTab: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __(
							'Deliver referrer via POST',
							'referrer-post-button'
						) }
						checked={ postReferrer }
						onChange={ ( value ) =>
							setAttributes( { postReferrer: value } )
						}
						help={ __(
							'Intercepts the click and submits a POST form carrying this page’s URL, for destinations that Safari’s tracking prevention keeps the Referer header from reaching.',
							'referrer-post-button'
						) }
						__nextHasNoMarginBottom
					/>
					{ referrerBlocked && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'This site only sends the referrer to approved destinations, and this URL isn’t one of them. On the live site the button will work as a plain link, without the referrer.',
								'referrer-post-button'
							) }
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<RichText
					tagName="a"
					className="wp-element-button"
					value={ text }
					onChange={ ( value ) => setAttributes( { text: value } ) }
					placeholder={ __( 'Add text…', 'referrer-post-button' ) }
					// Plain-text label: render.php outputs it with esc_html().
					allowedFormats={ [] }
				/>
			</div>
		</>
	);
}
