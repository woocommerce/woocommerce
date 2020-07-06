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
		/**
		 * Promise.all will return an array of all promises resolved values.
		 * Some functions like setupSettings and enablePaymentGateways resolve
		 * to server data so we ignore the values here.
		 */
		const results = await Promise.all( [
			setupSettings(),
			createTaxes(),
			createCoupons(),
			createCategories(),
			createShippingZones(),
			createBlockPages(),
			enablePaymentGateways(),
		] );
		const [ , taxes, coupons, categories, shippingZones, pages ] = results;

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
