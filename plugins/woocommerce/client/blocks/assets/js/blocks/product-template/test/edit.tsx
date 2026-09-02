/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { store as blockEditorStore } from '@wordpress/block-editor';
import {
	createBlock,
	getBlockType,
	registerBlockType,
	unregisterBlockType,
} from '@wordpress/blocks';
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import ProductTemplateEdit from '../edit';
import { LocationType } from '../utils';

type EntityQuery = Record< string, unknown >;
const noProducts: never[] = [];
const noTaxonomies: never[] = [];
const resolvedTaxonomyRecords = {
	product_cat: { id: 81, slug: 'hoodies' },
	product_tag: { id: 91, slug: 'recommended' },
} as const;
const requiredBlockTypes = [
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
			edit: () => null,
			save: () => null,
		} );
		registeredBlockTypes.push( name );
	} );
} );

afterAll( () => {
	registeredBlockTypes.forEach( ( name ) => unregisterBlockType( name ) );
} );

const createProps = ( {
	clientId = 'product-template-client-id',
	inherit = false,
	postId,
	templateSlug = '',
}: {
	clientId?: string;
	inherit?: boolean;
	postId?: number;
	templateSlug?: string;
} = {} ) => ( {
	attributes: {},
	clientId,
	context: {
		__privateProductCollectionPreviewState: undefined,
		displayLayout: {
			columns: 3,
			shrinkColumns: false,
			type: 'flex',
		},
		query: {
			exclude: [],
			inherit,
			offset: 0,
			order: 'asc',
			orderBy: 'menu_order',
			pages: 0,
			perPage: 4,
			search: '',
			taxQuery: {},
		},
		queryContext: [ { page: 1 } ],
		queryContextIncludes: [],
		postId,
		templateSlug,
	},
	insertBlocksAfter: jest.fn(),
	isSelected: false,
	name: 'woocommerce/product-template',
	onReplace: jest.fn(),
	setAttributes: jest.fn(),
	__unstableLayoutClassNames: '',
} );

const getProductQuery = ( getEntityRecords: jest.SpyInstance ) => {
	const call = [ ...getEntityRecords.mock.calls ]
		.reverse()
		.find(
			( [ kind, name, query ] ) =>
				kind === 'postType' &&
				name === 'product' &&
				! ( query as EntityQuery ).slug
		);

	expect( call ).toBeDefined();
	return call?.[ 2 ] as EntityQuery;
};

