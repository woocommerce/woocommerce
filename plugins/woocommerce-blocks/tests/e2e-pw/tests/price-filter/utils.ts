/**
 * External dependencies
 */
import { Locator } from '@playwright/test';

export const getMinMaxPriceInputs = async (
	priceFilterBlockLocator: Locator
) => {
	const maxPriceInput = await priceFilterBlockLocator.locator(
		'.wc-block-price-filter__amount--max'
	);

	const minPriceInput = await priceFilterBlockLocator.locator(
		'.wc-block-price-filter__amount--min'
	);

	return { maxPriceInput, minPriceInput };
};
