/**
 * External dependencies
 */
import React, { createElement } from 'react';

/**
 * Internal dependencies
 */
import { MediaUploader } from '../';

const MockMediaUpload = ( { onSelect, render } ) => {
	return render( {
		open: () => alert( 'WP Media Modal will be opened when loaded' ),
	} );
};

export const Basic: React.FC = () => (
	<MediaUploader
		MediaUploadComponent={ MockMediaUpload }
		onSelect={ ( media ) => console.log( media ) }
	/>
);

export default {
	title: 'WooCommerce Admin/components/MediaUploader',
	component: Basic,
};
