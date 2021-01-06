/**
 * @format
 */

export async function clickContinue() {
	// Wait for "Continue" button to become active
	await page.waitForSelector( 'button.is-primary:not(:disabled)' );

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

export async function getElementByText( element, text ) {
	const els = await page.$x(
		`//${ element }[contains(text(), '${ text }')]`
	);
	return els[ 0 ];
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

export async function getPreviousSibling( el ) {
	return page.evaluateHandle( ( el ) => el.previousElementSibling, el );
}

export async function getElementProperty( el, property ) {
	const prop = await el.getProperty( property );
	return prop.jsonValue();
}