describe( 'ProductTemplateEdit request context', () => {
	let getEntityRecords: jest.SpyInstance;
	let getTaxonomies: jest.SpyInstance;
	let getEditedEntityRecord: jest.SpyInstance;

	beforeEach( () => {
		act( () => {
			dispatch( blockEditorStore ).resetBlocks( [] );
		} );

		const coreSelectors = select( coreStore );
		const selectEntityRecords =
			coreSelectors.getEntityRecords.bind( coreSelectors );

		getEntityRecords = jest
			.spyOn( coreSelectors, 'getEntityRecords' )
			.mockImplementation( ( kind, name, query ) => {
				if ( kind === 'taxonomy' ) {
					const record =
						resolvedTaxonomyRecords[
							name as keyof typeof resolvedTaxonomyRecords
						];
					if ( record?.slug === ( query as EntityQuery ).slug ) {
						return [ record ];
					}
				}

				if (
					kind === 'postType' &&
					name === 'product' &&
					! ( query as EntityQuery ).slug
				) {
					return noProducts;
				}

				return selectEntityRecords( kind, name, query );
			} );
		getTaxonomies = jest
			.spyOn( coreSelectors, 'getTaxonomies' )
			.mockReturnValue( noTaxonomies );
		getEditedEntityRecord = jest
			.spyOn( coreSelectors, 'getEditedEntityRecord' )
			.mockReturnValue( {
				woocommerce_default_catalog_orderby: 'price-desc',
			} );
	} );

	afterEach( () => {
		getEntityRecords.mockRestore();
		getTaxonomies.mockRestore();
		getEditedEntityRecord.mockRestore();
		act( () => {
			dispatch( blockEditorStore ).resetBlocks( [] );
		} );
	} );

	it( 'adds only the product ID for a product location', () => {
		const productTemplate = createBlock( 'woocommerce/product-template' );
		const singleProduct = createBlock( 'woocommerce/single-product', {}, [
			productTemplate,
		] );
		act( () => {
			dispatch( blockEditorStore ).resetBlocks( [ singleProduct ] );
		} );

		render(
			<ProductTemplateEdit
				{ ...createProps( {
					clientId: productTemplate.clientId,
					postId: 101,
					templateSlug: 'taxonomy-product_cat',
				} ) }
			/>
		);

		expect( getProductQuery( getEntityRecords ) ).toMatchObject( {
			productCollectionLocation: {
				sourceData: { productId: 101 },
				type: LocationType.Product,
			},
		} );
		const sourceData = (
			getProductQuery( getEntityRecords ).productCollectionLocation as {
				sourceData: EntityQuery;
			}
		 ).sourceData;
		expect( sourceData ).not.toHaveProperty( 'taxonomy' );
		expect( sourceData ).not.toHaveProperty( 'termId' );
	} );

	it( 'adds only taxonomy fields for an archive location', () => {
		render(
			<ProductTemplateEdit
				{ ...createProps( {
					templateSlug: 'taxonomy-product_cat',
				} ) }
			/>
		);

		expect( getProductQuery( getEntityRecords ) ).toMatchObject( {
			productCollectionLocation: {
				sourceData: {
					taxonomy: 'product_cat',
					termId: null,
				},
				type: LocationType.Archive,
			},
		} );
		const sourceData = (
			getProductQuery( getEntityRecords ).productCollectionLocation as {
				sourceData: EntityQuery;
			}
		 ).sourceData;
		expect( sourceData ).not.toHaveProperty( 'productId' );
	} );

	it.each( [
		{
			taxonomy: 'product_cat',
			termId: 81,
			templateSlug: 'taxonomy-product_cat-hoodies',
			wrongTaxonomy: 'product_tag',
		},
		{
			taxonomy: 'product_tag',
			termId: 91,
			templateSlug: 'taxonomy-product_tag-recommended',
			wrongTaxonomy: 'product_cat',
		},
	] )(
		'adds the resolved $taxonomy ID to an inherited product request',
		( { taxonomy, termId, templateSlug, wrongTaxonomy } ) => {
			render(
				<ProductTemplateEdit
					{ ...createProps( {
						inherit: true,
						templateSlug,
					} ) }
				/>
			);

			const request = getProductQuery( getEntityRecords );
			expect( request[ taxonomy ] ).toBe( termId );
			expect( request ).not.toHaveProperty( wrongTaxonomy );
		}
	);

	it( 'does not add source fields for a site location', () => {
		render( <ProductTemplateEdit { ...createProps() } /> );

		const location = getProductQuery( getEntityRecords )
			.productCollectionLocation as {
			sourceData: EntityQuery;
			type: LocationType;
		};

		expect( location ).toEqual( {
			sourceData: {},
			type: LocationType.Site,
		} );
		expect( location.sourceData ).toEqual( {} );
		expect( location.sourceData ).not.toHaveProperty( 'productId' );
		expect( location.sourceData ).not.toHaveProperty( 'taxonomy' );
		expect( location.sourceData ).not.toHaveProperty( 'termId' );
	} );

	it( 'uses the site default catalog order for inherited requests', () => {
		render(
			<ProductTemplateEdit
				{ ...createProps( {
					inherit: true,
					templateSlug: 'archive-product',
				} ) }
			/>
		);

		expect( getProductQuery( getEntityRecords ) ).toMatchObject( {
			order: 'desc',
			orderby: 'price',
		} );
	} );
} );
