/**
 * @jest-environment jsdom
 */
/**
 * External dependencies
 */
import { render, within } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Chart from '../';

jest.mock( '../d3chart', () => ( {
	D3Legend: jest.fn().mockReturnValue( '[D3Legend]' ),
} ) );

const data = [
	{
		date: '2018-05-30T00:00:00',
		Hoodie: {
			label: 'Hoodie',
			value: 21599,
		},
		Sunglasses: {
			label: 'Sunglasses',
			value: 38537,
		},
		Cap: {
			label: 'Cap',
			value: 106010,
		},
	},
	{
		date: '2018-05-31T00:00:00',
		Hoodie: {
			label: 'Hoodie',
			value: 14205,
		},
		Sunglasses: {
			label: 'Sunglasses',
			value: 24721,
		},
		Cap: {
			label: 'Cap',
			value: 70131,
		},
	},
];

describe( 'Chart', () => {
	test( '<Chart legendPosition="hidden" /> should not render any legend', () => {
		const { queryByText } = render(
			<Chart data={ data } legendPosition="hidden" />
		);
		expect( queryByText( '[D3Legend]' ) ).not.toBeInTheDocument();
	} );

	test( '<Chart legendPosition="bottom" /> should render the legend at the bottom', () => {
		const { container } = render(
			<Chart data={ data } legendPosition="bottom" />
		);
		const footer = container.querySelector( '.woocommerce-chart__footer' );
		expect(
			within( footer ).queryByText( '[D3Legend]' )
		).toBeInTheDocument();
	} );

	test( '<Chart legendPosition="side" /> should render the legend at the side', () => {
		const { container } = render(
			<Chart data={ data } legendPosition="side" />
		);
		const body = container.querySelector( '.woocommerce-chart__body' );
		expect(
			within( body ).queryByText( '[D3Legend]' )
		).toBeInTheDocument();
	} );

	test( '<Chart legendPosition="top" /> should render the legend at the top', () => {
		const { container } = render(
			<Chart data={ data } legendPosition="top" />
		);
		const top = container.querySelector( '.woocommerce-chart__header' );
		expect( within( top ).queryByText( '[D3Legend]' ) ).toBeInTheDocument();
	} );
} );
