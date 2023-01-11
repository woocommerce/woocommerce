/**
 * External dependencies
 */
import React from 'react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Tag from '../';

export const Basic = () => (
	<>
		<Tag label="My tag" id={ 1 } />
		<Tag
			label="Removable tag"
			id={ 2 }
			// eslint-disable-next-line no-alert
			remove={ ( id ) => () => window.alert( `Remove ID ${ id }` ) }
		/>
		<Tag
			label="Tag with popover"
			popoverContents={ <p>This is a popover</p> }
		/>
	</>
);

export default {
	title: 'WooCommerce Admin/components/Tag',
	component: Tag,
};
