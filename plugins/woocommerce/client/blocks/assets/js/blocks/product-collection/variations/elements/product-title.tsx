/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';
import { heading } from '@wordpress/icons';
import {
	title,
	description,
} from '@woocommerce/atomic-blocks/product-elements/title/block.json';

/**
 * Internal dependencies
 */
import { registerElementVariation } from './utils';
import blockJson from '../../block.json';

export const CORE_NAME = 'core/post-title';
export const VARIATION_NAME = `${ blockJson.name }/product-title`;

const registerProductTitle = () => {
	registerElementVariation( CORE_NAME, {
		blockDescription: description,
		blockIcon: <Icon icon={ heading } />,
		blockTitle: title,
		variationName: VARIATION_NAME,
		scope: [ 'block', 'inserter' ],
	} );
};

export default registerProductTitle;
