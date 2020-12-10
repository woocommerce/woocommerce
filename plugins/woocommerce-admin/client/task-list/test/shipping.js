/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';

/**
 * Internal dependencies
 */
import { Shipping } from '../tasks/shipping';

describe( 'TaskList > Shipping', () => {
	describe( 'Shipping', () => {
		afterEach( () => jest.clearAllMocks() );

		it( 'hides shipping setup when user has disabled shipping', () => {
			render(
				<Shipping
					settings={ {
						woocommerce_ship_to_countries: 'disabled',
					} }
				/>
			);

			expect( screen.queryByText( 'Set shipping costs' ) ).toBeNull();
		} );
	} );
} );
