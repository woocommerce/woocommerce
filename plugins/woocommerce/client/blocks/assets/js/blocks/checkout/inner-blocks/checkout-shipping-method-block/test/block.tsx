/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Block from '../block';
jest.mock( '@woocommerce/settings', () => {
	return {
		...jest.requireActual( '@woocommerce/settings' ),
		getSetting: jest.fn().mockImplementation( ( key, defaultValue ) => {
			if ( key === 'localPickupText' ) {
				return 'Pickup text from settings';
			}
			return defaultValue;
		} ),
	};
} );
describe( 'Block', () => {
	it( 'Renders the local pickup text from options', async () => {
		const { getByText } = render(
			<Block
				localPickupText="backup local pickup text"
				onChange={ () => void 0 }
				showIcon={ false }
				showPrice={ false }
				checked={ 'shipping' }
				shippingText="backup shipping text"
			/>
		);

		expect( getByText( 'Pickup text from settings' ) ).toBeInTheDocument();
	} );
} );
