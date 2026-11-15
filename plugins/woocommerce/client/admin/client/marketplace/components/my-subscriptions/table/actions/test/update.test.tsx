/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: () => ( {
		updatePhpUrl: 'https://wordpress.org/support/update-php/',
	} ),
} ) );

jest.mock( '../../../../../utils/functions', () => ( {
	...jest.requireActual( '../../../../../utils/functions' ),
	addNotice: jest.fn(),
	removeNotice: jest.fn(),
	updateProduct: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import Update from '../update';
import { Subscription } from '../../../types';
import { addNotice, updateProduct } from '../../../../../utils/functions';

const subscription = {
	product_key: 'product-key',
	product_name: 'Product Add-Ons',
	product_id: 18618,
	zip_slug: 'woocommerce-product-addons',
	version: '8.0.0',
	active: true,
	update_requires_php_met: false,
	local: {
		installed: true,
		slug: 'woocommerce-product-addons',
		path: 'woocommerce-product-addons/woocommerce-product-addons.php',
	},
} as unknown as Subscription;

describe( 'Update', () => {
	it( 'explains the PHP requirement instead of updating when the site’s PHP is too old', () => {
		render(
			<Update subscription={ subscription } wooUpdateManagerActive />
		);

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Update to 8.0.0' } )
		);

		expect( updateProduct ).not.toHaveBeenCalled();
		expect( addNotice ).toHaveBeenCalledWith(
			'product-key',
			'Product Add-Ons couldn’t be updated. This version requires a newer version of PHP.',
			'error',
			{
				actions: [
					{
						label: 'Learn more',
						url: 'https://wordpress.org/support/update-php/',
						openInNewTab: true,
					},
				],
			}
		);
		expect( recordEvent ).toHaveBeenCalledWith(
			'marketplace_product_update_failed',
			expect.objectContaining( {
				error_code: 'incompatible_php_required_version',
			} )
		);
	} );
} );
