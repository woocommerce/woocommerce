/**
 * External dependencies
 */
import {
	act,
	fireEvent,
	render,
	screen,
	waitFor,
} from '@testing-library/react';
import { store as coreStore } from '@wordpress/core-data';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import ProductCollectionInspectorControls from '..';
import { DEFAULT_ATTRIBUTES, DEFAULT_QUERY } from '../../../constants';
import type {
	ProductCollectionContentProps,
	ProductCollectionAttributes,
} from '../../../types';
import { CoreFilterNames } from '../../../types';
import { LocationType } from '../../../../product-template/utils';

let mockIsEmailEditor = false;

jest.mock( '@wordpress/block-editor', () => {
	const actual = jest.requireActual( '@wordpress/block-editor' );
	const React = jest.requireActual( 'react' );

	return {
		...actual,
		InspectorControls: ( { children } ) =>
			React.createElement( 'div', null, children ),
	};
} );

jest.mock( '@woocommerce/email-editor', () => ( {
	useIsEmailEditor: () => mockIsEmailEditor,
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	ADMIN_URL: 'https://example.test/wp-admin/',
	getSetting: jest.fn( ( setting, defaultValue ) =>
		setting === 'stockStatusOptions'
			? {
					instock: 'In stock',
					outofstock: 'Out of stock',
			  }
			: defaultValue
	),
} ) );

jest.mock( '@wordpress/components', () => {
	const actual = jest.requireActual( '@wordpress/components' );
	const React = jest.requireActual( 'react' );
	const passThrough = ( { children } ) =>
		React.createElement( React.Fragment, null, children );
	const BaseControl = ( { children, label } ) =>
		React.createElement( 'fieldset', { 'aria-label': label }, children );
	BaseControl.VisualLabel = passThrough;

	return {
		...actual,
		BaseControl,
		ExternalLink: ( { children, href } ) =>
			React.createElement( 'a', { href }, children ),
		Flex: passThrough,
		FlexItem: passThrough,
		FormTokenField: ( { label, onChange, value = [] } ) =>
			React.createElement( 'input', {
				'aria-label': label,
				onChange: ( event ) =>
					onChange(
						event.target.value
							? event.target.value
									.split( ',' )
									.map( ( token ) => token.trim() )
							: []
					),
				value: value.join( ', ' ),
			} ),
		Notice: passThrough,
		PanelBody: passThrough,
		RadioControl: ( { onChange, options, selected } ) =>
			React.createElement(
				'select',
				{
					'aria-label': 'Created period',
					onChange: ( event ) => onChange( event.target.value ),
					value: selected || '',
				},
				React.createElement( 'option', { value: '' }, 'Select' ),
				options.map( ( option ) =>
					React.createElement(
						'option',
						{ key: option.value, value: option.value },
						option.label
					)
				)
			),
		RangeControl: ( { label, max, min, onChange, value } ) =>
			React.createElement( 'input', {
				'aria-label': label,
				max,
				min,
				onChange: ( event ) => onChange( Number( event.target.value ) ),
				type: 'range',
				value,
			} ),
		SelectControl: ( { label, onChange, options, value } ) =>
			React.createElement(
				'select',
				{
					'aria-label': label,
					onChange: ( event ) => onChange( event.target.value ),
					value,
				},
				options.map( ( option ) =>
					React.createElement(
						'option',
						{ key: option.value, value: option.value },
						option.label
					)
				)
			),
		TextControl: ( { label, onChange, value } ) =>
			React.createElement( 'input', {
				'aria-label': label,
				onChange: ( event ) => onChange( event.target.value ),
				value,
			} ),
		ToggleControl: ( { checked, label, onChange } ) =>
			React.createElement( 'input', {
				'aria-label': label,
				checked,
				onChange: ( event ) => onChange( event.target.checked ),
				type: 'checkbox',
			} ),
		__experimentalHStack: passThrough,
		__experimentalInputControl: ( {
			label,
			onBlur,
			onChange,
			onKeyDown,
			value,
		} ) =>
			React.createElement( 'input', {
				'aria-label': label,
				onBlur,
				onChange: ( event ) => onChange( event.target.value ),
				onKeyDown,
				value: value || '',
			} ),
		__experimentalInputControlPrefixWrapper: passThrough,
		__experimentalNumberControl: ( { label, min, onChange, value } ) =>
			React.createElement( 'input', {
				'aria-label': label,
				min,
				onChange: ( event ) => onChange( Number( event.target.value ) ),
				type: 'number',
				value,
			} ),
		__experimentalToggleGroupControl: ( {
			children,
			label,
			onChange,
			value,
		} ) =>
			React.createElement(
				'select',
				{
					'aria-label': label,
					onChange: ( event ) => onChange( event.target.value ),
					value,
				},
				children
			),
		__experimentalToggleGroupControlOption: ( { label, value } ) =>
			React.createElement( 'option', { value }, label ),
		__experimentalToolsPanel: ( { children, label } ) =>
			React.createElement( 'section', { 'aria-label': label }, children ),
		__experimentalToolsPanelItem: ( { children, label, onDeselect } ) =>
			React.createElement(
				'section',
				{ 'aria-label': label },
				children,
				onDeselect &&
					React.createElement(
						'button',
						{ onClick: onDeselect, type: 'button' },
						`Reset ${ label }`
					)
			),
		__experimentalUnitControl: ( { onChange, value } ) =>
			React.createElement( 'input', {
				'aria-label': 'Fixed width',
				onChange: ( event ) => onChange( event.target.value ),
				value,
			} ),
	};
} );

