/**
 * @jest-environment jsdom
 */
/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Chart from '../';

const data = [
	{
		date: '2018-05-30T00:00:00',
		Revenue: {
			label: 'Revenue',
			value: 21599,
		},
	},
	{
		date: '2018-05-31T00:00:00',
		Revenue: {
			label: 'Revenue',
			value: 14205,
		},
	},
];

const usd = {
	symbol: '$',
	symbolPosition: 'left',
	decimalSeparator: '.',
	thousandSeparator: ',',
};

const eur = {
	symbol: '€',
	symbolPosition: 'right_space',
	decimalSeparator: ',',
	thousandSeparator: '.',
};

const getYAxisLabels = ( container ) =>
	Array.from( container.querySelectorAll( '.axis.y-axis .tick text' ) ).map(
		( tick ) => tick.textContent
	);

describe( 'Chart currency', () => {
	beforeEach( () => {
		jest.spyOn(
			window.HTMLElement.prototype,
			'offsetWidth',
			'get'
		).mockReturnValue( 600 );
	} );

	test( 'should format the Y axis with the new currency when the currency changes', () => {
		const { container, rerender } = render(
			<Chart data={ data } currency={ usd } valueType="currency" />
		);
		expect( getYAxisLabels( container ).join( ' ' ) ).toContain( '$' );

		rerender(
			<Chart data={ data } currency={ eur } valueType="currency" />
		);
		const labels = getYAxisLabels( container ).join( ' ' );
		expect( labels ).toContain( '€' );
		expect( labels ).not.toContain( '$' );
	} );
} );
