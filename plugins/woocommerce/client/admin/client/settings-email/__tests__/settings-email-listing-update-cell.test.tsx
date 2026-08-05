/**
 * Component tests for <UpdatesCell> — RSM-140 acceptance criteria.
 */

/**
 * External dependencies
 */
import { render, screen, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import type { EmailType } from '../settings-email-listing-slotfill';
import { UpdatesCell } from '../settings-email-listing-update-cell';

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: ( path: string ) => `https://example.test/wp-admin/${ path }`,
} ) );

jest.mock( '@wordpress/components', () => ( {
	Button: ( {
		children,
		onClick,
		...rest
	}: {
		children: React.ReactNode;
		onClick?: () => void;
	} & Record< string, unknown > ) => (
		<button onClick={ onClick } { ...rest }>
			{ children }
		</button>
	),
} ) );

const baseEmail: EmailType = {
	id: 'new-order',
	post_id: '123',
	title: 'New order',
	description: 'Notifies admins when a new order is placed.',
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

describe( '<UpdatesCell>', () => {
	let originalLocation: Location;

	beforeEach( () => {
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

	it( 'renders a Review update button when status is core_updated_customized and merchant version is older than current', () => {
		render(
			<UpdatesCell
				post={ {
					...baseEmail,
					templateStatus: 'core_updated_customized',
					templateVersion: '10.6.0',
					currentVersion: '10.7.0',
				} }
			/>
		);

		expect(
			screen.getByRole( 'button', { name: /review update/i } )
		).toBeInTheDocument();
	} );

	it( 'renders em-dash when status is core_updated_customized but merchant version equals current (already reviewed)', () => {
		// Canonical detector check: status alone isn't enough — the merchant
		// is "up to date" once they've reviewed this version, even if they
		// kept some customizations during the apply.
		render(
			<UpdatesCell
				post={ {
					...baseEmail,
					templateStatus: 'core_updated_customized',
					templateVersion: '10.7.0',
					currentVersion: '10.7.0',
				} }
			/>
		);

		expect(
			screen.queryByRole( 'button', { name: /review update/i } )
		).not.toBeInTheDocument();
		expect( screen.getByLabelText( /up to date/i ) ).toHaveTextContent(
			'—'
		);
	} );

	it( 'falls back to status-only gating when version metadata is missing (legacy posts)', () => {
		// Posts that haven't been backfilled yet won't have templateVersion;
		// keep showing the indicator on status alone so legacy posts surface.
		render(
			<UpdatesCell
				post={ {
					...baseEmail,
					templateStatus: 'core_updated_customized',
					templateVersion: null,
					currentVersion: '10.7.0',
				} }
			/>
		);

		expect(
			screen.getByRole( 'button', { name: /review update/i } )
		).toBeInTheDocument();
	} );

	it.each( [ [ 'in_sync' ], [ 'core_updated_uncustomized' ], [ null ] ] )(
		'renders an em-dash with Up to date label when status is %s',
		( status ) => {
			render(
				<UpdatesCell
					post={ {
						...baseEmail,
						templateStatus: status as EmailType[ 'templateStatus' ],
					} }
				/>
			);

			expect(
				screen.queryByRole( 'button', { name: /review update/i } )
			).not.toBeInTheDocument();

			expect( screen.getByLabelText( /up to date/i ) ).toHaveTextContent(
				'—'
			);
		}
	);

	it( 'falls through to em-dash for an unexpected status string', () => {
		render(
			<UpdatesCell
				post={ {
					...baseEmail,
					// Cast to bypass the union for the defensive-default test.
					templateStatus:
						'something_unexpected' as unknown as EmailType[ 'templateStatus' ],
				} }
			/>
		);

		expect(
			screen.queryByRole( 'button', { name: /review update/i } )
		).not.toBeInTheDocument();
		expect( screen.getByLabelText( /up to date/i ) ).toBeInTheDocument();
	} );

	it( 'navigates to the editor with wc_email_review_drawer=1 on click', () => {
		render(
			<UpdatesCell
				post={ {
					...baseEmail,
					templateStatus: 'core_updated_customized',
				} }
			/>
		);

		fireEvent.click(
			screen.getByRole( 'button', { name: /review update/i } )
		);

		expect( window.location.href ).toMatch(
			/\/wp-admin\/post\.php\?post=123&action=edit&wc_email_review_drawer=1$/
		);
	} );

	it( 'does nothing on click when post_id is empty', () => {
		render(
			<UpdatesCell
				post={ {
					...baseEmail,
					post_id: '',
					templateStatus: 'core_updated_customized',
				} }
			/>
		);

		fireEvent.click(
			screen.getByRole( 'button', { name: /review update/i } )
		);

		expect( window.location.href ).toBe( '' );
	} );
} );
