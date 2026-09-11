/*
 * @jest-environment-options {"url": "http://woo.local/"}
 */

/**
 * External dependencies
 */
import { act, render, screen, within } from '@testing-library/react';
import * as hooks from '@woocommerce/base-context/hooks';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AttributeFilterBlock from '../block';
import { BlockAttributes } from '../types';

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	__esModule: true,
	...jest.requireActual( '@woocommerce/base-context/hooks' ),
} ) );

const setWindowUrl = ( { url }: { url: string } ) => {
	/*
	 * jsdom (>= 21) makes `window.location` non-configurable, so navigate via
	 * the History API instead of replacing the object. Same-origin only (see
	 * the `@jest-environment-options` url above).
	 */
	window.history.replaceState( {}, '', url );
};

// Captured before any test navigates, so each test starts from the env URL.
const initialUrl = window.location.href;

afterEach( () => {
	window.history.replaceState( {}, '', initialUrl );
} );

const stubProductsAttributesTerms = () => [
	{
		id: 25,
		name: 'Large',
		slug: 'large',
		description: '',
		parent: 0,
		count: 1,
	},
	{
		id: 26,
		name: 'Medium',
		slug: 'medium',
		description: '',
		parent: 0,
		count: 1,
	},
	{
		id: 27,
		name: 'Small',
		slug: 'small',
		description: '',
		parent: 0,
		count: 1,
	},
];

const stubCollectionData = () => ( {
	price_range: null,
	attribute_counts: [
		{
			term: 25,
			count: 1,
		},
		{
			term: 26,
			count: 1,
		},
		{
			term: 27,
			count: 1,
		},
	],
	rating_counts: null,
	stock_status_counts: null,
} );

interface SetupParams {
	initialUrl: string;
	productAttributeTerms?: ReturnType< typeof stubProductsAttributesTerms >;
	collectionData?: ReturnType< typeof stubCollectionData >;
	queryState?: Record< string, unknown >;
}

const renderBlock = ( params: SetupParams ) => {
	const setupParams: SetupParams = {
		initialUrl: params.initialUrl || 'http://woo.local/',
	};
	const url =
		setupParams.initialUrl ||
		'http://woo.local/?filter_size=large&query_type_size=or';
	setWindowUrl( { url } );

	const attributes: BlockAttributes = {
		attributeId: 2,
		showCounts: true,
		queryType: 'or',
		heading: 'Size',
		headingLevel: 3,
		displayStyle: 'list',
		showFilterButton: true,
		selectType: 'single',
		isPreview: false,
	};
	jest.spyOn( hooks, 'useCollection' ).mockReturnValue( {
		results: params.productAttributeTerms || stubProductsAttributesTerms(),
		isLoading: false,
	} );

	jest.spyOn( hooks, 'useCollectionData' ).mockReturnValue( {
		data: params.collectionData || stubCollectionData(),
		isLoading: false,
	} );
	jest.spyOn( hooks, 'useQueryStateByContext' ).mockReturnValue( [
		params.queryState || {},
		jest.fn(),
	] );

	return render( <AttributeFilterBlock attributes={ attributes } /> );
};

const setup = ( params: SetupParams ) => {
	const utils = renderBlock( params );
	const applyButton = screen.getByRole( 'button', { name: /apply/i } );
	const smallAttributeCheckbox = screen.getByRole( 'checkbox', {
		name: /small/i,
	} );

	return {
		...utils,
		applyButton,
		smallAttributeCheckbox,
	};
};

interface SetupWithSelectedFilterAttributesParams {
	filterSize: 'large' | 'medium' | 'small';
}

const setupWithSelectedFilterAttributes = (
	params: SetupWithSelectedFilterAttributesParams
) => {
	const setupParams: SetupWithSelectedFilterAttributesParams = {
		filterSize: params?.filterSize || 'large',
	};
	const utils = setup( {
		initialUrl: `http://woo.local/?filter_size=${ setupParams.filterSize }&query_type_size=or`,
	} );

	return {
		...utils,
	};
};

