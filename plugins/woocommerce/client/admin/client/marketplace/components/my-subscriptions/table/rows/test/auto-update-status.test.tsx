/**
 * External dependencies
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import React from 'react';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
	queueRecordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

jest.mock( '../../../../../../utils/admin-settings', () => ( {
	getAdminSetting: jest.fn(),
} ) );

jest.mock( '../../../../../utils/functions', () => ( {
	setProductAutoUpdate: jest.fn( () => Promise.resolve() ),
	addNotice: jest.fn(),
	removeNotice: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import { speak } from '@wordpress/a11y';
import {
	setProductAutoUpdate,
	addNotice,
} from '../../../../../utils/functions';
import { getAdminSetting } from '../../../../../../utils/admin-settings';
import AutoUpdateStatus from '../auto-update-status';
import { SubscriptionsContext } from '../../../../../contexts/subscriptions-context';
import { SubscriptionsContextType } from '../../../../../contexts/types';
import { Subscription, SubscriptionLocal } from '../../../types';

const loadSubscriptions = jest.fn( () => Promise.resolve() );

function subscriptionWith(
	local: Partial< SubscriptionLocal >,
	subscription: Partial< Subscription > = {}
): Subscription {
	return {
		product_key: 'test-key',
		product_id: 123,
		product_name: 'Test Extension',
		zip_slug: 'test-extension',
		expired: false,
		lifetime: false,
		active: true,
		...subscription,
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
			...local,
		},
	} as Subscription;
}

function renderStatus( subscription: Subscription ) {
	return render(
		<SubscriptionsContext.Provider
			value={
				{ loadSubscriptions } as unknown as SubscriptionsContextType
			}
		>
			<AutoUpdateStatus subscription={ subscription } />
		</SubscriptionsContext.Provider>
	);
}

/**
 * Stand in the site-level settings, healthy unless a test says otherwise.
 */
function setSiteSettings( settings: Record< string, boolean > = {} ) {
	( getAdminSetting as jest.Mock ).mockReturnValue( {
		wooUpdateManagerActive: true,
		...settings,
	} );
}

