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
import { render, screen, fireEvent, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import {
	EmailListingFill,
	normalizeEmailTypePayload,
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
	Button: ( {
		children,
		onClick,
		disabled,
		href,
	}: {
		children: React.ReactNode;
		onClick?: () => void;
		disabled?: boolean;
		href?: string;
	} ) =>
		href ? (
			<a href={ href }>{ children }</a>
		) : (
			<button onClick={ onClick } disabled={ disabled }>
				{ children }
			</button>
		),
} ) );

jest.mock( '../settings-email-listing-listview', () => ( {
	ListView: () => <div data-testid="listview" />,
} ) );

// The slotfill imports `recreateEmailPostRequest` from the data module, which
// transitively pulls in `@wordpress/core-data`; mock it out — these tests only
// exercise the Tracks instrumentation, payload normalization, and the
// "Edit template" lazy-creation flow.
jest.mock( '../settings-email-listing-data', () => ( {
	recreateEmailPostRequest: jest.fn(),
} ) );

const createErrorNoticeMock = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	dispatch: () => ( { createErrorNotice: createErrorNoticeMock } ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: ( path: string ) => `https://example.com/wp-admin/${ path }`,
} ) );

const baseEmail: EmailType = {
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

	it( 'fires one block_email_list_viewed on mount with eligible_count and total_count', () => {
		render(
			<EmailListingFill
				emailTypes={ [ baseEmail, eligibleEmail ] }
				editTemplateUrl={ null }
				emailTemplateId={ null }
			/>
		);

		expect( recordEventMock ).toHaveBeenCalledTimes( 1 );
		expect( recordEventMock ).toHaveBeenCalledWith(
			'block_email_list_viewed',
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
				emailTemplateId={ null }
			/>
		);
		unmount();
		render(
			<EmailListingFill
				emailTypes={ [ eligibleEmail ] }
				editTemplateUrl={ null }
				emailTemplateId={ null }
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
					emailTemplateId={ null }
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
				emailTemplateId={ null }
			/>
		);

		expect( recordEventMock ).toHaveBeenCalledWith(
			'block_email_list_viewed',
			expect.objectContaining( { eligible_count: 0, total_count: 2 } )
		);
	} );
} );

/**
 * Regression-witness tests for the snake_case→camelCase projection done by
 * `normalizeEmailTypePayload`.
 *
 * During manual testing of the RSM-145 list-page `_list_viewed` event we
 * discovered that the slotfill's raw `data-email-types` JSON carries snake_case
 * keys (`template_status`, `template_version`, `current_version`,
 * `was_backfilled`) — but `EmailListingFill.useEffect` calls
 * `shouldShowReviewUpdate(post)` which reads the camelCase TS fields. Without
 * a projection step, every eligible post is silently classified as ineligible
 * and `eligible_count` reports 0 regardless of how many posts are actually
 * divergent.
 *
 * Two tests below pin this down:
 *   1. Reproduction: a raw payload object missing camelCase fields renders as
 *      `eligible_count: 0` through `EmailListingFill`. This is the broken
 *      shape that previously reached the component before the fix.
 *   2. Fix verification: piping the same raw payload through
 *      `normalizeEmailTypePayload` first yields `eligible_count: 1`. This is
 *      what `registerSettingsEmailListingFill` now does at JSON-parse time.
 */
