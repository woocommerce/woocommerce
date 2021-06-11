/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Card from '../';

describe( 'Card', () => {
	test( 'it renders correctly', () => {
		const { container, getByRole } = render(
			<Card title="A Card Example" />
		);
		expect( container ).toMatchSnapshot();

		// should have correct title
		expect(
			getByRole( 'heading', { name: 'A Card Example' } )
		).toBeInTheDocument();

		// should have correct class
		expect(
			container.getElementsByClassName( 'woocommerce-card' )
		).toHaveLength( 1 );
	} );
} );
