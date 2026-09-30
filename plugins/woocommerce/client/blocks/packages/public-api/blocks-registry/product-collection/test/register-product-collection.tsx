/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import {
	getBlockVariations,
	registerBlockVariation,
	store as blocksStore,
	unregisterBlockVariation,
} from '@wordpress/blocks';
import { store as coreDataStore } from '@wordpress/core-data';
import { createRegistry, RegistryProvider, select } from '@wordpress/data';
import { applyFilters, removeFilter } from '@wordpress/hooks';
import {
	__experimentalRegisterProductCollection,
	type ProductCollectionConfig,
} from '@woocommerce/blocks-registry';
import {
	ProductCollectionUIStatesInEditor,
	type ProductCollectionAttributes,
	type ProductCollectionEditComponentProps,
} from '@woocommerce/blocks/product-collection/types';
import { useProductCollectionUIState } from '@woocommerce/blocks/product-collection/utils';
import {
	LocationType,
	type WooCommerceBlockLocation,
} from '@woocommerce/blocks/product-template/utils';

const BLOCK_NAME = 'woocommerce/product-collection';
const TEST_NAMESPACE = 'test-plugin/product-collection';

type ProbeProps = ProductCollectionEditComponentProps & {
	location: WooCommerceBlockLocation;
};

const attemptedCollectionNames = new Set< string >();

const trackAttemptedCollectionName = ( config: unknown ) => {
	if (
		config &&
		typeof config === 'object' &&
		'name' in config &&
		typeof config.name === 'string'
	) {
		attemptedCollectionNames.add( config.name );
	}
};

const registerCollection = ( config: ProductCollectionConfig ) => {
	trackAttemptedCollectionName( config );
	__experimentalRegisterProductCollection( config );
};

const getRegisteredVariation = ( name: string ) => {
	const variation = getBlockVariations( BLOCK_NAME )?.find(
		( candidate ) => candidate.name === name
	);

	if ( ! variation ) {
		throw new Error( `Expected Product Collection variation "${ name }".` );
	}

	return variation;
};

const makeAttributes = ( collection: string, productReference?: number ) =>
	( {
		collection,
		query: productReference === undefined ? {} : { productReference },
	} ) as ProductCollectionAttributes;

const getRegisteredAttributes = ( name: string, productReference?: number ) => {
	const attributes = getRegisteredVariation( name )
		.attributes as ProductCollectionAttributes;
	const query = { ...attributes.query };
	if ( productReference !== undefined ) {
		query.productReference = productReference;
	}

	return {
		...attributes,
		query,
	};
};

const makeBlockEditProps = (
	attributes: ProductCollectionAttributes
): ProductCollectionEditComponentProps =>
	( {
		attributes,
		clientId: 'test-product-collection',
		context: {},
		isSelected: true,
		name: BLOCK_NAME,
		setAttributes: jest.fn(),
	} ) as ProductCollectionEditComponentProps;

const StateProbe = ( { attributes, location, usesReference }: ProbeProps ) => {
	const { productCollectionUIStateInEditor, isLoading } =
		useProductCollectionUIState( {
			attributes,
			hasInnerBlocks: true,
			location,
			usesReference,
		} );

	return (
		<output data-testid="collection-state">
			{ isLoading ? 'loading' : productCollectionUIStateInEditor }
		</output>
	);
};

const renderFilteredState = ( {
	attributes,
	location,
	registry,
}: {
	attributes: ProductCollectionAttributes;
	location: WooCommerceBlockLocation;
	registry: ReturnType< typeof createRegistry >;
} ) => {
	const FilteredStateProbe = applyFilters(
		'editor.BlockEdit',
		StateProbe
	) as typeof StateProbe;

	return render(
		<RegistryProvider value={ registry }>
			<FilteredStateProbe
				{ ...makeBlockEditProps( attributes ) }
				location={ location }
			/>
		</RegistryProvider>
	);
};

