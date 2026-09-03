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
import AutoUpdateStatus from '../auto-update-status';
import { SubscriptionsContext } from '../../../../../contexts/subscriptions-context';
import { SubscriptionsContextType } from '../../../../../contexts/types';
import { Subscription, SubscriptionLocal } from '../../../types';

const loadSubscriptions = jest.fn( () => Promise.resolve() );

function subscriptionWith( local: Partial< SubscriptionLocal > ): Subscription {
	return {
		product_key: 'test-key',
		product_id: 123,
		product_name: 'Test Extension',
		zip_slug: 'test-extension',
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

describe( 'AutoUpdateStatus', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'renders nothing when auto-updates are on', () => {
		const { container } = renderStatus(
			subscriptionWith( { auto_update: true } )
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders nothing when the product is not installed', () => {
		const { container } = renderStatus(
			subscriptionWith( { installed: false } )
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders nothing for a theme', () => {
		const { container } = renderStatus(
			subscriptionWith( { type: 'theme' } )
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'warns when auto-updates are off', () => {
		renderStatus( subscriptionWith( {} ) );

		expect(
			screen.getByText( 'Auto-updates are off' )
		).toBeInTheDocument();
	} );

	it( 'offers no action when auto-updates cannot be changed from here', async () => {
		renderStatus( subscriptionWith( { auto_update_manageable: false } ) );

		fireEvent.click( screen.getByText( 'Auto-updates are off' ) );

		await screen.findByText( /controlled outside this screen/ );

		expect(
			screen.queryByRole( 'button', { name: 'Enable auto-updates' } )
		).not.toBeInTheDocument();
	} );

	it( 'enables auto-updates, refreshes the row and announces the change', async () => {
		const subscription = subscriptionWith( {} );
		renderStatus( subscription );

		fireEvent.click( screen.getByText( 'Auto-updates are off' ) );
		fireEvent.click(
			await screen.findByRole( 'button', {
				name: 'Enable auto-updates',
			} )
		);

		await waitFor( () =>
			expect( setProductAutoUpdate ).toHaveBeenCalledWith(
				subscription,
				true
			)
		);
		await waitFor( () => expect( loadSubscriptions ).toHaveBeenCalled() );
		await waitFor( () =>
			expect( speak ).toHaveBeenCalledWith(
				'Auto-updates enabled for Test Extension.'
			)
		);
	} );

	it( 'surfaces a notice when enabling fails', async () => {
		( setProductAutoUpdate as jest.Mock ).mockRejectedValueOnce( {
			message: 'Nope.',
		} );
		renderStatus( subscriptionWith( {} ) );

		fireEvent.click( screen.getByText( 'Auto-updates are off' ) );
		fireEvent.click(
			await screen.findByRole( 'button', {
				name: 'Enable auto-updates',
			} )
		);

		await waitFor( () =>
			expect( addNotice ).toHaveBeenCalledWith(
				'test-key',
				'Nope.',
				'error'
			)
		);
	} );
} );
