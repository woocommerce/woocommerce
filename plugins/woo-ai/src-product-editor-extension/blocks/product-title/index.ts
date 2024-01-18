/**
 * External dependencies
 */
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import metadata from './block.json';

import './style.scss';

registerBlockType( metadata.name, {
	title: 'Product title with AI assistance',
	name: 'woocommerce/product-title-ai-field',

	/**
	 * @see ./edit.js
	 */
	edit: Edit,
} );
