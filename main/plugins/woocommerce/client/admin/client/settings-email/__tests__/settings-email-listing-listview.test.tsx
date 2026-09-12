/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { ListView } from '../settings-email-listing-listview';
import type { EmailType } from '../settings-email-listing-slotfill';
import { useSendTestEmail } from '../settings-email-send-test';

type DataViewsAction = {
	id: string;
	callback?: ( items: EmailType[] ) => void | Promise< void >;
	isEligible?: ( item: EmailType ) => boolean;
	RenderModal?: ComponentType< {
		items: EmailType[];
		closeModal?: () => void;
	} >;
};

// Captured on each render so tests can drive the actions directly.
let capturedActions: DataViewsAction[] = [];

jest.mock( '@wordpress/dataviews/wp', () => ( {
	DataViews: ( { actions }: { actions: DataViewsAction[] } ) => {
		capturedActions = actions;
		return null;
	},
} ) );

const mockRecreateEmailPost = jest.fn();
const mockCreateErrorNotice = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	dispatch: () => ( { createErrorNotice: mockCreateErrorNotice } ),
} ) );

jest.mock( '../settings-email-listing-data', () => ( {
	useTransactionalEmails: ( emailTypes: EmailType[] ) => ( {
		emails: emailTypes,
		total: emailTypes.length,
		updateEmailEnabledStatus: jest.fn(),
		recreateEmailPost: mockRecreateEmailPost,
	} ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: ( path: string ) => `https://example.test/wp-admin/${ path }`,
} ) );

jest.mock( '../settings-email-send-test', () => ( {
	useSendTestEmail: jest.fn( () => ( {
		email: '',
		setEmail: jest.fn(),
		isSending: false,
		notice: '',
		noticeType: '',
		sendEmail: jest.fn(),
	} ) ),
	SendTestEmailForm: () => null,
} ) );

const useSendTestEmailMock = useSendTestEmail as jest.MockedFunction<
	typeof useSendTestEmail
>;

const emailType: EmailType = {
	title: 'New order',
	description: 'New order notification',
	id: 'new_order',
	email_key: 'wc_email_new_order',
	email_class_name: 'WC_Email_New_Order',
	post_id: '123',
	file_template_preview_url:
		'https://example.test/wp-admin/?preview_woo_block_email=true&email_id=new_order&_wpnonce=abc',
	recipients: {
		to: 'admin@example.com',
		cc: '',
		bcc: '',
	},
	enabled: true,
	manual: false,
	postStatus: 'publish',
	templateStatus: null,
	templateVersion: null,
	currentVersion: null,
	wasBackfilled: false,
};

const getAction = ( id: string ) =>
	capturedActions.find( ( action ) => action.id === id );

const renderTestModal = ( email: EmailType ) => {
	render( <ListView emailTypes={ [ email ] } /> );
	const RenderModal = getAction( 'test' )?.RenderModal;
	if ( ! RenderModal ) {
		throw new Error( 'Send test email action has no RenderModal' );
	}
	return render(
		<RenderModal items={ [ email ] } closeModal={ () => {} } />
	);
};

describe( 'ListView', () => {
	let originalLocation: typeof window.location;

	beforeEach( () => {
		capturedActions = [];
		useSendTestEmailMock.mockClear();
		mockRecreateEmailPost.mockReset();

		originalLocation = window.location;
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		delete ( window as any ).location;
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		( window as any ).location = {
			...originalLocation,
			href: '',
			assign: jest.fn(),
		};
	} );

	afterEach( () => {
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		( window as any ).location = originalLocation;
	} );

	describe( 'edit action', () => {
		it( 'navigates directly to the post editor when a post already exists', async () => {
			render( <ListView emailTypes={ [ emailType ] } /> );

			await getAction( 'edit' )?.callback?.( [ emailType ] );

			expect( mockRecreateEmailPost ).not.toHaveBeenCalled();
			expect( window.location.href ).toBe(
				'https://example.test/wp-admin/post.php?post=123&action=edit'
			);
		} );

		it( 'lazily creates the post and navigates to it when no post exists', async () => {
			mockRecreateEmailPost.mockResolvedValue( {
				message: 'ok',
				post_id: '456',
			} );
			const emailWithoutPost = {
				...emailType,
				post_id: '',
				postStatus: null,
			};
			render( <ListView emailTypes={ [ emailWithoutPost ] } /> );

			await getAction( 'edit' )?.callback?.( [ emailWithoutPost ] );

			expect( mockRecreateEmailPost ).toHaveBeenCalledWith( 'new_order' );
			expect( window.location.href ).toBe(
				'https://example.test/wp-admin/post.php?post=456&action=edit'
			);
		} );

		it( 'does not navigate and surfaces an error notice when the post could not be created', async () => {
			mockRecreateEmailPost.mockResolvedValue( null );
			const emailWithoutPost = {
				...emailType,
				post_id: '',
				postStatus: null,
			};
			render( <ListView emailTypes={ [ emailWithoutPost ] } /> );

			await getAction( 'edit' )?.callback?.( [ emailWithoutPost ] );

			expect( mockRecreateEmailPost ).toHaveBeenCalledWith( 'new_order' );
			expect( window.location.href ).toBe( '' );
			expect( mockCreateErrorNotice ).toHaveBeenCalled();
		} );
	} );

	it( 'does not register a recreate-email-post action', () => {
		render( <ListView emailTypes={ [ emailType ] } /> );

		expect( getAction( 'recreate-email-post' ) ).toBeUndefined();
	} );

	describe( 'preview action', () => {
		it( 'is eligible for published posts and for rows with a file-template preview URL', () => {
			render( <ListView emailTypes={ [ emailType ] } /> );
			const isEligible = getAction( 'preview' )?.isEligible;

			expect(
				isEligible?.( { ...emailType, postStatus: 'publish' } )
			).toBe( true );
			expect(
				isEligible?.( { ...emailType, postStatus: 'draft' } )
			).toBe( true );
			expect(
				isEligible?.( { ...emailType, post_id: '', postStatus: null } )
			).toBe( true );
			// Emails not registered for the block editor have no preview URL
			// and no published post — no preview.
			expect(
				isEligible?.( {
					...emailType,
					post_id: '',
					postStatus: null,
					file_template_preview_url: null,
				} )
			).toBe( false );
			// A published row needs a resolved permalink; without one (and
			// without a file-template URL) there is nothing to open.
			expect(
				isEligible?.( {
					...emailType,
					postStatus: 'publish',
					link: 'https://example.test/?woo_email=new-order',
					file_template_preview_url: null,
				} )
			).toBe( true );
			expect(
				isEligible?.( {
					...emailType,
					postStatus: 'publish',
					link: '',
					file_template_preview_url: null,
				} )
			).toBe( false );
			expect(
				isEligible?.( {
					...emailType,
					postStatus: 'draft',
					file_template_preview_url: null,
				} )
			).toBe( false );
		} );

		it( 'opens the permalink for published posts and the file-template preview otherwise', () => {
			const windowOpenSpy = jest
				.spyOn( window, 'open' )
				.mockImplementation( () => null );
			render( <ListView emailTypes={ [ emailType ] } /> );
			const callback = getAction( 'preview' )?.callback;

			callback?.( [
				{
					...emailType,
					postStatus: 'publish',
					link: 'https://example.test/?woo_email=new-order',
				},
			] );
			expect( windowOpenSpy ).toHaveBeenLastCalledWith(
				'https://example.test/?woo_email=new-order'
			);

			// An unpublished draft is not what customers receive — preview the
			// file template instead.
			callback?.( [ { ...emailType, postStatus: 'draft' } ] );
			expect( windowOpenSpy ).toHaveBeenLastCalledWith(
				emailType.file_template_preview_url
			);

			callback?.( [ { ...emailType, post_id: '', postStatus: null } ] );
			expect( windowOpenSpy ).toHaveBeenLastCalledWith(
				emailType.file_template_preview_url
			);

			windowOpenSpy.mockRestore();
		} );
	} );

	describe( 'send test email action', () => {
		it( 'sends by post ID for a published post, tracking with the email class name', () => {
			renderTestModal( emailType );

			expect( useSendTestEmailMock ).toHaveBeenCalledWith(
				{
					endpoint: 'editor',
					postId: 123,
					emailType: 'WC_Email_New_Order',
					emailTypeId: 'new_order',
				},
				'email_listing'
			);
		} );

		it( 'is available for emails without a post', () => {
			render( <ListView emailTypes={ [ emailType ] } /> );
			const action = getAction( 'test' );

			// No eligibility gate — every email can send a test; without a
			// published post the server renders the file template.
			expect( action ).toBeDefined();
			expect( action?.isEligible ).toBeUndefined();
		} );

		// Unpublished drafts render nothing for customers, so the test email
		// must use the file template too — not the draft content.
		it.each( [
			[ 'no post', { post_id: '', postStatus: null } ],
			[ 'an unpublished draft', { post_id: '55', postStatus: 'draft' } ],
		] )(
			'sends by email type when the email has %s',
			( _label, overrides ) => {
				renderTestModal( { ...emailType, ...overrides } );

				expect( useSendTestEmailMock ).toHaveBeenCalledWith(
					{
						endpoint: 'editor',
						postId: null,
						emailType: 'WC_Email_New_Order',
						emailTypeId: 'new_order',
					},
					'email_listing'
				);
			}
		);
	} );
} );
