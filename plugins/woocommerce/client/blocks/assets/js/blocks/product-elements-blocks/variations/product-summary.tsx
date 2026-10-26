/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';
import { page } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { registerElementVariation } from './utils';
import { BLOCK_DESCRIPTION, BLOCK_TITLE } from '../summary/constants';
import {
	PRODUCT_SUMMARY_VARIATION_NAME,
	PRODUCT_SUMMARY_NAMESPACES,
} from './constants';

export const CORE_NAME = 'core/post-excerpt';

const registerProductSummary = () => {
	registerElementVariation( CORE_NAME, {
		blockDescription: BLOCK_DESCRIPTION,
		blockIcon: <Icon icon={ page } />,
		blockTitle: BLOCK_TITLE,
		variationName: PRODUCT_SUMMARY_VARIATION_NAME,
		namespaces: PRODUCT_SUMMARY_NAMESPACES,
		scope: [],
	} );
};

export default registerProductSummary;