jest.mock(
	'@woocommerce/editor-components/product-attribute-term-control',
	() => ( props ) => {
		const React = jest.requireActual( 'react' );
		return React.createElement(
			'button',
			{
				onClick: () =>
					props.onChange( [ { id: 41, value: 'pa_color' } ] ),
				type: 'button',
			},
			'Choose product attribute'
		);
	}
);

jest.mock(
	'@woocommerce/editor-components/product-category-control',
	() => ( props ) => {
		const React = jest.requireActual( 'react' );
		return React.createElement(
			'button',
			{
				onClick: () => props.onChange( [ { id: 31 } ] ),
				type: 'button',
			},
			'Choose product category'
		);
	}
);

jest.mock(
	'@woocommerce/editor-components/product-tag-control',
	() => ( props ) => {
		const React = jest.requireActual( 'react' );
		return React.createElement(
			'button',
			{
				onClick: () => props.onChange( [ { id: 32 } ] ),
				type: 'button',
			},
			'Choose product tag'
		);
	}
);

jest.mock(
	'@woocommerce/editor-components/product-brand-control',
	() => ( props ) => {
		const React = jest.requireActual( 'react' );
		return React.createElement(
			'button',
			{
				onClick: () => props.onChange( [ { id: 33 } ] ),
				type: 'button',
			},
			'Choose product brand'
		);
	}
);

