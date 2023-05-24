/**
 * External dependencies
 */
import { decodeEntities } from '@wordpress/html-entities';
import { without } from 'lodash';
import {
	OnboardingProductType,
	OnboardingProductTypes,
	ProfileItems,
} from '@woocommerce/data';

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

export function getCurrencyRegion( countryState: string ) {
	let region = getCountryCode( countryState );
	const euCountries = without(
		getAdminSetting( 'onboarding', { euCountries: [] } ).euCountries,
		'GB'
	);
	if ( region !== null && euCountries.includes( region ) ) {
		region = 'EU';
	}

	return region;
}

/**
 * Get the value of a price from a string, removing any non-numeric characters.
 *
 * @param {string} string Price string.
 * @return {number} Number value.
 */
export function getPriceValue( string: string ) {
	return Number( decodeEntities( string ).replace( /[^0-9.-]+/g, '' ) );
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
	profileItems: ProfileItems,
	includeInstalledItems = false,
	installedPlugins: string[],
	productTypes: OnboardingProductTypes
) {
	const productList: OnboardingProductType[] = [];

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
					productTypes[ productType ].slug as string
				) )
		) {
			productList.push( productTypes[ productType ] );
		}
	} );

	return productList;
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
	productTypes: OnboardingProductTypes,
	profileItems: ProfileItems,
	includeInstalledItems = false,
	installedPlugins: string[]
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
	productTypes: OnboardingProductTypes,
	profileItems: ProfileItems,
	installedPlugins: string[]
) {
	const products = getProductList(
		profileItems,
		true,
		installedPlugins,
		productTypes
	);
	const remainingProducts = getProductList(
		profileItems,
		false,
		installedPlugins,
		productTypes
	);

	const productSets = [ ...new Set( [ ...products, ...remainingProducts ] ) ];
	const uniqueItemsList = productSets.map( ( product ) => {
		let cleanedProduct;
		if ( product.label ) {
			cleanedProduct = { type: 'extension', name: product.label };
		} else {
			cleanedProduct = { type: 'theme', name: product.title };
		}
		return cleanedProduct;
	} );

	return {
		products,
		remainingProducts,
		uniqueItemsList,
	};
}
