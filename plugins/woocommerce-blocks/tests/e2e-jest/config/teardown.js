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
	deleteCategories,
	deleteTags,
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
		tags,
		categories,
		coupons,
		products,
		shippingZones,
		pages,
		attributes,
	} = global.fixtureData;
	return Promise.allSettled( [
		deleteCategories( categories ),
		deleteTags( tags ),
		deleteTaxes( taxes ),
		deleteCoupons( coupons ),
		deleteProducts( products ),
		deleteShippingZones( shippingZones ),
		deleteBlockPages( pages ),
		deleteProductAttributes( attributes ),
	] ).catch( console.log );
};
