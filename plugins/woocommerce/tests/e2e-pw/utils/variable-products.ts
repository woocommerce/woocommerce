/**
 * External dependencies
 */
import type { Browser, Page } from '@playwright/test';

/**
 * Internal dependencies
 */
import * as api from './api';

/**
 * Declare window.wp type for WordPress data API.
 */
declare global {
	interface Window {
		wp: {
			data: {
				select: ( store: string ) => {
					getCurrentUser: () => {
						id: number;
						woocommerce_meta: Record< string, string >;
					};
				};
				dispatch: ( store: string ) => {
					saveUser: ( data: {
						id: number;
						woocommerce_meta: Record< string, string >;
					} ) => Promise< void >;
				};
			};
		};
	}
}

/**
 * Product attribute interface.
 */
export interface ProductAttribute {
	name: string;
	visible: boolean;
	variation: boolean;
	options: string[];
}

/**
 * Variation attribute interface.
 */
interface VariationAttribute {
	name: string;
	option: string;
}

/**
 * Variation interface for creating variations.
 */
export interface Variation {
	regular_price: string;
	attributes: VariationAttribute[];
	[ key: string ]: unknown;
}

/**
 * Array to hold all product ID's to be deleted after test.
 */
const productIds: number[] = [];

/**
 * The `attributes` property to be used in the request payload for creating a variable product through the REST API.
 *
 * @see {@link [Product - Attributes properties](https://woocommerce.github.io/woocommerce-rest-api-docs/#product-attributes-properties)}
 */
export const productAttributes: ProductAttribute[] = [
	{
		name: 'Colour',
		visible: true,
		variation: true,
		options: [ 'Red', 'Green' ],
	},
	{
		name: 'Size',
		visible: true,
		variation: true,
		options: [ 'Small', 'Medium' ],
	},
	{
		name: 'Logo',
		visible: true,
		variation: true,
		options: [ 'Woo', 'WordPress' ],
	},
];

/**
 * Request payload for creating variations.
 *
 * @see {@link [Product variation properties](https://woocommerce.github.io/woocommerce-rest-api-docs/#product-variation-properties)}
 */
export const sampleVariations: Variation[] = [
	{
		regular_price: '9.99',
		attributes: [
			{
				name: 'Colour',
				option: 'Red',
			},
			{
				name: 'Size',
				option: 'Small',
			},
			{
				name: 'Logo',
				option: 'Woo',
			},
		],
	},
	{
		regular_price: '10.99',
		attributes: [
			{
				name: 'Colour',
				option: 'Red',
			},
			{
				name: 'Size',
				option: 'Small',
			},
			{
				name: 'Logo',
				option: 'WordPress',
			},
		],
	},
	{
		regular_price: '11.99',
		attributes: [
			{
				name: 'Colour',
				option: 'Red',
			},
			{
				name: 'Size',
				option: 'Medium',
			},
			{
				name: 'Logo',
				option: 'Woo',
			},
		],
	},
];

/**
 * Create a variable product using the WooCommerce REST API.
 *
 * @param attributes - List of attributes. See [Product - Attributes properties](https://woocommerce.github.io/woocommerce-rest-api-docs/#product-attributes-properties).
 * @return ID of the created variable product
 */
export async function createVariableProduct(
	attributes: ProductAttribute[] = []
): Promise< number > {
	const randomNum = Math.floor( Math.random() * 1000 );
	const payload = {
		name: `Unbranded Granite Shirt ${ randomNum }`,
		type: 'variable',
		attributes,
	};

	const productId = await api.create.product( payload );

	productIds.push( productId );

	return productId;
}

/**
 * Clean up all products created by the test.
 */
export async function deleteProductsAddedByTests(): Promise< void > {
	await api.deletePost.products( productIds );
}

/**
 * Enable or disable the variable product tour through JavaScript.
 *
 * @param browser - The Playwright browser.
 * @param show    - Whether to show the variable product tour or not.
 */
export async function showVariableProductTour(
	browser: Browser,
	show: boolean
): Promise< void > {
	const productPageURL = 'wp-admin/post-new.php?post_type=product';
	const addProductPage: Page = await browser.newPage();

	// Go to "Add new product" page
	await addProductPage.goto( productPageURL );

	// Get the current user's ID and user preferences
	const { id: userId, woocommerce_meta } = await addProductPage.evaluate(
		() => {
			return window.wp.data.select( 'core' ).getCurrentUser();
		}
	);

	// Turn off the variable product tour
	const updatedWooCommerceMeta = {
		...woocommerce_meta,
		variable_product_tour_shown: show ? '' : '"yes"',
	};

	// Save the updated user preferences
	await addProductPage.evaluate(
		// eslint-disable-next-line @typescript-eslint/no-shadow
		async ( { userId, updatedWooCommerceMeta } ) => {
			await window.wp.data.dispatch( 'core' ).saveUser( {
				id: userId,
				woocommerce_meta: updatedWooCommerceMeta,
			} );
		},
		{ userId, updatedWooCommerceMeta }
	);

	// Close the page
	await addProductPage.close();
}

/**
 * Generate all possible variations from the given attributes.
 *
 * @param attributes - The product attributes.
 * @return All possible variations from the given attributes
 */
export function generateVariationsFromAttributes(
	attributes: ProductAttribute[]
): string[][] {
	const combine = (
		runningList: string[] | string[][],
		nextAttribute: string[]
	): string[][] => {
		const variations: string[][] = [];
		let newVar: string[];

		if ( ! Array.isArray( runningList[ 0 ] ) ) {
			runningList = [ runningList as string[] ];
		}

		for ( const partialVariation of runningList as string[][] ) {
			if ( ( runningList as string[][] ).length === 1 ) {
				for ( const startingAttribute of partialVariation ) {
					for ( const nextAttrValue of nextAttribute ) {
						newVar = [ startingAttribute, nextAttrValue ];
						variations.push( newVar );
					}
				}
			} else {
				for ( const nextAttrValue of nextAttribute ) {
					newVar = partialVariation.concat( [ nextAttrValue ] );
					variations.push( newVar );
				}
			}
		}

		return variations;
	};

	let allVariations: string[] | string[][] = attributes[ 0 ].options;

	for ( let i = 1; i < attributes.length; i++ ) {
		const nextAttribute = attributes[ i ].options;

		allVariations = combine( allVariations, nextAttribute );
	}

	return allVariations as string[][];
}

/**
 * Create variations through the WooCommerce REST API.
 *
 * @param productId  - Product ID to add variations to.
 * @param variations - List of variations to create.
 * @return Array of variation ID's created.
 */
export async function createVariations(
	productId: number,
	variations: Variation[]
): Promise< number[] > {
	return await api.create.productVariations( productId, variations );
}
