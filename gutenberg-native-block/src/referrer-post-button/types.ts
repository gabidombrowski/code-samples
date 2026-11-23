export interface BlockAttributes {
	text: string;
	url: string;
	opensInNewTab: boolean;
	postReferrer: boolean;
}

declare global {
	interface Window {
		/**
		 * Printed before the editor script by referrer-post-button.php.
		 * Only present in the block editor.
		 */
		referrerPostButtonSettings?: {
			allowedHosts: string[];
		};
	}
}
