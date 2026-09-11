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

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
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
	Notice: ( { children }: { children: React.ReactNode } ) => (
		<div role="alert">{ children }</div>
	),
} ) );

// Stands in for a chunk request that fails: the dynamic import rejects.
jest.mock( '../settings-email-listing-listview', () => {
	throw new Error( 'Loading chunk failed' );
} );

jest.mock( '../settings-email-listing-data', () => ( {
	recreateEmailPostRequest: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	dispatch: () => ( { createErrorNotice: jest.fn() } ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: ( path: string ) => `https://example.com/wp-admin/${ path }`,
} ) );

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
	recipients: { to: '', cc: '', bcc: '' },
	status: 'enabled',
	templateStatus: null,
	templateVersion: null,
	currentVersion: null,
	wasBackfilled: false,
};

describe( 'EmailListingFill when the list view chunk fails to load', () => {
	it( 'shows a notice and keeps the rest of the fill', async () => {
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
	} );
} );
