/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { getBlockByName } from '@woocommerce/e2e-utils';

export const getMinMaxPriceInputs = async ( {
	page,
	blockName,
}: {
	page: Page;
	blockName: string;
} ) => {
	const priceFilterBlock = await getBlockByName( {
		page,
		name: blockName,
	} );

	const maxPriceInput = await priceFilterBlock.locator(
		'.wc-block-price-filter__amount--max'
	);

	const minPriceInput = await priceFilterBlock.locator(
		'.wc-block-price-filter__amount--min'
	);

	return { maxPriceInput, minPriceInput };
};
