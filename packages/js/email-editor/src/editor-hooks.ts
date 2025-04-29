/**
 * External dependencies
 */
import { ComponentType } from 'react';
import { MediaUpload } from '@wordpress/media-utils';
import { addFilter } from '@wordpress/hooks';

export const initHooks = (): void => {
	// see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/media-upload/README.md
	const replaceMediaUpload = (): ComponentType => MediaUpload;
	addFilter(
		'editor.MediaUpload',
		'woocommerce/email-editor/replace-media-upload',
		replaceMediaUpload
	);
};