jest.mock( '@woocommerce/editor-components/utils', () => ( {
	...jest.requireActual( '@woocommerce/editor-components/utils' ),
	getProducts: jest.fn().mockResolvedValue( [
		{ id: 71, name: 'Beanie' },
		{ id: 72, name: 'Cap' },
	] ),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

const createAttributes = (
	overrides: Partial< ProductCollectionAttributes > = {}
): ProductCollectionAttributes => ( {
	...DEFAULT_ATTRIBUTES,
	collection: undefined,
	convertedFromProducts: false,
	hideControls: [ CoreFilterNames.HAND_PICKED ],
	query: { ...DEFAULT_QUERY },
	queryContext: [ { page: 1 } ],
	queryId: 1,
	templateSlug: '',
	...overrides,
} );

const renderInspector = ( {
	attributes = createAttributes(),
	templateSlug = 'single-product',
}: {
	attributes?: ProductCollectionAttributes;
	templateSlug?: string;
} = {} ) => {
	const setAttributes = jest.fn();
	const props = {
		attributes,
		clientId: 'product-collection-test',
		context: { templateSlug },
		insertBlocksAfter: jest.fn(),
		isSelected: true,
		isUsingReferencePreviewMode: false,
		location: { type: LocationType.Site },
		name: 'woocommerce/product-collection',
		onReplace: jest.fn(),
		openCollectionSelectionModal: jest.fn(),
		setAttributes,
		tracksLocation: 'single-product',
	} as unknown as ProductCollectionContentProps;

	const renderResult = render(
		<ProductCollectionInspectorControls { ...props } />
	);

	return { ...renderResult, setAttributes };
};

describe( 'Product Collection inspector control contracts', () => {
	beforeEach( () => {
		mockIsEmailEditor = false;
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

	it( 'writes the complete nested query from the products-per-page control', () => {
		const { setAttributes } = renderInspector();

		fireEvent.change(
			screen.getByRole( 'slider', { name: 'Products per page' } ),
			{ target: { value: '12' } }
		);

		expect( setAttributes ).toHaveBeenCalledWith( {
			query: {
				...DEFAULT_QUERY,
				perPage: 12,
			},
		} );
	} );

	it.each( [
		[ 'order', 'Order by', 'combobox' ],
		[ 'keyword', 'Keyword', 'textbox' ],
		[ 'offset', 'Offset', 'spinbutton' ],
		[ 'max-pages-to-show', 'Max pages to show', 'spinbutton' ],
		[ 'products-per-page', 'Products per page', 'slider' ],
	] as const )(
		'honors the %s hidden-control contract',
		( control, label, role ) => {
			renderInspector( {
				attributes: createAttributes( {
					hideControls: [ CoreFilterNames.HAND_PICKED, control ],
				} ),
			} );

			expect(
				screen.queryByRole( role, { name: label } )
			).not.toBeInTheDocument();
		}
	);

	it( 'writes the debounced keyword to the complete nested query', () => {
		jest.useFakeTimers();
		const { setAttributes, unmount } = renderInspector();

		try {
			fireEvent.change(
				screen.getByRole( 'textbox', { name: 'Keyword' } ),
				{ target: { value: 'beanie' } }
			);
			expect( setAttributes ).not.toHaveBeenCalled();

			act( () => {
				jest.advanceTimersByTime( 250 );
			} );

			expect( setAttributes ).toHaveBeenCalledWith( {
				query: {
					...DEFAULT_QUERY,
					search: 'beanie',
				},
			} );
		} finally {
			unmount();
			jest.useRealTimers();
		}
	} );

	it.each( [
		[ 'category', 'product_cat', 31 ],
		[ 'tag', 'product_tag', 32 ],
		[ 'brand', 'product_brand', 33 ],
	] as const )(
		'writes the %s taxonomy selection to the complete nested query',
		( control, taxonomy, termId ) => {
			const { setAttributes } = renderInspector();

			fireEvent.click(
				screen.getByRole( 'button', {
					name: `Choose product ${ control }`,
				} )
			);

			expect( setAttributes ).toHaveBeenCalledWith( {
				query: {
					...DEFAULT_QUERY,
					taxQuery: { [ taxonomy ]: [ termId ] },
				},
			} );
		}
	);

	it( 'switches the visible controls for inherited, carousel, and email contexts', () => {
		const inherited = createAttributes( {
			query: { ...DEFAULT_QUERY, inherit: true },
		} );
		const { rerender } = renderInspector( {
			attributes: inherited,
			templateSlug: 'archive-product',
		} );

		expect(
			screen.getByRole( 'combobox', { name: 'Query type' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'region', { name: 'Filters' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'spinbutton', { name: 'Offset' } )
		).not.toBeInTheDocument();

		const carousel = {
			...createAttributes(),
			displayLayout: {
				...DEFAULT_ATTRIBUTES.displayLayout,
				type: 'carousel',
			},
		} as ProductCollectionAttributes;
		rerender(
			<ProductCollectionInspectorControls
				{ ...( {
					attributes: carousel,
					clientId: 'product-collection-test',
					context: { templateSlug: 'single-product' },
					location: { type: LocationType.Site },
					setAttributes: jest.fn(),
				} as unknown as ProductCollectionContentProps ) }
			/>
		);
		expect(
			screen.getByRole( 'slider', { name: 'Products in carousel' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'slider', { name: 'Columns' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'spinbutton', {
				name: 'Max pages to show',
			} )
		).not.toBeInTheDocument();

		mockIsEmailEditor = true;
		rerender(
			<ProductCollectionInspectorControls
				{ ...( {
					attributes: createAttributes(),
					clientId: 'product-collection-test',
					context: { templateSlug: 'single-product' },
					location: { type: LocationType.Site },
					setAttributes: jest.fn(),
				} as unknown as ProductCollectionContentProps ) }
			/>
		);
		expect(
			screen.getByRole( 'slider', { name: 'Number of products' } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'combobox', { name: 'Layout' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'combobox', { name: 'Width' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'spinbutton', { name: 'Offset' } )
		).not.toBeInTheDocument();
	} );

	it.each( [
		[
			'Order by',
			'combobox',
			'title/desc',
			{ orderBy: 'title', order: 'desc' },
		],
		[ 'Offset', 'spinbutton', '4', { offset: 4 } ],
		[ 'Max pages to show', 'spinbutton', '2', { pages: 2 } ],
	] as const )(
		'writes the complete nested query from %s',
		( label, role, value, update ) => {
			const { setAttributes } = renderInspector();
			fireEvent.change( screen.getByRole( role, { name: label } ), {
				target: { value },
			} );

			expect( setAttributes ).toHaveBeenCalledWith( {
				query: { ...DEFAULT_QUERY, ...update },
			} );
		}
	);

	it.each( [
		[ 'Show only products on sale', { woocommerceOnSale: true } ],
		[ 'Show only featured products', { featured: true } ],
	] as const )(
		'writes the complete nested query from %s',
		( label, update ) => {
			const { setAttributes } = renderInspector();
			fireEvent.click( screen.getByRole( 'checkbox', { name: label } ) );

			expect( setAttributes ).toHaveBeenCalledWith( {
				query: { ...DEFAULT_QUERY, ...update },
			} );
		}
	);

	it( 'writes stock and attribute filters', () => {
		const { setAttributes } = renderInspector();

		fireEvent.change(
			screen.getByRole( 'textbox', { name: 'Stock Status' } ),
			{
				target: { value: 'In stock' },
			}
		);
		expect( setAttributes ).toHaveBeenLastCalledWith( {
			query: {
				...DEFAULT_QUERY,
				woocommerceStockStatus: [ 'instock' ],
			},
		} );

		fireEvent.click( screen.getByText( 'Choose product attribute' ) );
		expect( setAttributes ).toHaveBeenLastCalledWith( {
			query: {
				...DEFAULT_QUERY,
				woocommerceAttributes: [ { taxonomy: 'pa_color', termId: 41 } ],
			},
		} );
	} );

	it( 'writes the created period with its default within operator', () => {
		const { setAttributes } = renderInspector();

		fireEvent.change(
			screen.getByRole( 'combobox', {
				name: 'Created period',
			} ),
			{
				target: { value: '-7 days' },
			}
		);
		expect( setAttributes ).toHaveBeenLastCalledWith( {
			query: {
				...DEFAULT_QUERY,
				timeFrame: { operator: 'in', value: '-7 days' },
			},
		} );
	} );

	it.each( [
		[ 'in', 'not-in' ],
		[ 'not-in', 'in' ],
	] as const )(
		'writes the created operator from %s to %s',
		( initialOperator, nextOperator ) => {
			const attributes = createAttributes( {
				query: {
					...DEFAULT_QUERY,
					timeFrame: {
						operator: initialOperator,
						value: '-7 days',
					},
				},
			} );
			const { setAttributes } = renderInspector( { attributes } );

			fireEvent.change(
				screen.getByRole( 'combobox', { name: 'Created' } ),
				{ target: { value: nextOperator } }
			);

			expect( setAttributes ).toHaveBeenCalledWith( {
				query: {
					...attributes.query,
					timeFrame: {
						operator: nextOperator,
						value: '-7 days',
					},
				},
			} );
		}
	);

	it.each( [
		[ 'MIN', '10', { max: 20, min: 10 } ],
		[ 'MAX', '30', { max: 30, min: 5 } ],
	] as const )(
		'writes the %s price boundary',
		( label, value, expectedPriceRange ) => {
			const attributes = createAttributes( {
				query: {
					...DEFAULT_QUERY,
					priceRange: { max: 20, min: 5 },
				},
			} );
			const { setAttributes } = renderInspector( { attributes } );

			fireEvent.change( screen.getByRole( 'textbox', { name: label } ), {
				target: { value },
			} );
			fireEvent.blur( screen.getByRole( 'textbox', { name: label } ) );

			expect( setAttributes ).toHaveBeenCalledWith( {
				query: {
					...attributes.query,
					priceRange: expectedPriceRange,
				},
			} );
		}
	);

	it( 'resets the price range', () => {
		const attributes = createAttributes( {
			query: {
				...DEFAULT_QUERY,
				priceRange: { max: 20, min: 5 },
			},
		} );
		const { setAttributes } = renderInspector( { attributes } );

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Reset Price Range' } )
		);

		expect( setAttributes ).toHaveBeenCalledWith( {
			query: {
				...attributes.query,
				priceRange: undefined,
			},
		} );
	} );

	it( 'writes the hand-picked product filter after remote data resolves', async () => {
		const { setAttributes } = renderInspector( {
			attributes: createAttributes( { hideControls: [] } ),
		} );
		const field = screen.getByRole( 'textbox', { name: 'Hand-Picked' } );
		await waitFor( () => expect( field ).toHaveValue( '' ) );

		fireEvent.change( field, {
			target: { value: 'Cap, Beanie' },
		} );
		expect( setAttributes ).toHaveBeenLastCalledWith( {
			query: {
				...DEFAULT_QUERY,
				woocommerceHandPickedProducts: [ '72', '71' ],
			},
		} );
	} );

	it( 'writes display settings and resets the products-per-page query', () => {
		const attributes = createAttributes( {
			query: { ...DEFAULT_QUERY, perPage: 12 },
		} );
		const { setAttributes } = renderInspector( { attributes } );

		fireEvent.change( screen.getByRole( 'slider', { name: 'Columns' } ), {
			target: { value: '4' },
		} );
		expect( setAttributes ).toHaveBeenLastCalledWith( {
			displayLayout: {
				...DEFAULT_ATTRIBUTES.displayLayout,
				columns: 4,
			},
		} );

		fireEvent.click(
			screen.getByRole( 'button', {
				name: 'Reset Products per page',
			} )
		);
		expect( setAttributes ).toHaveBeenLastCalledWith( {
			query: { ...attributes.query, perPage: DEFAULT_QUERY.perPage },
		} );
	} );
} );
