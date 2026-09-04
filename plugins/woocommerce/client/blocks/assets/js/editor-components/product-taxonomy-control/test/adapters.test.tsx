/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import type { ComponentType } from 'react';
import { getSetting } from '@woocommerce/settings';
import useProductAttributes from '@woocommerce/base-context/hooks/use-product-attributes';
import type { ProductCategoryResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import ProductTaxonomyControl from '..';
import ProductCategoryControl from '../../product-category-control';
import ProductBrandControl from '../../product-brand-control';
import ProductTagControl from '../../product-tag-control';
import ProductAttributeTermControl from '../../product-attribute-term-control';
import { getProductTags } from '../../utils';
import type { SearchListItem } from '../../search-list-control/types';

jest.mock( '@woocommerce/editor-components/product-taxonomy-control', () => ( {
	__esModule: true,
	default: jest.fn( () => null ),
} ) );

jest.mock( '@woocommerce/editor-components/search-list-control', () => ( {
	SearchListItem: jest.fn( () => null ),
} ) );

jest.mock( '@woocommerce/block-hocs', () => ( {
	withSearchedCategories: < T, >( component: T ): T => component,
	withSearchedBrands: < T, >( component: T ): T => component,
} ) );

jest.mock( '@wordpress/compose', () => ( {
	...jest.requireActual( '@wordpress/compose' ),
	withInstanceId: < T, >( component: T ): T => component,
} ) );

jest.mock( '@woocommerce/base-context/hooks/use-product-attributes', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	...jest.requireActual( '@woocommerce/settings' ),
	getSetting: jest.fn(
		( _key: string, defaultValue: unknown ) => defaultValue
	),
	getSettingWithCoercion: jest.fn(
		( _key: string, defaultValue: unknown ) => defaultValue
	),
} ) );

jest.mock( '../../utils', () => ( {
	getProductTags: jest.fn(),
} ) );

interface CapturedTaxonomyProps {
	className: string;
	countType?: 'product' | 'review';
	error?: { message: string; type: string } | null;
	isCompact: boolean;
	isHierarchical?: boolean;
	isLoading?: boolean;
	isSingle: boolean;
	itemClassName?: string;
	list: SearchListItem[];
	onChange: ( selected: SearchListItem[] ) => void;
	onSearch?: ( search: string ) => void;
	operator?: {
		className?: string;
		labels: {
			all: string;
			any: string;
			help: string;
		};
		onChange: ( operator: string ) => void;
		selectedCount: number;
		value: string;
	};
	renderItem?: unknown;
	selected: SearchListItem[];
	type?: 'text' | 'token';
}

const mockProductTaxonomyControl =
	ProductTaxonomyControl as unknown as jest.Mock<
		null,
		[ CapturedTaxonomyProps ]
	>;
const mockGetSetting = getSetting as unknown as jest.Mock;
const mockUseProductAttributes = useProductAttributes as jest.MockedFunction<
	typeof useProductAttributes
>;
const mockGetProductTags = getProductTags as unknown as jest.Mock;

const renderAdapter = (
	Adapter: unknown,
	props: Record< string, unknown >
) => {
	const Component = Adapter as ComponentType< Record< string, unknown > >;

	return render( <Component { ...props } /> );
};

const getLastTaxonomyProps = (): CapturedTaxonomyProps => {
	const call = mockProductTaxonomyControl.mock.calls.at( -1 );

	if ( ! call ) {
		throw new Error( 'ProductTaxonomyControl was not rendered.' );
	}

	return call[ 0 ];
};

const createTaxonomyItem = (
	id: number,
	name: string
): ProductCategoryResponseItem => ( {
	count: id + 1,
	description: '',
	id,
	image: null,
	name,
	parent: 0,
	permalink: `https://example.com/${ id }`,
	review_count: id + 2,
	slug: name.toLowerCase(),
} );

