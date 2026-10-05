import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Block from '../block';
vi.mock( '@woocommerce/settings', async () => {
	return ( ( mock ) => ( { default: mock, ...mock } ) )( {
		...( await vi.importActual( '@woocommerce/settings' ) ),
		getSetting: vi.fn().mockImplementation( ( key, defaultValue ) => {
			if ( key === 'localPickupText' ) {
				return 'Pickup text from settings';
			}
			return defaultValue;
		} ),
	} );
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
