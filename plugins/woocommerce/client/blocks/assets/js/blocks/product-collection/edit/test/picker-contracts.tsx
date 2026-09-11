/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
	BlockEditorProvider,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	type BlockInstance,
	registerBlockVariation,
	unregisterBlockVariation,
} from '@wordpress/blocks';
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, select } from '@wordpress/data';
import { useState, type ReactNode } from '@wordpress/element';
import { removeFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import Edit from '..';
import LinkedProductControl from '../inspector-controls/linked-product-control';
import MultiProductPicker from '../multi-product-picker';
import SingleProductPicker from '../single-product-picker';
import TaxonomyPicker from '../taxonomy-picker';
import {
	registerCollections,
	registerEmailCollections,
} from '../../collections';
import {
	DEFAULT_ATTRIBUTES,
	DEFAULT_QUERY,
	PRODUCT_COLLECTION_BLOCK_NAME,
} from '../../constants';
import {
	CoreCollectionNames,
	type ProductCollectionAttributes,
	type ProductCollectionEditComponentProps,
	type ProductCollectionQuery,
	type ProductCollectionSetAttributes,
} from '../../types';
import {
	LocationType,
	type WooCommerceBlockLocation,
} from '../../../product-template/utils';

jest.mock( '@woocommerce/editor-components/products-control', () => {
	const React = jest.requireActual( 'react' );

	return ( { onChange, selected } ) =>
		React.createElement(
			'button',
			{
				'aria-label': 'Choose hand-picked products',
				'data-selected': selected.join( ',' ),
				onClick: () => onChange( [ { id: 71 }, { id: 72 } ] ),
				type: 'button',
			},
			'Choose hand-picked products'
		);
} );

jest.mock( '@woocommerce/editor-components/product-category-control', () => {
	const React = jest.requireActual( 'react' );

	return ( { onChange, selected } ) =>
		React.createElement(
			'button',
			{
				'aria-label': 'Choose product category',
				'data-selected': selected.join( ',' ),
				onClick: () => onChange( [ { id: 31 } ] ),
				type: 'button',
			},
			'Choose product category'
		);
} );

jest.mock( '@woocommerce/editor-components/product-tag-control', () => {
	const React = jest.requireActual( 'react' );

	return ( { onChange, selected } ) =>
		React.createElement(
			'button',
			{
				'aria-label': 'Choose product tag',
				'data-selected': selected.join( ',' ),
				onClick: () => onChange( [ { id: '32' } ] ),
				type: 'button',
			},
			'Choose product tag'
		);
} );

jest.mock( '@woocommerce/editor-components/product-brand-control', () => {
	const React = jest.requireActual( 'react' );

	return ( { onChange, selected } ) =>
		React.createElement(
			'button',
			{
				'aria-label': 'Choose product brand',
				'data-selected': selected.join( ',' ),
				onClick: () => onChange( [ { id: 33 } ] ),
				type: 'button',
			},
			'Choose product brand'
		);
} );

jest.mock( '@woocommerce/editor-components/product-control', () => {
	const React = jest.requireActual( 'react' );

	return ( { onChange, selected } ) =>
		React.createElement(
			'button',
			{
				'aria-label': 'Choose product reference',
				'data-selected': String( selected ?? '' ),
				onClick: () => onChange( [ { id: 73 } ] ),
				type: 'button',
			},
			'Choose product reference'
		);
} );

jest.mock(
	'@woocommerce/editor-components/product-attribute-term-control',
	() => {
		const React = jest.requireActual( 'react' );

		return ( { onChange } ) =>
			React.createElement(
				'button',
				{
					'aria-label': 'Choose product attribute',
					onClick: () =>
						onChange( [ { id: 41, value: 'pa_color' } ] ),
					type: 'button',
				},
				'Choose product attribute'
			);
	}
);

jest.mock( '@woocommerce/editor-components/utils', () => ( {
	...jest.requireActual( '@woocommerce/editor-components/utils' ),
	getProduct: jest.fn( ( id: number ) =>
		Promise.resolve( {
			id,
			images: [],
			name: id === 71 ? 'Beanie' : `Product ${ id }`,
			sku: `sku-${ id }`,
		} )
	),
	getProducts: jest.fn().mockResolvedValue( [
		{ id: 71, name: 'Beanie' },
		{ id: 72, name: 'Cap' },
	] ),
} ) );

const registeredCollectionNames = [
	CoreCollectionNames.PRODUCT_CATALOG,
	CoreCollectionNames.FEATURED,
	CoreCollectionNames.NEW_ARRIVALS,
	CoreCollectionNames.ON_SALE,
	CoreCollectionNames.BEST_SELLERS,
	CoreCollectionNames.TOP_RATED,
	CoreCollectionNames.HAND_PICKED,
	CoreCollectionNames.BY_CATEGORY,
	CoreCollectionNames.BY_TAG,
	CoreCollectionNames.BY_BRAND,
	CoreCollectionNames.RELATED,
	CoreCollectionNames.UPSELLS,
	CoreCollectionNames.CROSS_SELLS,
	CoreCollectionNames.CART_CONTENTS,
];

const originalWpDescriptor = Object.getOwnPropertyDescriptor( window, 'wp' );

const createAttributes = (
	collection: string,
	query: Partial< ProductCollectionQuery > = {}
): ProductCollectionAttributes => ( {
	...DEFAULT_ATTRIBUTES,
	collection,
	convertedFromProducts: false,
	filterable: false,
	hideControls: [],
	query: { ...DEFAULT_QUERY, ...query },
	queryContext: [ { page: 1 } ],
	queryId: 1,
	templateSlug: '',
} );

const makeEditProps = (
	attributes: ProductCollectionAttributes,
	setAttributes: ProductCollectionSetAttributes,
	usesReference?: string[]
): ProductCollectionEditComponentProps =>
	( {
		attributes,
		clientId: 'product-collection-picker-contract',
		context: { templateSlug: '' },
		insertBlocksAfter: jest.fn(),
		isSelected: true,
		name: PRODUCT_COLLECTION_BLOCK_NAME,
		onReplace: jest.fn(),
		setAttributes,
		tracksLocation: 'other',
		usesReference,
	} ) as unknown as ProductCollectionEditComponentProps;

const GutenbergProvider = ( { children }: { children: ReactNode } ) => {
	const [ blocks, setBlocks ] = useState< BlockInstance[] >( [] );

	return (
		<BlockEditorProvider
			value={ blocks }
			onInput={ setBlocks }
			onChange={ setBlocks }
			settings={ {} }
		>
			{ children }
		</BlockEditorProvider>
	);
};

const StatefulAttributes = ( {
	children,
	initialAttributes,
	onSetAttributes = jest.fn(),
}: {
	children: (
		attributes: ProductCollectionAttributes,
		setAttributes: ProductCollectionSetAttributes
	) => ReactNode;
	initialAttributes: ProductCollectionAttributes;
	onSetAttributes?: jest.Mock;
} ) => {
	const [ attributes, setCurrentAttributes ] = useState( initialAttributes );
	const setAttributes: ProductCollectionSetAttributes = ( updates ) => {
		onSetAttributes( updates );
		setCurrentAttributes( ( current ) => ( {
			...current,
			...updates,
		} ) );
	};

	return (
		<>
			{ children( attributes, setAttributes ) }
			<output data-testid="current-query">
				{ JSON.stringify( attributes.query ) }
			</output>
		</>
	);
};

const renderInEditor = ( children: ReactNode ) =>
	render( <GutenbergProvider>{ children }</GutenbergProvider> );

const click = async (
	user: ReturnType< typeof userEvent.setup >,
	element: HTMLElement
) => {
	await act( async () => {
		await user.click( element );
	} );
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

beforeEach( () => {
	dispatch( blockEditorStore ).resetBlocks( [] );
	jest.spyOn(
		select( coreStore ) as unknown as {
			getTaxonomies: () => Array< {
				name: string;
				slug: string;
				visibility: { publicly_queryable: boolean };
			} >;
		},
		'getTaxonomies'
	).mockReturnValue( [
		{
			name: 'product categories',
			slug: 'product_cat',
			visibility: { publicly_queryable: true },
		},
		{
			name: 'product tags',
			slug: 'product_tag',
			visibility: { publicly_queryable: true },
		},
		{
			name: 'product brands',
			slug: 'product_brand',
			visibility: { publicly_queryable: true },
		},
	] );
} );

afterEach( () => {
	jest.restoreAllMocks();
} );

afterAll( () => {
	for ( const name of registeredCollectionNames ) {
		unregisterBlockVariation( PRODUCT_COLLECTION_BLOCK_NAME, name );
		removeFilter( 'editor.BlockEdit', name );
	}

	if ( originalWpDescriptor ) {
		Object.defineProperty( window, 'wp', originalWpDescriptor );
	} else {
		Reflect.deleteProperty( window, 'wp' );
	}
} );

describe( 'collection-specific picker contracts', () => {
	it( 'writes hand-picked products and enables Done before dismissal', async () => {
		const user = userEvent.setup();
		const onDone = jest.fn();
		const onSetAttributes = jest.fn();
		const attributes = createAttributes( CoreCollectionNames.HAND_PICKED, {
			search: 'shirts',
		} );

		renderInEditor(
			<StatefulAttributes
				initialAttributes={ attributes }
				onSetAttributes={ onSetAttributes }
			>
				{ ( currentAttributes, setAttributes ) => (
					<MultiProductPicker
						{ ...makeEditProps( currentAttributes, setAttributes ) }
						onDone={ onDone }
					/>
				) }
			</StatefulAttributes>
		);

		const done = screen.getByRole( 'button', { name: 'Done' } );
		expect( done ).toBeDisabled();

		await click(
			user,
			screen.getByRole( 'button', {
				name: 'Choose hand-picked products',
			} )
		);

		expect( onSetAttributes ).toHaveBeenLastCalledWith( {
			query: {
				...attributes.query,
				woocommerceHandPickedProducts: [ '71', '72' ],
			},
		} );
		expect( done ).toBeEnabled();
		await click( user, done );
		expect( onDone ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'writes Products by Category taxonomy selection', async () => {
		const user = userEvent.setup();
		const onDone = jest.fn();
		const onSetAttributes = jest.fn();
		const attributes = createAttributes( CoreCollectionNames.BY_CATEGORY, {
			search: 'hoodie',
			taxQuery: { product_tag: [ 17 ] },
		} );

		renderInEditor(
			<StatefulAttributes
				initialAttributes={ attributes }
				onSetAttributes={ onSetAttributes }
			>
				{ ( currentAttributes, setAttributes ) => (
					<TaxonomyPicker
						{ ...makeEditProps( currentAttributes, setAttributes ) }
						onDone={ onDone }
					/>
				) }
			</StatefulAttributes>
		);

		const done = screen.getByRole( 'button', { name: 'Done' } );
		expect( done ).toBeDisabled();
		await click(
			user,
			screen.getByRole( 'button', {
				name: 'Choose product category',
			} )
		);

		expect( onSetAttributes ).toHaveBeenLastCalledWith( {
			query: {
				...attributes.query,
				taxQuery: {
					product_tag: [ 17 ],
					product_cat: [ 31 ],
				},
			},
		} );
		expect( done ).toBeEnabled();
		await click( user, done );
		expect( onDone ).toHaveBeenCalledTimes( 1 );
	} );

	it.each( [
		{
			caseName: 'Products by Tag',
			collection: CoreCollectionNames.BY_TAG,
			controlName: 'Choose product tag',
			taxonomy: 'product_tag',
			termId: 32,
		},
		{
			caseName: 'Products by Brand',
			collection: CoreCollectionNames.BY_BRAND,
			controlName: 'Choose product brand',
			taxonomy: 'product_brand',
			termId: 33,
		},
	] )( 'writes $caseName taxonomy selection', async ( row ) => {
		const user = userEvent.setup();
		const onSetAttributes = jest.fn();
		const attributes = createAttributes( row.collection );

		renderInEditor(
			<StatefulAttributes
				initialAttributes={ attributes }
				onSetAttributes={ onSetAttributes }
			>
				{ ( currentAttributes, setAttributes ) => (
					<TaxonomyPicker
						{ ...makeEditProps( currentAttributes, setAttributes ) }
						onDone={ jest.fn() }
					/>
				) }
			</StatefulAttributes>
		);

		await click(
			user,
			screen.getByRole( 'button', { name: row.controlName } )
		);

		expect( onSetAttributes ).toHaveBeenLastCalledWith( {
			query: {
				...attributes.query,
				taxQuery: { [ row.taxonomy ]: [ row.termId ] },
			},
		} );
		expect( screen.getByRole( 'button', { name: 'Done' } ) ).toBeEnabled();
	} );

	it( 'writes an exact single-product reference', async () => {
		const user = userEvent.setup();
		const onSetAttributes = jest.fn();
		const attributes = createAttributes( CoreCollectionNames.RELATED, {
			search: 'hoodie',
		} );

		renderInEditor(
			<StatefulAttributes
				initialAttributes={ attributes }
				onSetAttributes={ onSetAttributes }
			>
				{ ( currentAttributes, setAttributes ) => (
					<SingleProductPicker
						{ ...makeEditProps( currentAttributes, setAttributes, [
							'product',
						] ) }
						isDeletedProductReference={ false }
					/>
				) }
			</StatefulAttributes>
		);

		await click(
			user,
			screen.getByRole( 'button', {
				name: 'Choose product reference',
			} )
		);
		expect( onSetAttributes ).toHaveBeenLastCalledWith( {
			query: { ...attributes.query, productReference: 73 },
		} );
	} );
} );

describe( 'Edit picker-state contracts', () => {
	it( 'dismisses and reactivates the hand-picked picker', async () => {
		const user = userEvent.setup();
		const setAttributes = jest.fn();
		const empty = createAttributes( CoreCollectionNames.HAND_PICKED );
		const selected = createAttributes( CoreCollectionNames.HAND_PICKED, {
			woocommerceHandPickedProducts: [ '71', '72' ],
		} );
		const { rerender } = renderInEditor(
			<Edit { ...makeEditProps( empty, setAttributes ) } />
		);

		expect(
			await screen.findByRole( 'button', {
				name: 'Choose hand-picked products',
			} )
		).toBeInTheDocument();

		rerender(
			<GutenbergProvider>
				<Edit { ...makeEditProps( selected, setAttributes ) } />
			</GutenbergProvider>
		);
		const done = screen.getByRole( 'button', { name: 'Done' } );
		expect( done ).toBeEnabled();
		await click( user, done );
		await waitFor( () =>
			expect(
				screen.queryByRole( 'button', {
					name: 'Choose hand-picked products',
				} )
			).not.toBeInTheDocument()
		);

		rerender(
			<GutenbergProvider>
				<Edit { ...makeEditProps( empty, setAttributes ) } />
			</GutenbergProvider>
		);
		expect(
			await screen.findByRole( 'button', {
				name: 'Choose hand-picked products',
			} )
		).toBeInTheDocument();
	} );

	it( 'drops stale pickers when collections switch', async () => {
		const setAttributes = jest.fn();
		const { rerender } = renderInEditor(
			<Edit
				{ ...makeEditProps(
					createAttributes( CoreCollectionNames.HAND_PICKED ),
					setAttributes
				) }
			/>
		);

		expect(
			await screen.findByRole( 'button', {
				name: 'Choose hand-picked products',
			} )
		).toBeInTheDocument();

		rerender(
			<GutenbergProvider>
				<Edit
					{ ...makeEditProps(
						createAttributes( CoreCollectionNames.BY_CATEGORY ),
						setAttributes
					) }
				/>
			</GutenbergProvider>
		);
		expect(
			await screen.findByRole( 'button', {
				name: 'Choose product category',
			} )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', {
				name: 'Choose hand-picked products',
			} )
		).not.toBeInTheDocument();

		setAttributes.mockClear();
		rerender(
			<GutenbergProvider>
				<Edit
					{ ...makeEditProps(
						createAttributes( CoreCollectionNames.FEATURED ),
						setAttributes
					) }
				/>
			</GutenbergProvider>
		);
		await waitFor( () =>
			expect( setAttributes ).toHaveBeenCalledWith(
				expect.objectContaining( {
					collection: CoreCollectionNames.FEATURED,
				} )
			)
		);
		expect(
			screen.queryByRole( 'button', {
				name: 'Choose hand-picked products',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', {
				name: 'Choose product category',
			} )
		).not.toBeInTheDocument();
	} );
} );

describe( 'linked-product reference contracts', () => {
	it.each( [
		{
			caseName: 'product',
			label: 'From the current product',
			location: LocationType.Product,
			usesReference: [ 'product' ],
		},
		{
			caseName: 'cart',
			label: 'From products in the cart',
			location: LocationType.Cart,
			usesReference: [ 'cart' ],
		},
		{
			caseName: 'order',
			label: 'From products in the order',
			location: LocationType.Order,
			usesReference: [ 'order' ],
		},
		{
			caseName: 'multiple',
			label: 'From the current product',
			location: LocationType.Product,
			usesReference: [ 'product', 'cart', 'order' ],
		},
	] )( 'defaults $caseName context to its current reference', ( row ) => {
		renderInEditor(
			<LinkedProductControl
				query={ { ...DEFAULT_QUERY } }
				setAttributes={ jest.fn() }
				location={ { type: row.location } as WooCommerceBlockLocation }
				usesReference={ row.usesReference }
			/>
		);

		expect(
			screen.getByRole( 'radio', { name: row.label } )
		).toBeChecked();
		expect(
			screen.getByRole( 'radio', {
				name: 'From a specific product',
			} )
		).not.toBeChecked();
	} );

	it( 'replaces the exact linked product and preserves the query', async () => {
		const user = userEvent.setup();
		const onSetAttributes = jest.fn();
		const initialQuery = {
			...DEFAULT_QUERY,
			productReference: 71,
			search: 'hoodie',
		};

		renderInEditor(
			<StatefulAttributes
				initialAttributes={ createAttributes(
					CoreCollectionNames.RELATED,
					initialQuery
				) }
				onSetAttributes={ onSetAttributes }
			>
				{ ( attributes, setAttributes ) => (
					<LinkedProductControl
						query={ attributes.query }
						setAttributes={ setAttributes }
						location={ { type: LocationType.Site } }
						usesReference={ [ 'product' ] }
					/>
				) }
			</StatefulAttributes>
		);

		await act( async () => {
			await Promise.resolve();
		} );
		await click(
			user,
			await screen.findByRole( 'button', { name: /Beanie/ } )
		);
		await click(
			user,
			screen.getByRole( 'button', {
				name: 'Choose product reference',
			} )
		);

		expect( onSetAttributes ).toHaveBeenLastCalledWith( {
			query: { ...initialQuery, productReference: 73 },
		} );
	} );
} );
