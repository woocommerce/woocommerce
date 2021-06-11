/**
 * External dependencies
 */
import { getValidBlockAttributes } from '@woocommerce/base-utils';
import {
	getBlockMap,
	renderParentBlock,
	renderStandaloneBlocks,
} from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import Block from './block';
import blockAttributes from './attributes';
import { BLOCK_NAME } from './constants';

const getProps = ( el ) => {
	return {
		attributes: getValidBlockAttributes( blockAttributes, el.dataset ),
	};
};

renderParentBlock( {
	Block,
	blockName: BLOCK_NAME,
	selector: '.wp-block-woocommerce-single-product',
	getProps,
	blockMap: getBlockMap( BLOCK_NAME ),
} );

renderStandaloneBlocks();
