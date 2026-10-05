import { describe, expect, it, vi } from 'vitest';

/**
 * The list view loads as its own chunk. When that request fails the fill must
 * keep the rest of the slot (description, "Edit template" button) and show a
 * notice instead of unmounting everything.
 */

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	EmailListingFill,
	type EmailType,
} from '../settings-email-listing-slotfill';
vi.mock( '@woocommerce/tracks', () => {
	const mock = {
		recordEvent: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/components', () => {
	const mock = {
		createSlotFill: () => ( {
			Fill: ( { children }: { children: React.ReactNode } ) => (
				<div>{ children }</div>
			),
		} ),
		Button: ( { children }: { children: React.ReactNode } ) => (
			<button>{ children }</button>
		),
		Notice: ( { children }: { children: React.ReactNode } ) => (
			<div role="alert">{ children }</div>
		),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Stands in for a chunk request that fails: the dynamic import rejects.
vi.mock( '../settings-email-listing-listview', () => {
	throw new Error( 'Loading chunk failed' );
} );
vi.mock( '../settings-email-listing-data', () => {
	const mock = {
		recreateEmailPostRequest: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/data', async () => {
	const mock = {
		...( await vi.importActual( '@wordpress/data' ) ),
		dispatch: () => ( {
			createErrorNotice: vi.fn(),
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/settings', () => {
	const mock = {
		getAdminLink: ( path: string ) =>
			`https://example.com/wp-admin/${ path }`,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const email: EmailType = {
	id: 'new-order',
	post_id: '123',
	file_template_preview_url: null,
	title: 'New order',
	description: '',
	enabled: true,
	manual: false,
	email_key: 'new_order',
	email_class_name: 'WC_Email_New_Order',
	recipients: {
		to: '',
		cc: '',
		bcc: '',
	},
	status: 'enabled',
	templateStatus: null,
	templateVersion: null,
	currentVersion: null,
	wasBackfilled: false,
};
describe( 'EmailListingFill when the list view chunk fails to load', () => {
	it( 'shows a notice and keeps the rest of the fill', async () => {
		const errorSpy = vi
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );
		render(
			<EmailListingFill
				emailTypes={ [ email ] }
				editTemplateUrl={ null }
				emailTemplateId={ null }
			/>
		);
		expect(
			await screen.findByText( /email list could not be loaded/i )
		).toBeInTheDocument();
		expect(
			screen.getByText( /Manage email notifications/ )
		).toBeInTheDocument();
		expect( errorSpy ).toHaveBeenCalledWith(
			expect.objectContaining( {
				cause: expect.objectContaining( {
					message: 'Loading chunk failed',
				} ),
			} )
		);
	} );
} );
