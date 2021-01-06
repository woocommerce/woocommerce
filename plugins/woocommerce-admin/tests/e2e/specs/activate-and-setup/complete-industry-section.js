/**
 * @format
 */

/**
 * Internal dependencies
 */
import {
	clickContinue,
	getElementByText,
	getPreviousSibling,
	setCheckboxToChecked,
	setCheckboxToUnchecked,
} from './utils';
import { waitForElementCount } from '../../utils/lib';
const config = require( 'config' );

export async function completeIndustrySection( expectedIndustryCount = 8 ) {
	// Query for the industries checkboxes
	await waitForElementCount(
		page,
		'.components-checkbox-control__input',
		expectedIndustryCount
	);

	await uncheckAllExistingIndustries();

	// Select just "fashion" and "health/beauty" to get the single checkbox business section when
	// filling out details for a US store.
	const fashionLabel = await getElementByText(
		'label',
		'Fashion, apparel, and accessories'
	);
	// get sibling checkbox
	const fashionCheckboxContainer = await getPreviousSibling( fashionLabel );
	const fashionCheckbox = await fashionCheckboxContainer.$( 'input' );

	await setCheckboxToChecked( fashionCheckbox );

	const healthBeautyLabel = await getElementByText(
		'label',
		'Health and beauty'
	);
	const healthBeautyCheckboxContainer = await getPreviousSibling(
		healthBeautyLabel
	);
	const healthBeautyCheckbox = await healthBeautyCheckboxContainer.$(
		'input'
	);

	await setCheckboxToChecked( healthBeautyCheckbox );
	await clickContinue();
}

// Check industry checkboxes based on industryLabels passed in. Note each label must match
// the text contents of the label.
export async function chooseIndustries( industryLabels = [] ) {
	await uncheckAllExistingIndustries();

	for ( const labelText of industryLabels ) {
		const label = await getElementByText( 'label', labelText );
		const checkboxContainer = await getPreviousSibling( label );
		const checkbox = await checkboxContainer.$( 'input' );

		await setCheckboxToChecked( checkbox );
		await clickContinue();
	}
}

// Utility to uncheck all the industry checkboxes, ensuring no state crosses over
// between tests.
async function uncheckAllExistingIndustries() {
	const industryCheckboxes = await page.$$(
		'.components-checkbox-control__input'
	);

	for ( const checkbox of industryCheckboxes ) {
		await setCheckboxToUnchecked( checkbox );
	}
}
