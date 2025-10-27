/**
 * External dependencies
 */
import { ComponentType } from 'react';
import { MediaUpload } from '@wordpress/media-utils';
/**
 * Internal dependencies
 */
import { addFilterForEmail } from './config-tools';

export const initHooks = (): void => {
	// see https://github.com/WordPress/gutenberg/blob/master/packages/block-editor/src/components/media-upload/README.md
	const replaceMediaUpload = (): ComponentType => MediaUpload;
	addFilterForEmail(
		'editor.MediaUpload',
		'woocommerce/email-editor/replace-media-upload',
		replaceMediaUpload
	);
};
