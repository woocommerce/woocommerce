/**
 * External dependencies
 */
import { act, renderHook, waitFor } from '@testing-library/react';
import {
	createBlock,
	getBlockType,
	registerBlockType,
	unregisterBlockType,
} from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as coreStore } from '@wordpress/core-data';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	LocationType,
	useGetLocation,
	useProductCollectionQueryContext,
} from '../utils';

const requiredBlockTypes = [
	'core/paragraph',
	'woocommerce/product-collection',
	'woocommerce/product-template',
	'woocommerce/single-product',
];
const registeredBlockTypes: string[] = [];

beforeAll( () => {
	requiredBlockTypes.forEach( ( name ) => {
		if ( getBlockType( name ) ) {
			return;
		}

		registerBlockType( name, {
			apiVersion: 3,
			title: name,
			category: 'widgets',
			attributes:
				name === 'woocommerce/product-collection'
					? {
							collection: { type: 'string' },
							forcePageReload: {
								type: 'boolean',
								default: false,
							},
					  }
					: {},
			edit: () => null,
			save: () => null,
		} );
		registeredBlockTypes.push( name );
	} );
} );

afterAll( () => {
	registeredBlockTypes.forEach( ( name ) => unregisterBlockType( name ) );
} );

const primeEntityResolution = (
	kind: 'postType' | 'taxonomy',
	name: 'product' | 'product_cat' | 'product_tag',
	slug: string,
	records: { id: number; slug: string }[]
) => {
	const query = {
		_fields: [ 'id' ],
		slug,
	};
	const actions = dispatch( coreStore );

	actions.addEntities( [
		{
			baseURL: `/wp/v2/${ name }`,
			kind,
			name,
		},
	] );
	actions.receiveEntityRecords( kind, name, records, query );
	actions.finishResolution( 'getEntityRecords', [ kind, name, query ] );
};

describe( 'useGetLocation', () => {
	beforeEach( () => {
		act( () => {
			dispatch( blockEditorStore ).resetBlocks( [] );
		} );
	} );

	afterEach( () => {
		act( () => {
			dispatch( blockEditorStore ).resetBlocks( [] );
		} );
	} );

	it( 'resolves a product template slug into a numeric product location', async () => {
		primeEntityResolution( 'postType', 'product', 'cap', [
			{ id: 71, slug: 'cap' },
		] );

		const { result } = renderHook( () =>
			useGetLocation(
				{ templateSlug: 'single-product-cap' },
				'standalone-product-template'
			)
		);

		expect( result.current ).toEqual( {
			type: LocationType.Product,
			sourceData: { productId: null },
		} );
		await waitFor( () =>
			expect( result.current ).toEqual( {
				type: LocationType.Product,
				sourceData: { productId: 71 },
			} )
		);
	} );

	it( 'resolves category location into taxonomy request', async () => {
		primeEntityResolution( 'taxonomy', 'product_cat', 'hoodies', [
			{ id: 81, slug: 'hoodies' },
		] );

		const { result } = renderHook( () =>
			useGetLocation(
				{ templateSlug: 'taxonomy-product_cat-hoodies' },
				'category-product-template'
			)
		);

		expect( result.current ).toEqual( {
			type: LocationType.Archive,
			sourceData: { taxonomy: 'product_cat', termId: null },
		} );
		await waitFor( () =>
			expect( result.current ).toEqual( {
				type: LocationType.Archive,
				sourceData: { taxonomy: 'product_cat', termId: 81 },
			} )
		);
	} );

	it( 'resolves a tag template slug into a numeric taxonomy location', async () => {
		primeEntityResolution( 'taxonomy', 'product_tag', 'recommended', [
			{ id: 91, slug: 'recommended' },
		] );

		const { result } = renderHook( () =>
			useGetLocation(
				{ templateSlug: 'taxonomy-product_tag-recommended' },
				'tag-product-template'
			)
		);

		await waitFor( () =>
			expect( result.current ).toEqual( {
				type: LocationType.Archive,
				sourceData: { taxonomy: 'product_tag', termId: 91 },
			} )
		);
	} );

	it.each( [
		{
			caseName: 'a generic category template',
			context: { templateSlug: 'taxonomy-product_cat' },
			expected: {
				type: LocationType.Archive,
				sourceData: { taxonomy: 'product_cat', termId: null },
			},
		},
		{
			caseName: 'a generic tag template',
			context: { templateSlug: 'taxonomy-product_tag' },
			expected: {
				type: LocationType.Archive,
				sourceData: { taxonomy: 'product_tag', termId: null },
			},
		},
		{
			caseName: 'a generic product attribute template',
			context: { templateSlug: 'taxonomy-product_attribute' },
			expected: {
				type: LocationType.Archive,
				sourceData: { taxonomy: null, termId: null },
			},
		},
		{
			caseName: 'an ordinary post',
			context: { templateSlug: 'single' },
			expected: { type: LocationType.Site, sourceData: {} },
		},
	] )( 'returns the context for $caseName', ( { context, expected } ) => {
		const { result } = renderHook( () =>
			useGetLocation( context, 'generic-product-template' )
		);

		expect( result.current ).toEqual( expected );
	} );

	it( 'gives Single Product block context precedence over the template', () => {
		const productTemplate = createBlock( 'woocommerce/product-template' );
		const singleProduct = createBlock( 'woocommerce/single-product', {}, [
			productTemplate,
		] );
		act( () => {
			dispatch( blockEditorStore ).resetBlocks( [ singleProduct ] );
		} );

		const { result } = renderHook( () =>
			useGetLocation(
				{
					postId: 101,
					templateSlug: 'taxonomy-product_cat',
				},
				productTemplate.clientId
			)
		);

		expect( result.current ).toEqual( {
			type: LocationType.Product,
			sourceData: { productId: 101 },
		} );
	} );
} );

describe( 'useProductCollectionQueryContext', () => {
	afterEach( () => {
		act( () => {
			dispatch( blockEditorStore ).resetBlocks( [] );
		} );
	} );

	it( 'includes only requested truthy Product Collection attributes', () => {
		const productTemplate = createBlock( 'woocommerce/product-template' );
		const productCollection = createBlock(
			'woocommerce/product-collection',
			{
				collection: 'woocommerce/product-collection/on-sale',
				forcePageReload: false,
			},
			[ productTemplate ]
		);
		act( () => {
			dispatch( blockEditorStore ).resetBlocks( [ productCollection ] );
		} );

		const { result, rerender } = renderHook(
			( { includes } ) =>
				useProductCollectionQueryContext( {
					clientId: productTemplate.clientId,
					queryContextIncludes: includes,
				} ),
			{
				initialProps: {
					includes: [ 'collection' ],
				},
			}
		);

		expect( result.current ).toEqual( {
			collection: 'woocommerce/product-collection/on-sale',
		} );

		rerender( { includes: [ 'forcePageReload' ] } );
		expect( result.current ).toEqual( {} );

		rerender( { includes: [] } );
		expect( result.current ).toEqual( {} );
	} );

	it( 'returns null outside Product Collection', () => {
		const paragraph = createBlock( 'core/paragraph' );
		act( () => {
			dispatch( blockEditorStore ).resetBlocks( [ paragraph ] );
		} );

		const { result } = renderHook( () =>
			useProductCollectionQueryContext( {
				clientId: paragraph.clientId,
				queryContextIncludes: [ 'collection' ],
			} )
		);

		expect( result.current ).toBeNull();
	} );
} );
