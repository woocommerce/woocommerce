/**
 * @format
 */

/**
 * Internal dependencies
 */
import { clickContinue, setCheckboxToChecked } from './utils';
import { waitForElementCount } from '../../utils/lib';

export async function completeProductTypesSection(
	expectedProductTypeCount = 7
) {
	await waitForElementCount(
		page,
		'.components-checkbox-control__input',
		expectedProductTypeCount
	);

	// Query for the product types checkboxes
	const productTypesCheckboxes = await page.$$(
		'.components-checkbox-control__input'
	);

	// Select Physical and Downloadable products
	for ( let i = 0; i < 2; i++ ) {
		await setCheckboxToChecked( productTypesCheckboxes[ i ] );
	}

	await clickContinue();
}
