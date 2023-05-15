/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';
import { page } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { registerElementVariation } from './utils';
import {
	BLOCK_DESCRIPTION,
	BLOCK_TITLE,
} from '../../../../atomic/blocks/product-elements/summary/constants';

export const CORE_NAME = 'core/post-excerpt';
export const VARIATION_NAME = 'woocommerce/product-collection/product-summary';

registerElementVariation( CORE_NAME, {
	blockDescription: BLOCK_DESCRIPTION,
	blockIcon: <Icon icon={ page } />,
	blockTitle: BLOCK_TITLE,
	variationName: VARIATION_NAME,
} );
