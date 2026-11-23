/**
 * External dependencies
 */
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';
import { useUser } from '@woocommerce/data';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

jest.mock( '@woocommerce/data', () => ( {
	useUser: jest.fn(),
} ) );

jest.mock( '../../../../../utils/functions', () => ( {
	...jest.requireActual( '../../../../../utils/functions' ),
	addNotice: jest.fn(),
	removeNotice: jest.fn(),
	getInstallUrl: jest.fn( () =>
		Promise.resolve(
			'https://woocommerce.com/install/?product-key=k&token=t&signature=s&utm_source=helper'
		)
	),
} ) );

/**
 * Internal dependencies
 */
import Install from '../install';
import { Subscription } from '../../../types';

const subscription = {
	product_key: 'product-key',
	product_name: 'Product Add-Ons',
	product_id: 18618,
	zip_slug: 'woocommerce-product-addons',
	version: '8.0.0',
	is_installable: false,
} as unknown as Subscription;

function mockCanInstall( canInstall: boolean ) {
	( useUser as jest.Mock ).mockReturnValue( {
		currentUserCan: () => canInstall,
	} );
}

describe( 'Install', () => {
	it( 'renders nothing for users who cannot install plugins', () => {
		mockCanInstall( false );
		const { container } = render(
			<Install subscription={ subscription } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'leaves the credentials out of the tracked install URL', async () => {
		mockCanInstall( true );
		const open = jest.spyOn( window, 'open' ).mockImplementation();
		render( <Install subscription={ subscription } /> );

		fireEvent.click( screen.getByRole( 'button', { name: 'Install' } ) );

		await waitFor( () =>
			expect( recordEvent ).toHaveBeenCalledWith(
				'marketplace_product_install_url',
				expect.objectContaining( {
					product_install_url:
						'https://woocommerce.com/install/?utm_source=helper',
				} )
			)
		);
		expect( open ).toHaveBeenCalledWith(
			'https://woocommerce.com/install/?product-key=k&token=t&signature=s&utm_source=helper',
			'_self'
		);
	} );
} );
