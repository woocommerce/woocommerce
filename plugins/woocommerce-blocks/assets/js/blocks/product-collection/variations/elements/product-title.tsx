/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';
import { heading } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { registerElementVariation } from './utils';
import {
	BLOCK_DESCRIPTION,
	BLOCK_TITLE,
} from '../../../../atomic/blocks/product-elements/title/constants';
import blockJson from '../../block.json';

export const CORE_NAME = 'core/post-title';
export const VARIATION_NAME = `${ blockJson.name }/product-title`;

const registerProductTitle = () => {
	registerElementVariation( CORE_NAME, {
		blockDescription: BLOCK_DESCRIPTION,
		blockIcon: <Icon icon={ heading } />,
		blockTitle: BLOCK_TITLE,
		variationName: VARIATION_NAME,
	} );
};

export default registerProductTitle;
