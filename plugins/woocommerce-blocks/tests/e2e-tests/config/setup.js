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
			createProducts(),
			createShippingZones(),
			createBlockPages(),
			enablePaymentGateways(),
		] );
		const [ , taxes, coupons, products, shippingZones, pages ] = results;

		/**
		 * Create fixture reviews data on first product.
		 */
		await createReviews( products[ 0 ] );

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
