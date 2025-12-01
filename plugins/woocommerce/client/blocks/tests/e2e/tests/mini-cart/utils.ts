/**
 * External dependencies
 */
import { FrontendUtils, BlockData } from '@woocommerce/e2e-utils';

export const blockData: BlockData = {
	name: 'Mini-Cart',
	slug: 'woocommerce/mini-cart',
	mainClass: '.wc-block-minicart',
	selectors: {
		frontend: {},
		editor: {},
	},
};

export const openMiniCart = async ( frontendUtils: FrontendUtils ) => {
	const block = await frontendUtils.getBlockByName( 'woocommerce/mini-cart' );
	await block.click();
};
