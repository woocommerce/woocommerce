/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import React from 'react';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
	queueRecordEvent: jest.fn(),
} ) );

jest.mock( '../../../../../utils/admin-settings', () => ( {
	getAdminSetting: jest.fn( () => ( { wooUpdateManagerActive: true } ) ),
} ) );

/**
 * Internal dependencies
 */
import { subscriptionRow } from '../table-rows';
import {
	AvailableSubscriptionsTable,
	InstalledSubscriptionsTable,
} from '../table';
import { Subscription } from '../../types';

const subscription = {
	product_key: 'test-key',
	product_id: 123,
	product_name: 'Test Extension',
	product_url: 'https://woocommerce.com/products/test-extension/',
	zip_slug: 'test-extension',
	expires: 0,
	expired: false,
	expiring: false,
	lifetime: false,
	autorenew: true,
	active: true,
	is_shared: false,
	version: '1.0.0',
	local: {
		installed: true,
		installable: true,
		active: true,
		version: '1.0.0',
		type: 'plugin',
		slug: 'test-extension',
		path: 'test-extension/test-extension.php',
		auto_update: false,
		auto_update_manageable: true,
		updates_from_wccom: true,
	},
} as unknown as Subscription;

describe( 'subscriptionRow', () => {
	it( 'gives installed rows one cell per header, including automatic updates', () => {
		render(
			<InstalledSubscriptionsTable
				rows={ [ subscriptionRow( subscription, 'installed' ) ] }
				isLoading={ false }
			/>
		);

		expect( screen.getAllByRole( 'cell' ) ).toHaveLength(
			screen.getAllByRole( 'columnheader' ).length
		);
		expect(
			screen.getByRole( 'columnheader', { name: 'Automatic updates' } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Enable auto-updates' } )
		).toBeInTheDocument();
	} );

	it( 'gives available rows one cell per header, without automatic updates', () => {
		render(
			<AvailableSubscriptionsTable
				rows={ [ subscriptionRow( subscription, 'available' ) ] }
				isLoading={ false }
			/>
		);

		expect( screen.getAllByRole( 'cell' ) ).toHaveLength(
			screen.getAllByRole( 'columnheader' ).length
		);
		expect(
			screen.queryByRole( 'columnheader', { name: 'Automatic updates' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Enable auto-updates' } )
		).not.toBeInTheDocument();
	} );
} );