const setupWithoutSelectedFilterAttributes = () => {
	const utils = setup( { initialUrl: 'http://woo.local/' } );

	return {
		...utils,
	};
};

describe( 'Filter by Attribute block', () => {
	test( 'passes active attribute and price filters to the count hook', () => {
		const queryState = {
			attributes: [
				{
					attribute: 'pa_size',
					operator: 'in',
					slug: [ 'small' ],
				},
			],
			min_price: '1500',
			max_price: '4000',
		};

		renderBlock( {
			initialUrl:
				'http://woo.local/?filter_size=small&query_type_size=or&min_price=15&max_price=40',
			queryState,
		} );

		expect( hooks.useCollectionData ).toHaveBeenCalledWith( {
			queryAttribute: {
				taxonomy: 'pa_size',
				queryType: 'or',
			},
			queryState,
			isEditor: false,
		} );
	} );

	test( 'maps each product count to its term by ID', () => {
		renderBlock( {
			initialUrl: 'http://woo.local/',
			productAttributeTerms: [
				stubProductsAttributesTerms()[ 2 ],
				stubProductsAttributesTerms()[ 0 ],
				stubProductsAttributesTerms()[ 1 ],
			],
			collectionData: {
				...stubCollectionData(),
				attribute_counts: [
					{ term: 26, count: 13 },
					{ term: 27, count: 2 },
					{ term: 25, count: 7 },
				],
			},
			queryState: {
				attributes: [
					{
						attribute: 'pa_size',
						operator: 'in',
						slug: [ 'large', 'medium', 'small' ],
					},
				],
			},
		} );

		[
			{ name: 'Large', count: 7 },
			{ name: 'Medium', count: 13 },
			{ name: 'Small', count: 2 },
		].forEach( ( { name, count } ) => {
			const label = screen.getByText( name ).closest( 'label' );
			const visualCount = label?.querySelector(
				'.wc-filter-element-label-list-count [aria-hidden="true"]'
			);
			const screenReaderCount = label?.querySelector(
				'.screen-reader-text'
			);

			expect( label ).not.toBeNull();
			expect(
				within( label as HTMLLabelElement ).getByRole( 'checkbox' )
			).toBeInTheDocument();
			expect( visualCount ).toHaveTextContent( count.toString() );
			expect( screenReaderCount ).toHaveTextContent(
				`${ count } products`
			);
			expect( screenReaderCount ).toHaveClass( 'screen-reader-text' );
		} );
	} );

	describe( 'Given no filter attribute is selected when page loads', () => {
		test( 'should disable Apply button when page loads', () => {
			const { applyButton } = setupWithoutSelectedFilterAttributes();

			expect( applyButton ).toBeDisabled();
		} );

		test( 'should enable Apply button when filter attributes are changed', async () => {
			const { applyButton, smallAttributeCheckbox } =
				setupWithoutSelectedFilterAttributes();

			await act( async () => {
				await userEvent.click( smallAttributeCheckbox );
			} );
			expect( applyButton ).not.toBeDisabled();
		} );
	} );

	describe( 'Given filter attribute is already selected when page loads', () => {
		test( 'should disable Apply button when page loads', () => {
			const { applyButton } = setupWithSelectedFilterAttributes();

			expect( applyButton ).toBeDisabled();
		} );

		test( 'should enable Apply button when filter attributes are changed', async () => {
			const { applyButton, smallAttributeCheckbox } =
				setupWithSelectedFilterAttributes();

			await act( async () => {
				await userEvent.click( smallAttributeCheckbox );
			} );
			expect( applyButton ).not.toBeDisabled();
		} );

		test( 'should disable Apply button when deselecting the same previously selected attribute', async () => {
			const { applyButton, smallAttributeCheckbox } =
				setupWithSelectedFilterAttributes( { filterSize: 'small' } );

			await act( async () => {
				await userEvent.click( smallAttributeCheckbox );
			} );
			expect( applyButton ).not.toBeDisabled();

			await act( async () => {
				await userEvent.click( smallAttributeCheckbox );
			} );
			expect( applyButton ).toBeDisabled();
		} );
	} );
} );
