/* eslint-disable no-console */
/**
 * External dependencies
 */
import { teardown as teardownPuppeteer } from 'jest-environment-puppeteer';

/**
 * Internal dependencies
 */
import {
	deleteTaxes,
	deleteCoupons,
	deleteProducts,
	deleteShippingZones,
	deleteBlockPages,
	deleteProductAttributes,
} from '../fixtures/fixture-loaders';

module.exports = async ( globalConfig ) => {
	await teardownPuppeteer( globalConfig );
	const {
		taxes,
		coupons,
		products,
		shippingZones,
		pages,
		attributes,
	} = global.fixtureData;
	return Promise.allSettled( [
		deleteTaxes( taxes ),
		deleteCoupons( coupons ),
		deleteProducts( products ),
		deleteShippingZones( shippingZones ),
		deleteBlockPages( pages ),
		deleteProductAttributes( attributes ),
	] ).catch( console.log );
};
