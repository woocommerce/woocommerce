/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';
import {
	title,
	description,
} from '@woocommerce/atomic-blocks/product-elements/title/block.json';
import { heading } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { registerElementVariation } from './utils';

export const CORE_NAME = 'core/post-title';
export const VARIATION_NAME = 'woocommerce/product-query/product-title';

registerElementVariation( CORE_NAME, {
	blockDescription: description,
	blockIcon: <Icon icon={ heading } />,
	blockTitle: title,
	variationName: VARIATION_NAME,
	scope: [ 'block' ],
} );
