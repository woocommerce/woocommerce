/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
	createBlock,
	getCategories,
	getBlockType,
	parse,
	registerBlockType,
	serialize,
	setCategories,
	unregisterBlockType,
} from '@wordpress/blocks';
import { store as coreStore } from '@wordpress/core-data';
import { dispatch, select } from '@wordpress/data';
import { applyFilters, removeFilter } from '@wordpress/hooks';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import metadata from '../block.json';
import productTemplateMetadata from '../../product-template/block.json';
import save from '../save';
import productTemplateSave from '../../product-template/save';
import ProductCollectionContent from '../edit/product-collection-content';
import DefaultQueryOrderByControl from '../edit/inspector-controls/order-by-control/default-query-order-by-control';
import {
	DEFAULT_ATTRIBUTES,
	DEFAULT_QUERY,
	PRODUCT_COLLECTION_BLOCK_NAME,
} from '../constants';
import { addProductCollectionToQueryPaginationParentOrAncestor } from '../utils';
import {
	CoreCollectionNames,
	LayoutOptions,
	ProductCollectionAttributes,
} from '../types';
import { LocationType } from '../../product-template/utils';

jest.mock( '../edit/inspector-controls', () => () => null );
jest.mock( '../edit/inspector-advanced-controls', () => () => null );
jest.mock( '../edit/toolbar-controls', () => () => null );
jest.mock(
	'../edit/inspector-controls/order-by-control/order-by-control',
	() => {
		const React = jest.requireActual( 'react' );

		return ( { label, onChange, orderOptions, selectedValue } ) =>
			React.createElement(
				'select',
				{
					'aria-label': label,
					onChange: ( event ) => onChange( event.target.value ),
					value: selectedValue,
				},
				orderOptions.map( ( option ) =>
					React.createElement(
						'option',
						{ key: option.value, value: option.value },
						option.label
					)
				)
			);
	}
);

const registeredBlockTypes: string[] = [];
let originalBlockCategories: ReturnType< typeof getCategories >;

beforeAll( () => {
	originalBlockCategories = getCategories();
	if (
		! originalBlockCategories.some(
			( category ) => category.slug === 'woocommerce'
		)
	) {
		setCategories( [
			...originalBlockCategories,
			{ slug: 'woocommerce', title: 'WooCommerce' },
		] );
	}

	if ( ! getBlockType( productTemplateMetadata.name ) ) {
		registerBlockType( productTemplateMetadata, {
			edit: () => null,
			save: productTemplateSave,
		} );
		registeredBlockTypes.push( productTemplateMetadata.name );
	}

	if ( ! getBlockType( metadata.name ) ) {
		registerBlockType( metadata, {
			edit: () => null,
			save,
		} );
		registeredBlockTypes.push( metadata.name );
	}
} );

afterAll( () => {
	registeredBlockTypes.forEach( ( name ) => unregisterBlockType( name ) );
	setCategories( originalBlockCategories );
} );

describe( 'Product Collection editor contracts', () => {
	it.each( [
		{
			caseName: 'default collection in a post',
			collection: undefined,
			inherit: false,
		},
		{
			caseName: 'named collection in a post',
			collection: CoreCollectionNames.ON_SALE,
			inherit: false,
		},
		{
			caseName: 'default collection in an archive template',
			collection: undefined,
			inherit: true,
		},
		{
			caseName: 'named collection in an archive template',
			collection: CoreCollectionNames.ON_SALE,
			inherit: true,
		},
	] )(
		'round-trips $caseName through real metadata',
		( { collection, inherit } ) => {
			const productTemplate = createBlock( productTemplateMetadata.name );
			const block = createBlock(
				PRODUCT_COLLECTION_BLOCK_NAME,
				{
					__privatePreviewState: {
						isPreview: true,
						previewMessage: 'local preview',
					},
					collection,
					displayLayout: {
						columns: 4,
						shrinkColumns: true,
						type: LayoutOptions.GRID,
					},
					query: {
						...DEFAULT_QUERY,
						inherit,
						perPage: 8,
					},
				},
				[ productTemplate ]
			);
			const serialized = serialize( block );
			const [ parsed ] = parse( serialized );

			expect( parsed.name ).toBe( PRODUCT_COLLECTION_BLOCK_NAME );
			expect( parsed.attributes.collection ).toBe( collection );
			expect( parsed.attributes.query ).toMatchObject( {
				inherit,
				order: 'asc',
				orderBy: 'title',
				perPage: 8,
			} );
			expect( parsed.attributes.displayLayout ).toEqual( {
				columns: 4,
				shrinkColumns: true,
				type: LayoutOptions.GRID,
			} );
			expect( parsed.innerBlocks ).toHaveLength( 1 );
			expect( parsed.innerBlocks[ 0 ].name ).toBe(
				productTemplateMetadata.name
			);
			expect( parsed.attributes ).not.toHaveProperty(
				'__privatePreviewState'
			);
			expect( serialized ).not.toContain( '__privatePreviewState' );
		}
	);

	it( 'adds Product Collection to the real Pagination metadata filter', () => {
		addProductCollectionToQueryPaginationParentOrAncestor();

		try {
			const settings = applyFilters(
				'blocks.registerBlockType',
				{ ancestor: [ 'core/query' ] },
				'core/query-pagination'
			) as { ancestor: string[] };

			expect( settings.ancestor ).toEqual( [
				'core/query',
				PRODUCT_COLLECTION_BLOCK_NAME,
			] );
		} finally {
			removeFilter(
				'blocks.registerBlockType',
				'woocommerce/add-product-collection-block-to-parent-array-of-pagination-block'
			);
		}
	} );
} );

