/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { without } from 'lodash';

/**
 * Internal dependencies
 */
import { getAdminSetting } from '~/utils/admin-settings';

/**
 * Gets the country code from a country:state value string.
 *
 * @param {string} countryState Country state string, e.g. US:GA.
 * @return {string} Country string.
 */

export function getCountryCode( countryState = '' ) {
	if ( ! countryState ) {
		return null;
	}

	return countryState.split( ':' )[ 0 ];
}

export function getCurrencyRegion( countryState ) {
	let region = getCountryCode( countryState );
	const euCountries = without(
		getAdminSetting( 'onboarding', { euCountries: [] } ).euCountries,
		'GB'
	);
	if ( euCountries.includes( region ) ) {
		region = 'EU';
	}

	return region;
}

/**
 * Gets the product IDs for items based on the product types and theme selected in the onboarding profiler.
 *
 * @param {Object}  productTypes          Product Types.
 * @param {Object}  profileItems          Onboarding profile.
 * @param {boolean} includeInstalledItems Include installed items in returned product IDs.
 * @param {Array}   installedPlugins      Installed plugins.
 * @return {Array} Product Ids.
 */
export function getProductIdsForCart(
	productTypes,
	profileItems,
	includeInstalledItems = false,
	installedPlugins
) {
	const productList = getProductList(
		profileItems,
		includeInstalledItems,
		installedPlugins,
		productTypes
	);
	const productIds = productList.map(
		( product ) => product.id || product.product
	);
	return productIds;
}

/**
 * Gets the labeled/categorized product names and types for items based on the product types and theme selected in the onboarding profiler.
 *
 * @param {Object} productTypes     Product Types.
 * @param {Object} profileItems     Onboarding profile.
 * @param {Array}  installedPlugins Installed plugins.
 * @return {Array} Objects with labeled/categorized product names and types.
 */
export function getCategorizedOnboardingProducts(
	productTypes,
	profileItems,
	installedPlugins
) {
	const productList = {};
	productList.products = getProductList(
		profileItems,
		true,
		installedPlugins,
		productTypes
	);
	productList.remainingProducts = getProductList(
		profileItems,
		false,
		installedPlugins,
		productTypes
	);

	const uniqueItemsList = [
		...new Set( [
			...productList.products,
			...productList.remainingProducts,
		] ),
	];

	productList.uniqueItemsList = uniqueItemsList.map( ( product ) => {
		let cleanedProduct;
		if ( product.label ) {
			cleanedProduct = { type: 'extension', name: product.label };
		} else {
			cleanedProduct = { type: 'theme', name: product.title };
		}
		return cleanedProduct;
	} );

	return productList;
}

/**
 * Gets a product list for items based on the product types and theme selected in the onboarding profiler.
 *
 * @param {Object}  profileItems          Onboarding profile.
 * @param {boolean} includeInstalledItems Include installed items in returned product list.
 * @param {Array}   installedPlugins      Installed plugins.
 * @param {Object}  productTypes          Product Types.
 * @return {Array} Products.
 */
export function getProductList(
	profileItems,
	includeInstalledItems = false,
	installedPlugins,
	productTypes
) {
	const productList = [];

	if ( ! productTypes ) {
		return productList;
	}

	const profileItemsProductTypes = profileItems.product_types || [];

	profileItemsProductTypes.forEach( ( productType ) => {
		if (
			productTypes[ productType ] &&
			productTypes[ productType ].product &&
			( includeInstalledItems ||
				! installedPlugins.includes(
					productTypes[ productType ].slug
				) )
		) {
			productList.push( productTypes[ productType ] );
		}
	} );

	return productList;
}

/**
 * Get the value of a price from a string, removing any non-numeric characters.
 *
 * @param {string} string Price string.
 * @return {number} Number value.
 */
export function getPriceValue( string ) {
	return Number( decodeEntities( string ).replace( /[^0-9.-]+/g, '' ) );
}
