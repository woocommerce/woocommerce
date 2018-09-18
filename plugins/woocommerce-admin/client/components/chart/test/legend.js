/**
 * External dependencies
 *
 * @format
 */
import { shallow } from 'enzyme';

/**
 * Internal dependencies
 */
import Legend from '../legend';

const colorScheme = jest.fn();
const data = [
	{
		key: 'lorem',
		visible: true,
		total: 100,
	},
	{
		key: 'ipsum',
		visible: true,
		total: 100,
	},
];

describe( 'Legend', () => {
	test( 'should not disable any button if more than one is active', () => {
		const topSellingProducts = shallow( <Legend colorScheme={ colorScheme } data={ data } /> );

		expect( topSellingProducts.find( 'button' ).get( 0 ).props.disabled ).toBeFalsy();
		expect( topSellingProducts.find( 'button' ).get( 1 ).props.disabled ).toBeFalsy();
	} );

	test( 'should disable the last active button', () => {
		data[ 1 ].visible = false;

		const topSellingProducts = shallow( <Legend colorScheme={ colorScheme } data={ data } /> );

		expect( topSellingProducts.find( 'button' ).get( 0 ).props.disabled ).toBeTruthy();
		expect( topSellingProducts.find( 'button' ).get( 1 ).props.disabled ).toBeFalsy();
	} );
} );
