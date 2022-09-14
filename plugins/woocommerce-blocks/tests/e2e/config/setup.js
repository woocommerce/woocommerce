/* eslint-disable no-console */
/**
 * External dependencies
 */
import path from 'path';
import { setup as setupPuppeteer } from 'jest-environment-puppeteer';
const { truncateSync, existsSync, unlinkSync } = require( 'fs' );
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
	disableAttributeLookup,
} from '../fixtures/fixture-loaders';
import { PERFORMANCE_REPORT_FILENAME } from '../../utils/constants';
import { GUTENBERG_EDITOR_CONTEXT } from '../utils';

module.exports = async ( globalConfig ) => {
	/**
	 * We have to remove snapshots to avoid "obsolete snapshot" errors.
	 *
	 * @todo Remove this logic when WordPress 6.1 is released.
	 */
	if ( GUTENBERG_EDITOR_CONTEXT !== 'core' ) {
		unlinkSync(
			path.join(
				__dirname,
				'../specs/backend/__snapshots__/site-editing-templates.test.js.snap'
			)
		);
	}
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

		// This is necessary for avoid this bug https://github.com/woocommerce/woocommerce/issues/32065
		await disableAttributeLookup();

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