describe( 'product taxonomy adapters', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockGetSetting.mockReturnValue( false );
		mockUseProductAttributes.mockReturnValue( {
			errorLoadingAttributes: null,
			isLoadingAttributes: false,
			productsAttributes: [],
		} );
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it.each( [
		{
			Adapter: ProductCategoryControl,
			allLabel: 'All selected categories',
			anyLabel: 'Any selected categories',
			className: 'woocommerce-product-categories',
			countType: 'review' as const,
			dataProp: 'categories',
			help: 'Pick at least two categories to use this setting.',
			itemClassName: 'woocommerce-product-categories__item',
			label: 'category',
			operatorClassName: 'woocommerce-product-categories__operator',
			showReviewCount: true,
		},
		{
			Adapter: ProductBrandControl,
			allLabel: 'All selected brands',
			anyLabel: 'Any selected brands',
			className: 'woocommerce-product-brands',
			countType: 'product' as const,
			dataProp: 'brands',
			help: 'Pick at least two brands to use this setting.',
			itemClassName: 'woocommerce-product-brands__item',
			label: 'brand',
			operatorClassName: 'woocommerce-product-brands__operator',
			showReviewCount: false,
		},
	] )(
		'normalizes $label data and configures the shared control',
		( {
			Adapter,
			allLabel,
			anyLabel,
			className,
			countType,
			dataProp,
			help,
			itemClassName,
			operatorClassName,
			showReviewCount,
		} ) => {
			const firstItem = createTaxonomyItem( 1, 'First' );
			const selectedItem = createTaxonomyItem( 2, 'Selected' );
			const onChange = jest.fn();
			const onOperatorChange = jest.fn();

			renderAdapter( Adapter, {
				[ dataProp ]: [ firstItem, selectedItem ],
				error: null,
				isCompact: true,
				isLoading: false,
				isSingle: true,
				onChange,
				onOperatorChange,
				operator: 'all',
				selected: [ selectedItem.id ],
				showReviewCount,
			} );

			const props = getLastTaxonomyProps();

			expect( props ).toMatchObject( {
				className,
				countType,
				isCompact: true,
				isHierarchical: true,
				isLoading: false,
				isSingle: true,
				itemClassName,
				onChange,
			} );
			expect( props.list ).toEqual( [
				expect.objectContaining( {
					details: firstItem,
					id: firstItem.id,
				} ),
				expect.objectContaining( {
					details: selectedItem,
					id: selectedItem.id,
				} ),
			] );
			expect( props.selected ).toEqual( [
				expect.objectContaining( {
					details: selectedItem,
					id: selectedItem.id,
				} ),
			] );
			expect( props.operator ).toMatchObject( {
				className: operatorClassName,
				labels: {
					all: allLabel,
					any: anyLabel,
					help,
				},
				onChange: onOperatorChange,
				selectedCount: 1,
				value: 'all',
			} );
		}
	);

	it( 'keeps raw tag selections and coalesces server search', async () => {
		jest.useFakeTimers();
		mockGetSetting.mockReturnValue( true );
		mockGetProductTags.mockResolvedValue( [
			{
				breadcrumbs: [],
				children: [],
				count: 3,
				id: 9,
				name: 'Sale',
				parent: 0,
				value: 'sale',
			},
		] );

		const onOperatorChange = jest.fn();

		renderAdapter( ProductTagControl, {
			onChange: jest.fn(),
			onOperatorChange,
			operator: 'all',
			selected: [ 9, 10 ],
		} );

		const initialProps = mockProductTaxonomyControl.mock.calls[ 0 ][ 0 ];

		expect( initialProps ).toMatchObject( {
			className: 'woocommerce-product-tags',
			isLoading: true,
			selected: [],
			operator: {
				className: 'woocommerce-product-tags__operator',
				labels: {
					all: 'All selected tags',
					any: 'Any selected tags',
					help: 'Pick at least two tags to use this setting.',
				},
				onChange: onOperatorChange,
				selectedCount: 2,
				value: 'all',
			},
		} );
		expect( initialProps ).not.toHaveProperty( 'countType' );
		expect( mockGetProductTags ).toHaveBeenCalledWith( {
			search: '',
			selected: [ 9, 10 ],
		} );

		await act( async () => {
			await Promise.resolve();
		} );

		const loadedProps = getLastTaxonomyProps();
		const onSearch = loadedProps.onSearch;

		expect( loadedProps.list ).toEqual( [
			expect.objectContaining( { id: 9 } ),
		] );
		expect( loadedProps.selected ).toEqual( [
			expect.objectContaining( { id: 9 } ),
		] );
		expect( loadedProps.operator?.selectedCount ).toBe( 2 );

		if ( ! onSearch ) {
			throw new Error( 'The tag adapter did not provide server search.' );
		}

		act( () => {
			onSearch( 's' );
			onSearch( 'su' );
			onSearch( 'sum' );
			jest.advanceTimersByTime( 399 );
		} );
		expect( mockGetProductTags ).toHaveBeenCalledTimes( 1 );

		await act( async () => {
			jest.advanceTimersByTime( 1 );
			await Promise.resolve();
		} );

		expect( mockGetProductTags ).toHaveBeenLastCalledWith( {
			search: 'sum',
			selected: [ 9, 10 ],
		} );
		expect( mockGetProductTags ).toHaveBeenCalledTimes( 2 );
	} );

	it( 'normalizes attribute parents and terms for the custom renderer', () => {
		const onOperatorChange = jest.fn();

		mockUseProductAttributes.mockReturnValue( {
			errorLoadingAttributes: null,
			isLoadingAttributes: false,
			productsAttributes: [
				{
					count: 1,
					has_archives: true,
					id: 7,
					label: 'Color',
					name: 'Color',
					orderby: 'name',
					parent: 0,
					taxonomy: 'pa_color',
					terms: [
						{
							attr_slug: 'pa_color',
							count: 3,
							description: '',
							id: 12,
							name: 'Blue',
							parent: 7,
							slug: 'blue',
						},
					],
					type: 'select',
				},
			],
		} );

		renderAdapter( ProductAttributeTermControl, {
			instanceId: 'test',
			isCompact: true,
			onChange: jest.fn(),
			onOperatorChange,
			operator: 'any',
			selected: [ { id: 12 } ],
			type: 'token',
		} );

		const props = getLastTaxonomyProps();

		expect( mockUseProductAttributes ).toHaveBeenCalledWith( true );
		expect( props ).toMatchObject( {
			className: 'woocommerce-product-attributes',
			isHierarchical: true,
			type: 'token',
		} );
		expect( props.list ).toEqual( [
			expect.objectContaining( {
				id: -7,
				name: 'Color',
				parent: 0,
			} ),
			expect.objectContaining( {
				id: 12,
				name: 'Blue',
				parent: -7,
				value: 'pa_color',
			} ),
		] );
		expect( props.selected ).toEqual( [
			expect.objectContaining( { id: 12, parent: -7 } ),
		] );
		expect( props.renderItem ).toEqual( expect.any( Function ) );
		expect( props.operator ).toMatchObject( {
			className: 'woocommerce-product-attributes__operator',
			labels: {
				all: 'All selected attributes',
				any: 'Any selected attributes',
				help: 'Pick at least two attributes to use this setting.',
			},
			onChange: onOperatorChange,
			selectedCount: 1,
			value: 'any',
		} );
	} );

	it.each( [
		{
			Adapter: ProductCategoryControl,
			dataProp: 'categories',
		},
		{
			Adapter: ProductBrandControl,
			dataProp: 'brands',
		},
	] )( 'forwards $dataProp errors', ( { Adapter, dataProp } ) => {
		const error = { message: 'Unable to load terms', type: 'general' };

		renderAdapter( Adapter, {
			[ dataProp ]: [],
			error,
			onChange: jest.fn(),
			selected: [],
		} );

		expect( getLastTaxonomyProps().error ).toEqual( error );
	} );

	it( 'forwards attribute errors', () => {
		const error = {
			message: 'Unable to load attributes',
			type: 'general',
		};
		mockUseProductAttributes.mockReturnValue( {
			errorLoadingAttributes: error,
			isLoadingAttributes: false,
			productsAttributes: [],
		} );

		renderAdapter( ProductAttributeTermControl, {
			instanceId: 'test',
			onChange: jest.fn(),
			selected: [],
		} );

		expect( getLastTaxonomyProps().error ).toEqual( error );
	} );
} );
