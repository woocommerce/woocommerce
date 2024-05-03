/**
 * @jest-environment jsdom
 */
/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AdvancedFilters from '../';

const ORDER_STATUSES = {
	cancelled: 'Cancelled',
	completed: 'Completed',
	failed: 'Failed',
	'on-hold': 'On hold',
	pending: 'Pending payment',
	processing: 'Processing',
	refunded: 'Refunded',
};

const CURRENCY = {
	code: 'USD',
	decimalSeparator: '.',
	precision: 2,
	priceFormat: '%1$s%2$s',
	symbol: '$',
	symbolPosition: 'left',
	thousandSeparator: ',',
};

const advancedFiltersConfig = {
	title: 'Orders Match <select/> Filters',
	filters: {
		status: {
			labels: {
				add: 'Order Status',
				remove: 'Remove order status filter',
				rule: 'Select an order status filter match',
				title: '<title>Order Status</title> <rule/> <filter/>',
				filter: 'Select an order status',
			},
			rules: [
				{
					value: 'is',
					label: 'Is',
				},
				{
					value: 'is_not',
					label: 'Is Not',
				},
			],
			input: {
				component: 'SelectControl',
				options: Object.keys( ORDER_STATUSES ).map( ( key ) => ( {
					value: key,
					label: ORDER_STATUSES[ key ],
				} ) ),
			},
		},
		product: {
			labels: {
				add: 'Products',
				placeholder: 'Search products',
				remove: 'Remove products filter',
				rule: 'Select a product filter match',
				title: '<title>Product</title> <rule/> <filter/>',
				filter: 'Select products',
			},
			rules: [
				{
					value: 'includes',
					label: 'Includes',
				},
				{
					value: 'excludes',
					label: 'Excludes',
				},
			],
			input: {
				component: 'Search',
				type: 'products',
				getLabels: () => Promise.resolve( [] ),
			},
		},
		customer: {
			labels: {
				add: 'Customer Type',
				remove: 'Remove customer filter',
				rule: 'Select a customer filter match',
				title: '<title>Customer is</title> <filter/>',
				filter: 'Select a customer type',
			},
			input: {
				component: 'SelectControl',
				options: [
					{ value: 'new', label: 'New' },
					{ value: 'returning', label: 'Returning' },
				],
				defaultOption: 'new',
			},
		},
		quantity: {
			labels: {
				add: 'Item Quantity',
				remove: 'Remove item quantity filter',
				rule: 'Select an item quantity filter match',
				title: '<title>Item Quantity is</title> <rule/> <filter/>',
			},
			rules: [
				{
					value: 'lessthan',
					label: 'Less Than',
				},
				{
					value: 'morethan',
					label: 'More Than',
				},
				{
					value: 'between',
					label: 'Between',
				},
			],
			input: {
				component: 'Number',
			},
		},
		subtotal: {
			labels: {
				add: 'Subtotal',
				remove: 'Remove subtotal filter',
				rule: 'Select a subtotal filter match',
				title: '<title>Subtotal is</title> <rule/> <filter/>',
			},
			rules: [
				{
					value: 'lessthan',
					label: 'Less Than',
				},
				{
					value: 'morethan',
					label: 'More Than',
				},
				{
					value: 'between',
					label: 'Between',
				},
			],
			input: {
				component: 'Number',
				type: 'currency',
			},
		},
		date: {
			labels: {
				add: 'Date',
				remove: 'Remove date filter',
				rule: 'Select a date filter match',
				title: '<title>Date</title> <rule/> <filter/>',
				filter: 'Select a transaction date',
			},
			rules: [
				{
					value: 'before',
					label: 'Before',
				},
				{
					value: 'after',
					label: 'After',
				},
				{
					value: 'between',
					label: 'Between',
				},
			],
			input: {
				component: 'Date',
			},
		},
		test: {
			labels: {
				add: 'Test',
				remove: 'Remove test filter',
				rule: 'Select a test filter match',
				title: '{{title}}Test title{{/title}} {{filter/}}',
				filter: 'Select a test type',
			},
			input: {
				component: 'SelectControl',
				options: [
					{ value: 'new', label: 'New' },
					{ value: 'old', label: 'Old' },
				],
				defaultOption: 'new',
			},
		},
	},
};

const AdvancedFiltersComponent = ( props = null ) => (
	<AdvancedFilters
		siteLocale="en_US"
		path=""
		query={ { component: 'advanced-filters' } }
		filterTitle="Orders"
		config={ advancedFiltersConfig }
		currency={ CURRENCY }
		{ ...props }
	/>
);

describe( 'AdvancedFilters', () => {
	test( 'should render', () => {
		const { getByRole } = render( <AdvancedFiltersComponent /> );
		expect(
			getByRole( 'button', { name: 'Add a filter' } )
		).toBeInTheDocument();
	} );

	test( 'should retain value/state when the rule is switched from "Before" to "After" in <DateFilter />', () => {
		render( <AdvancedFiltersComponent /> );

		// Add a new filter.
		userEvent.click(
			screen.getByRole( 'button', { name: 'Add a filter' } )
		);

		// Add a "Before" date filter.
		userEvent.click( screen.getByRole( 'button', { name: 'Date' } ) );

		// Add a date in mm/dd/yyyy format.
		userEvent.type(
			screen.getByRole( 'textbox', { name: 'Choose a date' } ),
			'01/01/2020'
		);

		// Switch the date filter from "Before" to "After".
		userEvent.selectOptions(
			screen.getByRole( 'combobox', {
				name: 'Select a date filter match',
			} ),
			'after'
		);

		// The previous value should be retained when switching between the "Before" and "After" rules.
		expect(
			screen.getByRole( 'textbox', { name: 'Choose a date' } )
		).toHaveValue( '01/01/2020' );
	} );

	test( 'should reset value/state when the rule is switched to/from "Between" in <DateFilter />', () => {
		render( <AdvancedFiltersComponent /> );

		// Add a new filter.
		userEvent.click(
			screen.getByRole( 'button', { name: 'Add a filter' } )
		);

		// Add a "Before" date filter.
		userEvent.click( screen.getByRole( 'button', { name: 'Date' } ) );

		// Add a date in mm/dd/yyyy format.
		userEvent.type(
			screen.getByRole( 'textbox', { name: 'Choose a date' } ),
			'01/01/2020'
		);

		// Switch the date filter from "Before" to "Between".
		userEvent.selectOptions(
			screen.getByRole( 'combobox', {
				name: 'Select a date filter match',
			} ),
			'between'
		);

		const dateFields = screen.getAllByRole( 'textbox', {
			name: 'Choose a date',
		} );

		// The previous value should be reset when switching to/from the "Between" rule.
		expect( dateFields[ 0 ] ).not.toHaveValue();
		expect( dateFields[ 1 ] ).not.toHaveValue();
	} );

	test( 'should render the Test option component set in the old way', () => {
		render( <AdvancedFiltersComponent /> );

		// Add a new filter.
		userEvent.click(
			screen.getByRole( 'button', { name: 'Add a filter' } )
		);

		// Select Test filter.
		userEvent.click( screen.getByRole( 'button', { name: 'Test' } ) );
		expect(
			screen.getByRole( 'combobox', { name: 'Select a test type' } )
		).toHaveValue();
	} );
} );
