/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import D3Legend from '../legend';

const colorScheme = jest.fn();
const data = [
	{
		key: 'lorem',
		label: 'Lorem',
		visible: true,
		total: 100,
	},
	{
		key: 'ipsum',
		label: 'Ipsum',
		visible: true,
		total: 100,
	},
];

describe( 'Legend', () => {
	test( 'renders toggles for each dataset', () => {
		const { getByRole } = render(
			<D3Legend colorScheme={ colorScheme } data={ data } />
		);

		expect( getByRole( 'checkbox', { name: /Lorem/ } ) ).toBeEnabled();
		expect( getByRole( 'checkbox', { name: /Ipsum/ } ) ).toBeEnabled();
	} );

	test( 'should prevent toggling off of last active dataset', () => {
		const dataset = { ...data };
		// Leave only the first dataset active.
		dataset[ 1 ].visible = false;

		const { getByRole } = render(
			<D3Legend colorScheme={ colorScheme } data={ data } />
		);

		expect( getByRole( 'checkbox', { name: /Lorem/ } ) ).toBeDisabled();
		expect( getByRole( 'checkbox', { name: /Ipsum/ } ) ).toBeEnabled();
	} );
} );
