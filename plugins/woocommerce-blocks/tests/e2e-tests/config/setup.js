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
	createShippingZones,
	createBlockPages,
	enablePaymentGateways,
} from '../fixtures/fixture-loaders';

module.exports = async ( globalConfig ) => {
	// we need to load puppeteer global setup here.
	await setupPuppeteer( globalConfig );

	/**
	 * Promise.all will return an array of all promises resolved values.
	 * Some functions like setupSettings and enablePaymentGateways resolve
	 * to server data so we ignore the values here.
	 */
	return Promise.all( [
		setupSettings(),
		createTaxes(),
		createCoupons(),
		createProducts(),
		createShippingZones(),
		createBlockPages(),
		enablePaymentGateways(),
	] )
		.then( ( results ) => {
			/**
			 * We save our data in global so we can share it with teardown test.
			 * It is relativity safe to hold this data in global since the context
			 * in which setup and teardown run in is separate from the one our
			 * test use, so there is no risk of data bleeding.
			 */
			const [
				,
				taxes,
				coupons,
				products,
				shippingZones,
				pages,
			] = results;
			global.fixtureData = {
				taxes,
				coupons,
				products,
				shippingZones,
				pages,
			};
		} )
		.catch( console.log );
};