describe( 'AutoUpdateStatus', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		setSiteSettings();
	} );

	it( 'renders nothing when the product is not installed', () => {
		const { container } = renderStatus(
			subscriptionWith( { installed: false } )
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'offers to enable auto-updates when they are off', () => {
		renderStatus( subscriptionWith( {} ) );

		expect(
			screen.getByRole( 'button', { name: 'Enable auto-updates' } )
		).toBeInTheDocument();
	} );

	it( 'offers to disable auto-updates when they are on and nothing blocks them', () => {
		renderStatus( subscriptionWith( { auto_update: true } ) );

		expect(
			screen.getByRole( 'button', { name: 'Disable auto-updates' } )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Blocked' ) ).not.toBeInTheDocument();
	} );

	it( 'shows the setting as text when it cannot be changed from here', async () => {
		renderStatus(
			subscriptionWith( {
				auto_update: true,
				auto_update_manageable: false,
			} )
		);

		expect(
			screen.queryByRole( 'button', { name: /auto-updates/ } )
		).not.toBeInTheDocument();
		fireEvent.click( screen.getByText( 'On' ) );

		expect(
			await screen.findByText(
				'Auto-updates for this product are controlled outside this screen.'
			)
		).toBeInTheDocument();
	} );

	it( 'shows off as text when it cannot be changed from here', () => {
		renderStatus( subscriptionWith( { auto_update_manageable: false } ) );

		expect( screen.getByText( 'Off' ) ).toBeInTheDocument();
	} );

	it( 'treats a theme like a plugin', () => {
		renderStatus(
			subscriptionWith( { type: 'theme', auto_update: true } )
		);

		expect(
			screen.getByRole( 'button', { name: 'Disable auto-updates' } )
		).toBeInTheDocument();
	} );

	it( 'never blocks a copy installed from WordPress.org', () => {
		setSiteSettings( { wooUpdateManagerActive: false } );
		renderStatus(
			subscriptionWith(
				{ auto_update: true, updates_from_wccom: false },
				{ product_key: '', expired: true, active: false }
			)
		);

		expect( screen.queryByText( 'Blocked' ) ).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Disable auto-updates' } )
		).toBeInTheDocument();
	} );

	it( 'shows blocked when the Update Manager is not active', async () => {
		setSiteSettings( { wooUpdateManagerActive: false } );
		renderStatus( subscriptionWith( { auto_update: true } ) );

		fireEvent.click( screen.getByText( 'Blocked' ) );

		expect(
			await screen.findByText(
				'WooCommerce.com Update Manager is not active, and it delivers these updates.'
			)
		).toBeInTheDocument();
	} );

	it( 'shows blocked for a theme for the same reasons as a plugin', async () => {
		setSiteSettings( { wooUpdateManagerActive: false } );
		renderStatus(
			subscriptionWith( { type: 'theme', auto_update: true } )
		);

		fireEvent.click( screen.getByText( 'Blocked' ) );

		expect(
			await screen.findByText(
				'WooCommerce.com Update Manager is not active, and it delivers these updates.'
			)
		).toBeInTheDocument();
	} );

	it( 'does not show blocked when auto-updates are off', () => {
		setSiteSettings( { wooUpdateManagerActive: false } );
		renderStatus( subscriptionWith( {} ) );

		expect( screen.queryByText( 'Blocked' ) ).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Enable auto-updates' } )
		).toBeInTheDocument();
	} );

	it( 'reports a missing subscription and stops there', async () => {
		renderStatus(
			subscriptionWith(
				{ auto_update: true },
				{ product_key: '', expired: true, active: false }
			)
		);

		fireEvent.click( screen.getByText( 'Blocked' ) );

		expect(
			await screen.findByText( 'There is no subscription for it.' )
		).toBeInTheDocument();
		expect(
			screen.queryByText( 'The subscription has expired.' )
		).not.toBeInTheDocument();
	} );

	it( 'lists an expired subscription and a disconnected one together', async () => {
		renderStatus(
			subscriptionWith(
				{ auto_update: true },
				{ expired: true, active: false }
			)
		);

		fireEvent.click( screen.getByText( 'Blocked' ) );

		expect(
			await screen.findByText( 'The subscription has expired.' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'The subscription is not connected to this store.'
			)
		).toBeInTheDocument();
	} );

	it( 'does not treat a lifetime subscription as expired', () => {
		renderStatus(
			subscriptionWith(
				{ auto_update: true },
				{ expired: true, lifetime: true }
			)
		);

		expect( screen.queryByText( 'Blocked' ) ).not.toBeInTheDocument();
	} );

	it( 'enables auto-updates, refreshes the row and announces the change', async () => {
		const subscription = subscriptionWith( {} );
		renderStatus( subscription );

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Enable auto-updates' } )
		);

		await waitFor( () =>
			expect( setProductAutoUpdate ).toHaveBeenCalledWith(
				subscription,
				true
			)
		);
		await waitFor( () =>
			expect( loadSubscriptions ).toHaveBeenCalledWith( false )
		);
		await waitFor( () =>
			expect( speak ).toHaveBeenCalledWith(
				'Auto-updates enabled for Test Extension.'
			)
		);
	} );

	it( 'disables auto-updates and announces the change', async () => {
		const subscription = subscriptionWith( { auto_update: true } );
		renderStatus( subscription );

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Disable auto-updates' } )
		);

		await waitFor( () =>
			expect( setProductAutoUpdate ).toHaveBeenCalledWith(
				subscription,
				false
			)
		);
		await waitFor( () =>
			expect( speak ).toHaveBeenCalledWith(
				'Auto-updates disabled for Test Extension.'
			)
		);
	} );

	it( 'shows a progress label while enabling', async () => {
		let finish: () => void = () => {};
		( setProductAutoUpdate as jest.Mock ).mockReturnValueOnce(
			new Promise< void >( ( resolve ) => {
				finish = resolve;
			} )
		);
		renderStatus( subscriptionWith( {} ) );

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Enable auto-updates' } )
		);

		// Disabled through aria-disabled, so focus stays on the control while it works.
		expect(
			await screen.findByRole( 'button', { name: 'Enabling…' } )
		).toHaveAttribute( 'aria-disabled', 'true' );

		finish();

		expect(
			await screen.findByRole( 'button', { name: 'Enable auto-updates' } )
		).not.toHaveAttribute( 'aria-disabled' );
	} );

	it( 'shows a progress label while disabling', async () => {
		( setProductAutoUpdate as jest.Mock ).mockReturnValueOnce(
			new Promise( () => {} )
		);
		renderStatus( subscriptionWith( { auto_update: true } ) );

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Disable auto-updates' } )
		);

		expect(
			await screen.findByRole( 'button', { name: 'Disabling…' } )
		).toHaveAttribute( 'aria-disabled', 'true' );
	} );

	it( "surfaces the endpoint's reason when enabling fails", async () => {
		// The shape wp_send_json_error() produces.
		( setProductAutoUpdate as jest.Mock ).mockRejectedValueOnce( {
			success: false,
			data: { message: 'There is no subscription for this product.' },
		} );
		renderStatus( subscriptionWith( {} ) );

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Enable auto-updates' } )
		);

		await waitFor( () =>
			expect( addNotice ).toHaveBeenCalledWith(
				'test-key',
				'Auto-updates could not be enabled. There is no subscription for this product.',
				'error'
			)
		);
	} );

	it( 'falls back to a generic notice when disabling fails without a reason', async () => {
		( setProductAutoUpdate as jest.Mock ).mockRejectedValueOnce( {
			code: 'invalid_json',
		} );
		renderStatus( subscriptionWith( { auto_update: true } ) );

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Disable auto-updates' } )
		);

		await waitFor( () =>
			expect( addNotice ).toHaveBeenCalledWith(
				'test-key',
				'Auto-updates could not be disabled.',
				'error'
			)
		);
	} );
} );
