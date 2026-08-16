/**
 * External dependencies
 */
import {
	getBlockVariations,
	registerBlockVariation,
	type BlockVariation,
	unregisterBlockVariation,
} from '@wordpress/blocks';
import { removeFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import bestSellers from '../best-sellers';
import byBrand from '../by-brand';
import byCategory from '../by-category';
import byTag from '../by-tag';
import cartContents from '../cart-contents';
import crossSells from '../cross-sells';
import featured from '../featured';
import handPicked from '../hand-picked';
import { registerCollections, registerEmailCollections } from '../index';
import newArrivals from '../new-arrivals';
import onSale from '../on-sale';
import productCatalog from '../product-collection';
import related from '../related';
import topRated from '../top-rated';
import upsells from '../upsells';
import {
	DEFAULT_ATTRIBUTES,
	DEFAULT_QUERY,
	INNER_BLOCKS_NO_RESULTS_TEMPLATE,
	INNER_BLOCKS_PAGINATION_TEMPLATE,
	INNER_BLOCKS_PRODUCT_TEMPLATE,
	INNER_BLOCKS_TEMPLATE,
	PRODUCT_COLLECTION_BLOCK_NAME,
} from '../../constants';
import { CoreCollectionNames, CoreFilterNames } from '../../types';

const productGrid = {
	type: 'flex',
	columns: 5,
	shrinkColumns: true,
};

const linkedProductGrid = {
	type: 'flex',
	columns: 4,
	shrinkColumns: true,
};

const chooserScope = [ 'inserter', 'block' ];

const heading = ( content: string, textAlign = 'center' ) => [
	'core/heading',
	{
		textAlign,
		level: 2,
		content,
		style: { spacing: { margin: { bottom: '1rem' } } },
	},
];

const collectionRows = [
	{
		caseName: 'Product Catalog',
		definition: productCatalog,
		name: CoreCollectionNames.PRODUCT_CATALOG,
		scope: [],
		query: DEFAULT_QUERY,
		displayLayout: DEFAULT_ATTRIBUTES.displayLayout,
		hideControls: [ CoreFilterNames.INHERIT ],
		usesReference: undefined,
		innerBlocks: INNER_BLOCKS_TEMPLATE,
	},
	{
		caseName: 'Featured Products',
		definition: featured,
		name: CoreCollectionNames.FEATURED,
		scope: chooserScope,
		query: { ...DEFAULT_QUERY, featured: true, perPage: 5, pages: 1 },
		displayLayout: productGrid,
		hideControls: [
			CoreFilterNames.INHERIT,
			CoreFilterNames.FEATURED,
			CoreFilterNames.FILTERABLE,
		],
		usesReference: undefined,
		innerBlocks: [
			heading( 'Featured products' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
		],
	},
	{
		caseName: 'New Arrivals',
		definition: newArrivals,
		name: CoreCollectionNames.NEW_ARRIVALS,
		scope: chooserScope,
		query: {
			...DEFAULT_QUERY,
			orderBy: 'date',
			order: 'desc',
			perPage: 5,
			pages: 1,
			timeFrame: { operator: 'in', value: '-7 days' },
		},
		displayLayout: productGrid,
		hideControls: [
			CoreFilterNames.INHERIT,
			CoreFilterNames.ORDER,
			CoreFilterNames.FILTERABLE,
		],
		usesReference: undefined,
		innerBlocks: [
			heading( 'New arrivals' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
		],
	},
	{
		caseName: 'On Sale Products',
		definition: onSale,
		name: CoreCollectionNames.ON_SALE,
		scope: chooserScope,
		query: {
			...DEFAULT_QUERY,
			woocommerceOnSale: true,
			perPage: 5,
			pages: 1,
		},
		displayLayout: productGrid,
		hideControls: [
			CoreFilterNames.INHERIT,
			CoreFilterNames.ON_SALE,
			CoreFilterNames.FILTERABLE,
		],
		usesReference: undefined,
		innerBlocks: [
			heading( 'On sale products' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
		],
	},
	{
		caseName: 'Best Sellers',
		definition: bestSellers,
		name: CoreCollectionNames.BEST_SELLERS,
		scope: chooserScope,
		query: {
			...DEFAULT_QUERY,
			orderBy: 'popularity',
			order: 'desc',
			perPage: 5,
			pages: 1,
		},
		displayLayout: productGrid,
		hideControls: [
			CoreFilterNames.INHERIT,
			CoreFilterNames.ORDER,
			CoreFilterNames.FILTERABLE,
		],
		usesReference: undefined,
		innerBlocks: [
			heading( 'Best selling products' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
		],
	},
	{
		caseName: 'Top Rated Products',
		definition: topRated,
		name: CoreCollectionNames.TOP_RATED,
		scope: chooserScope,
		query: {
			...DEFAULT_QUERY,
			orderBy: 'rating',
			order: 'desc',
			perPage: 5,
			pages: 1,
		},
		displayLayout: productGrid,
		hideControls: [
			CoreFilterNames.INHERIT,
			CoreFilterNames.ORDER,
			CoreFilterNames.FILTERABLE,
		],
		usesReference: undefined,
		innerBlocks: [
			heading( 'Top rated products' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
		],
	},
	{
		caseName: 'Hand-Picked Products',
		definition: handPicked,
		name: CoreCollectionNames.HAND_PICKED,
		scope: chooserScope,
		query: { ...DEFAULT_QUERY, orderBy: 'post__in' },
		displayLayout: productGrid,
		hideControls: [
			CoreFilterNames.INHERIT,
			CoreFilterNames.HAND_PICKED,
			CoreFilterNames.FILTERABLE,
			CoreFilterNames.ORDER,
		],
		usesReference: undefined,
		innerBlocks: [
			heading( 'Recommended products' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
			INNER_BLOCKS_PAGINATION_TEMPLATE,
		],
	},
	{
		caseName: 'Products by Category',
		definition: byCategory,
		name: CoreCollectionNames.BY_CATEGORY,
		scope: chooserScope,
		query: DEFAULT_QUERY,
		displayLayout: productGrid,
		hideControls: [
			CoreFilterNames.INHERIT,
			CoreFilterNames.HAND_PICKED,
			CoreFilterNames.FILTERABLE,
		],
		usesReference: undefined,
		innerBlocks: [
			heading( 'Products by Category' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
			INNER_BLOCKS_PAGINATION_TEMPLATE,
		],
	},
	{
		caseName: 'Products by Tag',
		definition: byTag,
		name: CoreCollectionNames.BY_TAG,
		scope: chooserScope,
		query: DEFAULT_QUERY,
		displayLayout: productGrid,
		hideControls: [
			CoreFilterNames.INHERIT,
			CoreFilterNames.HAND_PICKED,
			CoreFilterNames.FILTERABLE,
		],
		usesReference: undefined,
		innerBlocks: [
			heading( 'Products by Tag' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
			INNER_BLOCKS_PAGINATION_TEMPLATE,
		],
	},
	{
		caseName: 'Products by Brand',
		definition: byBrand,
		name: CoreCollectionNames.BY_BRAND,
		scope: chooserScope,
		query: DEFAULT_QUERY,
		displayLayout: productGrid,
		hideControls: [
			CoreFilterNames.INHERIT,
			CoreFilterNames.HAND_PICKED,
			CoreFilterNames.FILTERABLE,
		],
		usesReference: undefined,
		innerBlocks: [
			heading( 'Products by Brand' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
			INNER_BLOCKS_PAGINATION_TEMPLATE,
		],
	},
	{
		caseName: 'Related Products',
		definition: related,
		name: CoreCollectionNames.RELATED,
		scope: chooserScope,
		query: { ...DEFAULT_QUERY, perPage: 4, pages: 1 },
		displayLayout: linkedProductGrid,
		hideControls: [ CoreFilterNames.INHERIT ],
		usesReference: [ 'product' ],
		innerBlocks: [
			heading( 'Related Products' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
		],
	},
	{
		caseName: 'Upsells',
		definition: upsells,
		name: CoreCollectionNames.UPSELLS,
		scope: chooserScope,
		query: { ...DEFAULT_QUERY, perPage: 8, pages: 1 },
		displayLayout: linkedProductGrid,
		hideControls: [ CoreFilterNames.INHERIT, CoreFilterNames.FILTERABLE ],
		usesReference: [ 'product', 'cart', 'order' ],
		innerBlocks: [
			heading( 'You may also like', 'left' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
		],
	},
	{
		caseName: 'Cross-Sells',
		definition: crossSells,
		name: CoreCollectionNames.CROSS_SELLS,
		scope: chooserScope,
		query: { ...DEFAULT_QUERY, perPage: 8, pages: 1 },
		displayLayout: linkedProductGrid,
		hideControls: [ CoreFilterNames.INHERIT, CoreFilterNames.FILTERABLE ],
		usesReference: [ 'product', 'cart', 'order' ],
		innerBlocks: [
			heading( 'You may be interested in…', 'left' ),
			INNER_BLOCKS_PRODUCT_TEMPLATE,
		],
	},
] as const;

const emailRows = [
	{
		caseName: 'Cart Contents',
		definition: cartContents,
		name: CoreCollectionNames.CART_CONTENTS,
		scope: chooserScope,
		query: {
			...DEFAULT_QUERY,
			inherit: false,
			perPage: 10,
			pages: 1,
		},
		displayLayout: {
			type: 'flex',
			columns: 1,
			shrinkColumns: true,
		},
		hideControls: [
			CoreFilterNames.INHERIT,
			CoreFilterNames.ATTRIBUTES,
			CoreFilterNames.KEYWORD,
			CoreFilterNames.ORDER,
			CoreFilterNames.DEFAULT_ORDER,
			CoreFilterNames.FEATURED,
			CoreFilterNames.ON_SALE,
			CoreFilterNames.STOCK_STATUS,
			CoreFilterNames.HAND_PICKED,
			CoreFilterNames.TAXONOMY,
			CoreFilterNames.FILTERABLE,
			CoreFilterNames.CREATED,
			CoreFilterNames.PRICE_RANGE,
		],
		usesReference: undefined,
		innerBlocks: [ heading( 'Your Cart' ), INNER_BLOCKS_PRODUCT_TEMPLATE ],
	},
] as const;

const allRows = [ ...collectionRows, ...emailRows ];
const originalWpDescriptor = Object.getOwnPropertyDescriptor( window, 'wp' );

const getRegisteredCollection = ( name: string ): BlockVariation => {
	const variation = getBlockVariations( PRODUCT_COLLECTION_BLOCK_NAME )?.find(
		( candidate ) => candidate.name === name
	);

	if ( ! variation ) {
		throw new Error( `Expected registered collection "${ name }".` );
	}

	return variation;
};

beforeAll( () => {
	Object.defineProperty( window, 'wp', {
		configurable: true,
		writable: true,
		value: {
			...window.wp,
			blocks: {
				...window.wp?.blocks,
				registerBlockVariation,
			},
		},
	} );

	registerCollections();
	registerEmailCollections();
} );

afterAll( () => {
	for ( const { name } of allRows ) {
		unregisterBlockVariation( PRODUCT_COLLECTION_BLOCK_NAME, name );
		removeFilter( 'editor.BlockEdit', name );
	}

	if ( originalWpDescriptor ) {
		Object.defineProperty( window, 'wp', originalWpDescriptor );
	} else {
		Reflect.deleteProperty( window, 'wp' );
	}
} );

describe( 'Product Collection built-in collection contracts', () => {
	it( 'registers chooser and email collections in their declared order', () => {
		expect(
			getBlockVariations( PRODUCT_COLLECTION_BLOCK_NAME )?.map(
				( variation ) => variation.name
			)
		).toEqual( allRows.map( ( row ) => row.name ) );
	} );

	it.each( allRows )(
		'declares $caseName collection contract',
		( {
			caseName,
			definition,
			name,
			scope,
			query,
			displayLayout,
			hideControls,
			usesReference,
			innerBlocks,
		} ) => {
			const variation = getRegisteredCollection( name );

			expect( variation.title ).toBe( caseName );
			expect( variation.scope ).toEqual( scope );
			expect( variation.attributes?.collection ).toBe( name );
			expect( variation.attributes?.query ).toEqual( query );
			expect( variation.attributes?.displayLayout ).toEqual(
				displayLayout
			);
			expect( variation.attributes?.hideControls ).toEqual(
				hideControls
			);
			expect( variation.innerBlocks ).toEqual( innerBlocks );
			expect(
				'usesReference' in definition
					? definition.usesReference
					: undefined
			).toEqual( usesReference );
		}
	);

	it( 'keeps the default no-results block in the catalog template', () => {
		expect(
			getRegisteredCollection( CoreCollectionNames.PRODUCT_CATALOG )
		).toHaveProperty( 'innerBlocks', [
			INNER_BLOCKS_PRODUCT_TEMPLATE,
			INNER_BLOCKS_PAGINATION_TEMPLATE,
			INNER_BLOCKS_NO_RESULTS_TEMPLATE,
		] );
	} );
} );
