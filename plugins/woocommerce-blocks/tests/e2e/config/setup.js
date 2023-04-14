/* eslint-disable no-console */
/**
 * External dependencies
 */
import { setup as setupPuppeteer } from 'jest-environment-puppeteer';
const { truncateSync, existsSync } = require( 'fs' );
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
	createTags,
	createShippingZones,
	createBlockPages,
	enablePaymentGateways,
	createProductAttributes,
	enableAttributeLookupDirectUpdates,
} from '../fixtures/fixture-loaders';
import { PERFORMANCE_REPORT_FILENAME } from '../../utils/constants';

module.exports = async ( globalConfig ) => {
	// we need to load puppeteer global setup here.
	await setupPuppeteer( globalConfig );

	try {
		await enableAttributeLookupDirectUpdates();

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
			createTags(),
			createShippingZones(),
			createProductAttributes(),
			enablePaymentGateways(),
			setupPageSettings(),
		] ).catch( console.log );

		const [ taxes, coupons, categories, tags, shippingZones, attributes ] =
			results;
		// Create products after categories.

		const products = await createProducts( categories, tags, attributes );
		/**
		 * Create fixture reviews data for each product.
		 */
		products.forEach( async ( productId ) => {
			await createReviews( productId );
		} );

		// Wipe the performance e2e file at the start of every run
		if ( existsSync( PERFORMANCE_REPORT_FILENAME ) ) {
			truncateSync( PERFORMANCE_REPORT_FILENAME );
		}

		global.fixtureData = {
			taxes,
			coupons,
			products,
			shippingZones,
			pages,
			attributes,
			categories,
			tags,
		};
	} catch ( e ) {
		console.log( e );
	}
};