const createPreviewAttributes = (): ProductCollectionAttributes => ( {
	...DEFAULT_ATTRIBUTES,
	convertedFromProducts: false,
	filterable: false,
	hideControls: [],
	query: {
		...DEFAULT_QUERY,
		inherit: true,
	},
	queryContext: [ { page: 1 } ],
	queryId: 1,
	templateSlug: '',
} );

const GenericArchivePreview = ( {
	isSelected,
	taxonomy,
}: {
	isSelected: boolean;
	taxonomy: string | null;
} ) => {
	const [ attributes, setAttributes ] = useState( createPreviewAttributes() );
	const setBlockAttributes = useCallback(
		( updates: Partial< ProductCollectionAttributes > ) => {
			setAttributes( ( current ) => ( {
				...current,
				...updates,
			} ) );
		},
		[]
	);

	return (
		<ProductCollectionContent
			attributes={ attributes }
			clientId={ `preview-${ taxonomy ?? 'attribute' }` }
			context={ { templateSlug: '' } }
			insertBlocksAfter={ () => undefined }
			isSelected={ isSelected }
			isUsingReferencePreviewMode={ false }
			location={ {
				sourceData: { taxonomy, termId: null },
				type: LocationType.Archive,
			} }
			name={ PRODUCT_COLLECTION_BLOCK_NAME }
			onReplace={ () => undefined }
			openCollectionSelectionModal={ () => undefined }
			setAttributes={ setBlockAttributes }
			tracksLocation="product-archive"
		/>
	);
};

describe( 'generic archive previews', () => {
	it.each( [
		[ 'tag', 'product_tag' ],
		[ 'category', 'product_cat' ],
		[ 'attribute', null ],
	] )( 'shows the %s preview only while selected', async ( _, taxonomy ) => {
		const { rerender } = render(
			<GenericArchivePreview isSelected taxonomy={ taxonomy } />
		);

		expect(
			await screen.findByTestId( 'product-collection-preview-button' )
		).toBeVisible();

		rerender(
			<GenericArchivePreview isSelected={ false } taxonomy={ taxonomy } />
		);
		await waitFor( () =>
			expect(
				screen.queryByTestId( 'product-collection-preview-button' )
			).not.toBeInTheDocument()
		);

		rerender( <GenericArchivePreview isSelected taxonomy={ taxonomy } /> );
		expect(
			await screen.findByTestId( 'product-collection-preview-button' )
		).toBeVisible();
	} );
} );

describe( 'default catalog order control', () => {
	it( 'writes the selected default order to the site entity', async () => {
		const user = userEvent.setup();
		const coreSelectors = select( coreStore );
		const coreActions = dispatch( coreStore );
		const getEditedEntityRecord = jest
			.spyOn( coreSelectors, 'getEditedEntityRecord' )
			.mockReturnValue( {
				woocommerce_default_catalog_orderby: 'menu_order',
			} );
		const editEntityRecord = jest
			.spyOn( coreActions, 'editEntityRecord' )
			.mockReturnValue( undefined );
		const trackInteraction = jest.fn();

		try {
			render(
				<DefaultQueryOrderByControl
					trackInteraction={ trackInteraction }
				/>
			);
			await act( async () => {
				await user.selectOptions(
					screen.getByRole( 'combobox', {
						name: 'Default sort by',
					} ),
					'price-desc'
				);
			} );

			expect( editEntityRecord ).toHaveBeenCalledWith(
				'root',
				'site',
				undefined,
				{
					woocommerce_default_catalog_orderby: 'price-desc',
				}
			);
			expect( trackInteraction ).toHaveBeenCalledWith( 'default-order' );
		} finally {
			getEditedEntityRecord.mockRestore();
			editEntityRecord.mockRestore();
		}
	} );
} );
