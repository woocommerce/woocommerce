/* eslint-disable no-console */
/**
 * External dependencies
 */
import { setup as setupPuppeteer } from 'jest-environment-puppeteer';
/**
 * Internal dependencies
 */
import {
	setupSettings,
	setupPageSettings,
	createTaxes,
	createCoupons,
	createProducts,
	createReviews,
	createCategories,
	createShippingZones,
	createBlockPages,
	enablePaymentGateways,
} from '../fixtures/fixture-loaders';

module.exports = async ( globalConfig ) => {
	// we need to load puppeteer global setup here.
	await setupPuppeteer( globalConfig );

	try {
		// do setupSettings separately which hopefully gives a chance for WooCommerce
		// to be configured before the others are executed.
		await setupSettings();
		const pages = await createBlockPages();

		/**
		 * Promise.all will return an array of all promises resolved values.
		 * Some functions like setupSettings and enablePaymentGateways resolve
		 * to server data so we ignore the values here.
		 */
		const results = await Promise.all( [
			createTaxes(),
			createCoupons(),
			createCategories(),
			createShippingZones(),
			enablePaymentGateways(),
			setupPageSettings(),
		] );
		const [ taxes, coupons, categories, shippingZones ] = results;

		// Create products after categories.
		const products = await createProducts( categories );

		/**
		 * Create fixture reviews data for each product.
		 */
		products.forEach( async ( productId ) => {
			await createReviews( productId );
		} );

		global.fixtureData = {
			taxes,
			coupons,
			products,
			shippingZones,
			pages,
		};
	} catch ( e ) {
		console.log( e );
	}
};
