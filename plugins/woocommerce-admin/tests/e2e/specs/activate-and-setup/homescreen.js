import { getElementByText } from './utils';

export async function onHomescreen() {
	// Wait for Benefits section to appear
	await page.waitForSelector( '.woocommerce-homescreen' );
	await page.waitForFunction(
		'document.querySelector(".woocommerce-layout__inbox-title").innerText == "Your store today"'
	);
}

export async function possibleDismissWelcomeModal() {
	// Wait for Benefits section to appear
	const modal = await getElementByText(
		'h2',
		'Welcome to your WooCommerce store’s online HQ!'
	);
	if ( modal ) {
		let nextButton = await getElementByText( 'button', 'Next' );
		await nextButton.click();

		await page.waitForFunction(
			'document.querySelector(".woocommerce__welcome-modal h2").innerText == "A personalized inbox full of relevant advice"'
		);
		nextButton = await getElementByText( 'button', 'Next' );
		await nextButton.click();
		await page.waitForFunction(
			'document.querySelector(".woocommerce__welcome-modal h2").innerText == "Good data leads to smart business decisions"'
		);
		nextButton = await page.$( '.components-guide__finish-button' );
		await nextButton.click();
	}
}

export async function getTaskList() {
	// Log out link in admin bar is not visible so can't be clicked directly.
	const list = await page.$$eval(
		'.woocommerce-task-card .woocommerce-list__item',
		( am ) => am.map( ( e ) => e.textContent )
	);
	return list.map( ( item ) => {
		const match = item.match( /(.+)[0-9] minute/ );
		if ( match && match.length > 1 ) {
			return match[ 1 ];
		}
		return item;
	} );
}

export async function clickOnTaskList( index ) {
	const taskItems = await page.$$(
		'.woocommerce-task-card .woocommerce-list__item'
	);

	// Fill the number of products you plan to sell
	await taskItems[ index ].click();
	await page.waitForNavigation( { waitUntil: 'networkidle0' } );
}

export const TaskTitles = {
	storeDetails: 'Store details',
	addPayments: 'Choose payment methods',
	wooPayments:
		'Set up WooCommerce PaymentsBy setting up, you are agreeing to the Terms of Service2 minutes',
	addProducts: 'Add products',
	addTaxRates: 'Add tax rates',
	setUpShippingCosts: 'Set up shipping costs',
	personalizeStore: 'Personalize your store',
};
