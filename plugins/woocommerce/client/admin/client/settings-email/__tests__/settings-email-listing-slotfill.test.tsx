/**
 * Tests for the list-page aggregate `_list_viewed` Tracks event fired by
 * `EmailListingFill` on mount (RSM-145).
 *
 * The list emits one page-level event per browser session — not one per row —
 * with `eligible_count` and `total_count` only. Per-post drilldown is covered
 * by the editor-banner `_viewed` event.
 */

/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	EmailListingFill,
	type EmailType,
} from '../settings-email-listing-slotfill';

const recordEventMock = jest.fn();

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: ( name: string, payload: Record< string, unknown > ) =>
		recordEventMock( name, payload ),
} ) );

jest.mock( '@wordpress/components', () => ( {
	createSlotFill: () => ( {
		Fill: ( { children }: { children: React.ReactNode } ) => (
			<div>{ children }</div>
		),
	} ),
	Button: ( { children }: { children: React.ReactNode } ) => (
		<button>{ children }</button>
	),
} ) );

jest.mock( '../settings-email-listing-listview', () => ( {
	ListView: () => <div data-testid="listview" />,
} ) );

const baseEmail: EmailType = {
	id: 'new-order',
	post_id: '123',
	title: 'New order',
	description: '',
	enabled: true,
	manual: false,
	email_key: 'new_order',
	recipients: { to: '', cc: '', bcc: '' },
	status: 'enabled',
	templateStatus: null,
	templateVersion: null,
	currentVersion: null,
	wasBackfilled: false,
};

const eligibleEmail: EmailType = {
	...baseEmail,
	id: 'customer-processing',
	post_id: '456',
	templateStatus: 'core_updated_customized',
	templateVersion: '10.6.0',
	currentVersion: '10.7.0',
};

describe( 'EmailListingFill — list-page Tracks instrumentation', () => {
	beforeEach( () => {
		recordEventMock.mockClear();
		window.sessionStorage.clear();
	} );

	it( 'fires one woocommerce_block_email_list_viewed on mount with eligible_count and total_count', () => {
		render(
			<EmailListingFill
				emailTypes={ [ baseEmail, eligibleEmail ] }
				editTemplateUrl={ null }
			/>
		);

		expect( recordEventMock ).toHaveBeenCalledTimes( 1 );
		expect( recordEventMock ).toHaveBeenCalledWith(
			'woocommerce_block_email_list_viewed',
			expect.objectContaining( {
				viewed_from: 'email_list',
				eligible_count: 1,
				total_count: 2,
			} )
		);
	} );

	it( 'dedups within a session: a second mount in the same tab does not refire', () => {
		const { unmount } = render(
			<EmailListingFill
				emailTypes={ [ eligibleEmail ] }
				editTemplateUrl={ null }
			/>
		);
		unmount();
		render(
			<EmailListingFill
				emailTypes={ [ eligibleEmail ] }
				editTemplateUrl={ null }
			/>
		);

		expect( recordEventMock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'still fires when sessionStorage is unavailable (privacy-mode fallback)', () => {
		const setItemSpy = jest
			.spyOn( window.sessionStorage.__proto__, 'setItem' )
			.mockImplementation( () => {
				throw new Error( 'quota / privacy mode' );
			} );

		try {
			render(
				<EmailListingFill
					emailTypes={ [ eligibleEmail ] }
					editTemplateUrl={ null }
				/>
			);

			expect( recordEventMock ).toHaveBeenCalledTimes( 1 );
		} finally {
			setItemSpy.mockRestore();
		}
	} );

	it( 'reports eligible_count=0 when no rows are eligible', () => {
		render(
			<EmailListingFill
				emailTypes={ [ baseEmail, baseEmail ] }
				editTemplateUrl={ null }
			/>
		);

		expect( recordEventMock ).toHaveBeenCalledWith(
			'woocommerce_block_email_list_viewed',
			expect.objectContaining( { eligible_count: 0, total_count: 2 } )
		);
	} );
} );
