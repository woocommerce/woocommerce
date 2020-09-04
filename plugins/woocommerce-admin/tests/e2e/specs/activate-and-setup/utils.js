/**
 * @format
 */

import { waitForSelector } from '../../utils/lib';

export async function clickContinue() {
	// Wait for "Continue" button to become active
	await waitForSelector( page, 'button.is-primary:not(:disabled)' );

	// Click on "Continue" button to move to the next step
	await page.click( 'button.is-primary' );

	// Wait for the page to load
	await page.waitForNavigation( { waitUntil: 'networkidle0' } );
}

export async function setCheckboxToChecked( checkbox ) {
	const checkedProperty = await checkbox.getProperty( 'checked' );
	const checked = await checkedProperty.jsonValue();

	// Skip if the checkbox is already checked.
	if ( checked ) {
		return;
	}

	await checkbox.click();
}

export async function getText( node ) {
	return page.evaluate( ( element ) => element.textContent, node );
}

export async function setCheckboxToUnchecked( checkbox ) {
	const checkedProperty = await checkbox.getProperty( 'checked' );
	const checked = await checkedProperty.jsonValue();

	// Skip if the checkbox is already unchecked.
	if ( ! checked ) {
		return;
	}

	await checkbox.click();
}
