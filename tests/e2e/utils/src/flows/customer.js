/**
 * External dependencies
 */
const config = require( 'config' );

/**
 * Internal dependencies
 */
const {
	MY_ACCOUNT_ADDRESSES,
	MY_ACCOUNT_ACCOUNT_DETAILS,
	MY_ACCOUNT_DOWNLOADS,
	MY_ACCOUNT_ORDERS,
	SHOP_MY_ACCOUNT_PAGE
} = require( './constants' );

const customer = {
	goToOrders: async () => {
		await page.goto( MY_ACCOUNT_ORDERS, {
			waitUntil: 'networkidle0',
		} );
	},

	goToDownloads: async () => {
		await page.goto( MY_ACCOUNT_DOWNLOADS, {
			waitUntil: 'networkidle0',
		} );
	},

	goToAddresses: async () => {
		await page.goto( MY_ACCOUNT_ADDRESSES, {
			waitUntil: 'networkidle0',
		} );
	},

	goToAccountDetails: async () => {
		await page.goto( MY_ACCOUNT_ACCOUNT_DETAILS, {
			waitUntil: 'networkidle0',
		} );
	},

	login: async () => {
		await page.goto( SHOP_MY_ACCOUNT_PAGE, {
			waitUntil: 'networkidle0',
		} );

		await expect( page.title() ).resolves.toMatch( 'My account' );

		await page.type( '#username', config.get('users.customer.username') );
		await page.type( '#password', config.get('users.customer.password') );

		await Promise.all( [
			page.waitForNavigation( { waitUntil: 'networkidle0' } ),
			page.click( 'button[name="login"]' ),
		] );
	},
};

module.exports = customer;
