/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import type { ComponentProps } from 'react';

/**
 * Internal dependencies
 */
import ProductTaxonomyControl from '..';
import type {
	RenderItemArgs,
	SearchListItem,
} from '../../search-list-control/types';

type ConsoleMatchers = { toHaveWarned: () => void };

const list: SearchListItem[] = [
	{
		breadcrumbs: [],
		children: [],
		count: 2,
		id: 1,
		name: 'Clothing',
		parent: 0,
		value: 'clothing',
	},
];

const messages = {
	clear: 'Clear all product terms',
	noItems: 'No product terms.',
	search: 'Search for product terms',
	selected: ( count: number ) => `${ count } selected`,
	updated: 'Product term search results updated.',
};

const renderControl = (
	overrides: Partial< ComponentProps< typeof ProductTaxonomyControl > > = {}
) =>
	render(
		<ProductTaxonomyControl
			isCompact={ false }
			isHierarchical
			isSingle={ false }
			list={ list }
			messages={ messages }
			onChange={ jest.fn() }
			selected={ [] }
			{ ...overrides }
		/>
	);

describe( 'ProductTaxonomyControl', () => {
	test( 'renders product counts and accessible labels', () => {
		renderControl( {
			countType: 'product',
			itemClassName: 'woocommerce-product-terms__item',
		} );

		expect( screen.getByText( '2 products' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Clothing, has 2 products',
			} )
		).toBeInTheDocument();
		expect(
			document.querySelector( '.woocommerce-product-terms__item' )
		).toBeInTheDocument();
	} );

	test( 'renders review counts from item details', () => {
		renderControl( {
			countType: 'review',
			list: [
				{
					...list[ 0 ],
					details: { review_count: 3 },
				},
			],
		} );

		expect( screen.getByText( '3 reviews' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Clothing, has 3 reviews',
			} )
		).toBeInTheDocument();
	} );

	test( 'includes breadcrumbs in accessible labels', () => {
		renderControl( {
			countType: 'product',
			isHierarchical: false,
			list: [
				{
					...list[ 0 ],
					breadcrumbs: [ 'Clothing' ],
					name: 'Shirts',
				},
			],
		} );

		expect(
			screen.getByRole( 'checkbox', {
				name: 'Clothing, Shirts, has 2 products',
			} )
		).toBeInTheDocument();
	} );

	test( 'renders loading and empty states', () => {
		const { container, rerender } = renderControl( { isLoading: true } );

		expect(
			container.querySelector( '.components-spinner' )
		).toBeInTheDocument();

		rerender(
			<ProductTaxonomyControl
				isCompact={ false }
				isHierarchical
				isSingle={ false }
				list={ [] }
				messages={ messages }
				onChange={ jest.fn() }
				selected={ [] }
			/>
		);

		expect( screen.getByText( 'No product terms.' ) ).toBeInTheDocument();
	} );

	test( 'forwards selection changes', () => {
		const onChange = jest.fn();
		renderControl( { countType: 'product', onChange } );

		fireEvent.click(
			screen.getByRole( 'checkbox', {
				name: 'Clothing, has 2 products',
			} )
		);

		expect( onChange ).toHaveBeenCalledWith( [ list[ 0 ] ] );
	} );

	test( 'supports a custom item renderer', () => {
		const renderItem = jest.fn( ( { item }: RenderItemArgs ) => (
			<span>Custom { item.name }</span>
		) );
		renderControl( { renderItem } );

		expect( screen.getByText( 'Custom Clothing' ) ).toBeInTheDocument();
		expect( renderItem ).toHaveBeenCalled();
	} );

	test( 'forwards compact, single, and hierarchical settings', () => {
		const hierarchicalList: SearchListItem[] = [
			list[ 0 ],
			{
				...list[ 0 ],
				count: 1,
				id: 2,
				name: 'Shirts',
				parent: 1,
				value: 'shirts',
			},
		];
		const { container } = renderControl( {
			isCompact: true,
			isSingle: true,
			list: hierarchicalList,
		} );

		expect(
			container.querySelector( '.woocommerce-search-list.is-compact' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'radio', {
				name: 'Clothing, has 2 products',
			} )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Shirts' ) ).not.toBeInTheDocument();

		fireEvent.click( screen.getByRole( 'treeitem' ) );

		expect( screen.getByText( 'Shirts' ) ).toBeInTheDocument();
	} );

	test( 'forwards server search changes', () => {
		const onSearch = jest.fn();
		renderControl( { onSearch } );
		onSearch.mockClear();

		fireEvent.change( screen.getByLabelText( 'Search for product terms' ), {
			target: { value: 'shirt' },
		} );

		expect( onSearch ).toHaveBeenCalledWith( 'shirt' );
	} );

	test( 'renders errors instead of the taxonomy list', () => {
		renderControl( {
			error: { message: 'Unable to load terms', type: 'general' },
		} );

		expect(
			screen.getByText( 'Unable to load terms' )
		).toBeInTheDocument();
		expect(
			screen.queryByLabelText( 'Search for product terms' )
		).not.toBeInTheDocument();
	} );

	test( 'uses the raw selected count to show the operator', () => {
		const onChange = jest.fn();
		const operator = {
			className: 'woocommerce-product-terms__operator',
			labels: {
				all: 'All selected terms',
				any: 'Any selected terms',
				help: 'Select two terms.',
			},
			onChange,
			selectedCount: 1,
			value: 'any',
		};
		const { container, rerender } = renderControl( { operator } );
		// eslint-disable-next-line jest/valid-expect -- The WordPress matcher has no local type declaration.
		( expect( console ) as unknown as ConsoleMatchers ).toHaveWarned();
		const select = screen.getByLabelText( 'Display products matching' );
		const options = screen.getAllByRole( 'option', { hidden: true } );
		const operatorControl = container.querySelector(
			'.woocommerce-product-taxonomy-control__operator'
		);

		expect( select ).toHaveValue( 'any' );
		expect( select.closest( '[hidden]' ) ).toBeInTheDocument();
		expect( options ).toHaveLength( 2 );
		expect( options[ 0 ] ).toHaveValue( 'any' );
		expect( options[ 0 ] ).toHaveTextContent( 'Any selected terms' );
		expect( options[ 1 ] ).toHaveValue( 'all' );
		expect( options[ 1 ] ).toHaveTextContent( 'All selected terms' );
		expect( screen.getByText( 'Select two terms.' ) ).toBeInTheDocument();
		expect( operatorControl ).toHaveClass(
			'woocommerce-product-taxonomy-control__operator',
			'woocommerce-product-terms__operator'
		);

		rerender(
			<ProductTaxonomyControl
				isCompact={ false }
				isHierarchical
				isSingle={ false }
				list={ [] }
				messages={ messages }
				onChange={ jest.fn() }
				operator={ { ...operator, selectedCount: 2 } }
				selected={ [] }
			/>
		);

		expect( select.closest( '[hidden]' ) ).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'combobox', {
				name: 'Display products matching',
			} )
		).toBe( select );
		fireEvent.change( select, { target: { value: 'all' } } );
		expect( onChange ).toHaveBeenCalledTimes( 1 );
		expect( onChange.mock.calls[ 0 ][ 0 ] ).toBe( 'all' );
	} );
} );
