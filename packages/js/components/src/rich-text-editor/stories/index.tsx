/**
 * External dependencies
 */
import React from 'react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { RichTextEditor } from '../';

export const Basic: React.FC = () => {
	return <RichTextEditor blocks={ [] } onChange={ () => null } />;
};

export default {
	title: 'WooCommerce Admin/components/RichTextEditor',
	component: RichTextEditor,
};