describe( '__experimentalRegisterProductCollection', () => {
	const originalWpDescriptor = Object.getOwnPropertyDescriptor(
		window,
		'wp'
	);

	beforeEach( () => {
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
	} );

	afterEach( () => {
		const registeredVariationNames = new Set(
			getBlockVariations( BLOCK_NAME )?.map(
				( variation ) => variation.name
			)
		);
		for ( const name of attemptedCollectionNames ) {
			if ( registeredVariationNames.has( name ) ) {
				unregisterBlockVariation( BLOCK_NAME, name );
			}
			if ( name ) {
				removeFilter( 'editor.BlockEdit', name );
			}
		}
		attemptedCollectionNames.clear();

		if ( originalWpDescriptor ) {
			Object.defineProperty( window, 'wp', originalWpDescriptor );
		} else {
			Reflect.deleteProperty( window, 'wp' );
		}
	} );

	it.each( [
		{ caseName: 'a null configuration', config: null },
		{ caseName: 'an empty configuration', config: {} },
		{
			caseName: 'an empty name',
			config: { name: '', title: 'Missing name' },
		},
		{
			caseName: 'an empty title',
			config: {
				name: `${ TEST_NAMESPACE }/missing-title`,
				title: '',
			},
		},
		{
			caseName: 'a non-array usesReference value',
			config: {
				name: `${ TEST_NAMESPACE }/invalid-references`,
				title: 'Invalid references',
				usesReference: 'product',
			},
		},
	] )( 'does not register $caseName', ( { config } ) => {
		const variationsBefore = getBlockVariations( BLOCK_NAME ) || [];
		const variationNamesBefore = variationsBefore.map(
			( variation ) => variation.name
		);
		trackAttemptedCollectionName( config );

		__experimentalRegisterProductCollection(
			config as unknown as ProductCollectionConfig
		);

		const variationsAfter = getBlockVariations( BLOCK_NAME ) || [];
		expect( variationsAfter ).toHaveLength( variationsBefore.length );
		expect(
			variationsAfter.map( ( variation ) => variation.name )
		).toEqual( variationNamesBefore );
		expect( variationsAfter ).toEqual( variationsBefore );
		expect( console ).toHaveErrored();
	} );

	it( 'registers a complete variation while preserving supported falsy values and caller attributes', () => {
		const name = `${ TEST_NAMESPACE }/complete`;
		const innerBlocks: ProductCollectionConfig[ 'innerBlocks' ] = [
			[ 'core/paragraph', { placeholder: 'Collection content' } ],
		];

		registerCollection( {
			name,
			title: 'Complete collection',
			description: 'A complete public collection.',
			category: 'woocommerce',
			keywords: [ 'complete', 'collection' ],
			scope: [ 'block' ],
			innerBlocks,
			attributes: {
				align: 'wide',
				displayLayout: {
					columns: 4,
					shrinkColumns: false,
				},
				hideControls: [ 'inherit', 'keyword', 'inherit' ],
				inherit: true,
				query: {
					featured: false,
					inherit: true,
					offset: 0,
					pages: 0,
					perPage: 0,
					woocommerceOnSale: false,
				},
			},
		} );

		const variation = getRegisteredVariation( name );
		expect( variation ).toMatchObject( {
			name,
			title: 'Complete collection',
			description: 'A complete public collection.',
			category: 'woocommerce',
			keywords: [ 'complete', 'collection' ],
			scope: [ 'block' ],
			innerBlocks,
			isDefault: false,
			attributes: {
				align: 'wide',
				collection: name,
				dimensions: { widthType: 'fill' },
				displayLayout: {
					columns: 4,
					shrinkColumns: false,
					type: 'flex',
				},
				forcePageReload: false,
				hideControls: [ 'inherit', 'keyword' ],
				inherit: false,
				query: {
					exclude: [],
					featured: false,
					filterable: false,
					inherit: false,
					isProductCollectionBlock: true,
					offset: 0,
					order: 'asc',
					orderBy: 'title',
					pages: 0,
					perPage: 0,
					postType: 'product',
					priceRange: undefined,
					relatedBy: { categories: true, tags: true },
					search: '',
					taxQuery: {},
					timeFrame: undefined,
					woocommerceAttributes: [],
					woocommerceHandPickedProducts: [],
					woocommerceOnSale: false,
					woocommerceStockStatus: [],
				},
				queryContextIncludes: [ 'collection' ],
				tagName: 'div',
			},
		} );

		expect( typeof variation.isActive ).toBe( 'function' );
		const isActive = variation.isActive as (
			blockAttributes: Record< string, unknown >,
			variationAttributes: Record< string, unknown >
		) => boolean;
		expect( isActive( { collection: name }, { collection: name } ) ).toBe(
			true
		);
		expect(
			isActive(
				{ collection: `${ TEST_NAMESPACE }/other` },
				{ collection: name }
			)
		).toBe( false );
	} );

	it( 'injects preview and reference props only into the matching collection edit', () => {
		const name = `${ TEST_NAMESPACE }/wrapped`;
		const setPreviewState = jest.fn();
		registerCollection( {
			name,
			title: 'Wrapped collection',
			preview: {
				initialPreviewState: {
					isPreview: true,
					previewMessage: 'Preview message',
				},
				setPreviewState,
			},
			usesReference: [ 'product' ],
		} );

		const PropsProbe = ( {
			preview,
			usesReference,
		}: ProductCollectionEditComponentProps ) => (
			<output
				data-testid="additional-props"
				data-preview={ preview?.initialPreviewState?.previewMessage }
				data-references={ usesReference?.join( ',' ) }
				data-setter={
					preview?.setPreviewState === setPreviewState
						? 'matching-setter'
						: undefined
				}
			/>
		);
		const FilteredProbe = applyFilters(
			'editor.BlockEdit',
			PropsProbe
		) as typeof PropsProbe;

		const { getByTestId, rerender } = render(
			<FilteredProbe
				{ ...makeBlockEditProps( makeAttributes( name ) ) }
			/>
		);
		expect( getByTestId( 'additional-props' ) ).toHaveAttribute(
			'data-preview',
			'Preview message'
		);
		expect( getByTestId( 'additional-props' ) ).toHaveAttribute(
			'data-references',
			'product'
		);
		expect( getByTestId( 'additional-props' ) ).toHaveAttribute(
			'data-setter',
			'matching-setter'
		);

		rerender(
			<FilteredProbe
				{ ...makeBlockEditProps(
					makeAttributes( `${ TEST_NAMESPACE }/other` )
				) }
			/>
		);
		expect( getByTestId( 'additional-props' ) ).not.toHaveAttribute(
			'data-preview'
		);
		expect( getByTestId( 'additional-props' ) ).not.toHaveAttribute(
			'data-references'
		);
		expect( getByTestId( 'additional-props' ) ).not.toHaveAttribute(
			'data-setter'
		);
	} );

	it.each( [
		{
			id: 'product',
			matchingLocations: [ { type: LocationType.Product } ],
			usesReference: [ LocationType.Product ],
			expectedNonmatchingState:
				ProductCollectionUIStatesInEditor.PRODUCT_REFERENCE_PICKER,
		},
		{
			id: 'cart',
			matchingLocations: [ { type: LocationType.Cart } ],
			usesReference: [ LocationType.Cart ],
			expectedNonmatchingState: ProductCollectionUIStatesInEditor.VALID,
		},
		{
			id: 'order',
			matchingLocations: [ { type: LocationType.Order } ],
			usesReference: [ LocationType.Order ],
			expectedNonmatchingState: ProductCollectionUIStatesInEditor.VALID,
		},
		{
			id: 'archive',
			matchingLocations: [ { type: LocationType.Archive } ],
			usesReference: [ LocationType.Archive ],
			expectedNonmatchingState: ProductCollectionUIStatesInEditor.VALID,
		},
		{
			id: 'multiple',
			matchingLocations: [
				{ type: LocationType.Product },
				{ type: LocationType.Order },
			],
			usesReference: [ LocationType.Product, LocationType.Order ],
			expectedNonmatchingState:
				ProductCollectionUIStatesInEditor.PRODUCT_REFERENCE_PICKER,
		},
	] )(
		'routes $id reference contexts through the registered edit wrapper and real UI-state hook',
		( {
			id,
			matchingLocations,
			usesReference,
			expectedNonmatchingState,
		} ) => {
			const name = `${ TEST_NAMESPACE }/context-${ id }`;
			registerCollection( {
				name,
				title: `${ id } context collection`,
				usesReference,
			} );

			for ( const location of matchingLocations ) {
				const registry = createRegistry();
				registry.register( coreDataStore );
				const { getByTestId: getMatchingByTestId, unmount } =
					renderFilteredState( {
						attributes: getRegisteredAttributes( name ),
						location: location as WooCommerceBlockLocation,
						registry,
					} );
				expect(
					getMatchingByTestId( 'collection-state' )
				).toHaveTextContent(
					ProductCollectionUIStatesInEditor.VALID_WITH_PREVIEW
				);
				unmount();
			}

			const nonmatchingRegistry = createRegistry();
			nonmatchingRegistry.register( coreDataStore );
			const {
				getByTestId: getNonmatchingByTestId,
				unmount: unmountNonmatching,
			} = renderFilteredState( {
				attributes: getRegisteredAttributes( name ),
				location: { type: LocationType.Site },
				registry: nonmatchingRegistry,
			} );
			expect(
				getNonmatchingByTestId( 'collection-state' )
			).toHaveTextContent( expectedNonmatchingState );

			unmountNonmatching();
			const selectedProductRegistry = createRegistry();
			selectedProductRegistry.register( coreDataStore );
			selectedProductRegistry.dispatch( coreDataStore ).addEntities( [
				{
					baseURL: '/wc/v3/products',
					kind: 'postType',
					name: 'product',
				},
			] );
			selectedProductRegistry
				.dispatch( coreDataStore )
				.receiveEntityRecords( 'postType', 'product', [
					{ id: 73, status: 'publish' },
				] );
			selectedProductRegistry
				.dispatch( coreDataStore )
				.finishResolution( 'getEntityRecord', [
					'postType',
					'product',
					73,
				] );
			const { getByTestId: getSelectedByTestId } = renderFilteredState( {
				attributes: getRegisteredAttributes( name, 73 ),
				location: { type: LocationType.Site },
				registry: selectedProductRegistry,
			} );
			expect(
				getSelectedByTestId( 'collection-state' )
			).toHaveTextContent( ProductCollectionUIStatesInEditor.VALID );
		}
	);

	it( 'stores and queries block and inserter scopes in the real Blocks registry', () => {
		const blockName = `${ TEST_NAMESPACE }/block-scope`;
		const inserterName = `${ TEST_NAMESPACE }/inserter-scope`;
		registerCollection( {
			name: blockName,
			title: 'Block scope',
			scope: [ 'block' ],
		} );
		registerCollection( {
			name: inserterName,
			title: 'Inserter scope',
			scope: [ 'inserter' ],
		} );

		expect(
			getBlockVariations( BLOCK_NAME, 'block' )?.map(
				( variation ) => variation.name
			)
		).toContain( blockName );
		expect(
			getBlockVariations( BLOCK_NAME, 'block' )?.map(
				( variation ) => variation.name
			)
		).not.toContain( inserterName );
		expect(
			select( blocksStore )
				.getBlockVariations( BLOCK_NAME, 'inserter' )
				?.map( ( variation ) => variation.name )
		).toContain( inserterName );
		expect(
			select( blocksStore )
				.getBlockVariations( BLOCK_NAME, 'inserter' )
				?.map( ( variation ) => variation.name )
		).not.toContain( blockName );
	} );
} );
