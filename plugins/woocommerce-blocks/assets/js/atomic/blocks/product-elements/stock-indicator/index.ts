/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import attributes from './attributes';
import edit from './edit';
import { supports } from './supports';

import {
	BLOCK_TITLE as title,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';

const blockConfig: BlockConfiguration = {
	...sharedConfig,
	title,
	description,
	icon: { src: icon },
	attributes,
	supports,
	edit,
	usesContext: [ 'query', 'queryId', 'postId' ],
};

registerBlockType( 'woocommerce/product-stock-indicator', {
	...blockConfig,
} );