describe( 'normalizeEmailTypePayload — regression for eligible_count=0', () => {
	const rawDivergentRow = {
		// Exact shape PHP serializes into `data-email-types` (snake_case keys
		// for the template-sync meta fields; other fields match the TS type
		// case directly).
		id: 'customer_processing_order',
		post_id: '18',
		title: 'Processing order',
		description: '',
		enabled: true,
		manual: false,
		email_key: 'customer_processing_order',
		email_class_name: 'WC_Email_Customer_Processing_Order',
		recipients: { to: '', cc: '', bcc: '' },
		status: 'enabled',
		template_status: 'core_updated_customized',
		template_version: '9.4.0-test',
		current_version: '10.7.0',
		was_backfilled: false,
	};

	beforeEach( () => {
		recordEventMock.mockClear();
		window.sessionStorage.clear();
	} );

	it( 'witness: rendering raw snake_case payload directly yields eligible_count=0 (the regression we observed in browser)', () => {
		// Casting to EmailType bypasses the type system to reproduce the
		// runtime shape that reached EmailListingFill before the fix.
		const rawAsEmailType = rawDivergentRow as unknown as EmailType;

		render(
			<EmailListingFill
				emailTypes={ [ rawAsEmailType ] }
				editTemplateUrl={ null }
				emailTemplateId={ null }
			/>
		);

		expect( recordEventMock ).toHaveBeenCalledWith(
			'block_email_list_viewed',
			expect.objectContaining( {
				eligible_count: 0,
				total_count: 1,
			} )
		);
	} );

	it( 'fix: normalizeEmailTypePayload projects snake_case meta so eligible_count=1', () => {
		const normalized = normalizeEmailTypePayload( rawDivergentRow );

		// Projection writes the camelCase fields shouldShowReviewUpdate reads.
		expect( normalized.templateStatus ).toBe( 'core_updated_customized' );
		expect( normalized.templateVersion ).toBe( '9.4.0-test' );
		expect( normalized.currentVersion ).toBe( '10.7.0' );
		expect( normalized.wasBackfilled ).toBe( false );
		expect( normalized.email_class_name ).toBe(
			'WC_Email_Customer_Processing_Order'
		);

		render(
			<EmailListingFill
				emailTypes={ [ normalized ] }
				editTemplateUrl={ null }
				emailTemplateId={ null }
			/>
		);

		expect( recordEventMock ).toHaveBeenCalledWith(
			'block_email_list_viewed',
			expect.objectContaining( {
				eligible_count: 1,
				total_count: 1,
			} )
		);
	} );

	it( 'projects was_backfilled=1 (number) and true (bool) consistently', () => {
		const numericTrue = normalizeEmailTypePayload( {
			...rawDivergentRow,
			was_backfilled: 1,
		} );
		const boolTrue = normalizeEmailTypePayload( {
			...rawDivergentRow,
			was_backfilled: true,
		} );

		expect( numericTrue.wasBackfilled ).toBe( true );
		expect( boolTrue.wasBackfilled ).toBe( true );
	} );

	it( 'treats missing template_status as null (not undefined)', () => {
		const { template_status: _omit, ...withoutStatus } = rawDivergentRow;
		const normalized = normalizeEmailTypePayload( withoutStatus );

		expect( normalized.templateStatus ).toBeNull();
	} );
} );

describe( 'EditTemplateButton — lazy post creation', () => {
	const TEMPLATE_ID = 'my-theme//wooemailtemplate';

	const { recreateEmailPostRequest } = jest.requireMock(
		'../settings-email-listing-data'
	) as { recreateEmailPostRequest: jest.Mock };

	const originalLocation = window.location;

	beforeAll( () => {
		// jsdom throws on real navigation; replace location with a writable stub.
		Object.defineProperty( window, 'location', {
			writable: true,
			value: { ...originalLocation, href: '' },
		} );
	} );

	afterAll( () => {
		Object.defineProperty( window, 'location', {
			writable: true,
			value: originalLocation,
		} );
	} );

	beforeEach( () => {
		recordEventMock.mockClear();
		recreateEmailPostRequest.mockReset();
		createErrorNoticeMock.mockClear();
		window.sessionStorage.clear();
		window.location.href = '';
	} );

	it( 'links directly to the template editor when editTemplateUrl is provided', () => {
		render(
			<EmailListingFill
				emailTypes={ [ baseEmail ] }
				editTemplateUrl="https://example.com/wp-admin/site-editor.php"
				emailTemplateId={ TEMPLATE_ID }
			/>
		);

		expect(
			screen.getByRole( 'link', { name: 'Edit template' } )
		).toHaveAttribute(
			'href',
			'https://example.com/wp-admin/site-editor.php'
		);
	} );

	it( 'renders no button when there is no URL and no template id', () => {
		render(
			<EmailListingFill
				emailTypes={ [ baseEmail ] }
				editTemplateUrl={ null }
				emailTemplateId={ null }
			/>
		);

		expect( screen.queryByText( 'Edit template' ) ).not.toBeInTheDocument();
	} );

	it( 'lazily creates the post and navigates to the editor with the template param', async () => {
		recreateEmailPostRequest.mockResolvedValue( {
			message: 'ok',
			post_id: '42',
		} );

		render(
			<EmailListingFill
				emailTypes={ [ baseEmail ] }
				editTemplateUrl={ null }
				emailTemplateId={ TEMPLATE_ID }
			/>
		);

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Edit template' } )
		);

		await waitFor( () => {
			expect( window.location.href ).toBe(
				`https://example.com/wp-admin/post.php?post=42&action=edit&template=${ encodeURIComponent(
					TEMPLATE_ID
				) }`
			);
		} );
		expect( recreateEmailPostRequest ).toHaveBeenCalledWith( baseEmail.id );
	} );

	it( 'shows an error notice and re-enables the button when post creation fails', async () => {
		recreateEmailPostRequest.mockResolvedValue( null );

		render(
			<EmailListingFill
				emailTypes={ [ baseEmail ] }
				editTemplateUrl={ null }
				emailTemplateId={ TEMPLATE_ID }
			/>
		);

		fireEvent.click(
			screen.getByRole( 'button', { name: 'Edit template' } )
		);

		await waitFor( () => {
			expect( createErrorNoticeMock ).toHaveBeenCalled();
		} );
		expect( window.location.href ).toBe( '' );
		expect(
			screen.getByRole( 'button', { name: 'Edit template' } )
		).toBeEnabled();
	} );
} );
