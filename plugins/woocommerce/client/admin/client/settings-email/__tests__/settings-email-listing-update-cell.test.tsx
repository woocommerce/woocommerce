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

const recordEventMock = jest.fn();

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: ( name: string, payload: Record< string, unknown > ) => {
		recordEventMock( name, payload );
	},
} ) );

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
		recordEventMock.mockClear();
		window.sessionStorage.clear();
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

	// RSM-145 list-page `_viewed` instrumentation.
	describe( 'Tracks _viewed event', () => {
		const eligibleEmail: EmailType = {
			...baseEmail,
			templateStatus: 'core_updated_customized',
			templateVersion: '10.6.0',
			currentVersion: '10.7.0',
		};

		it( 'fires woocommerce_block_email_update_viewed once on mount when the cell is eligible', () => {
			render( <UpdatesCell post={ eligibleEmail } /> );

			expect( recordEventMock ).toHaveBeenCalledTimes( 1 );
			expect( recordEventMock ).toHaveBeenCalledWith(
				'woocommerce_block_email_update_viewed',
				expect.objectContaining( {
					email_id: 'new-order',
					template_version_from: '10.6.0',
					template_version_to: '10.7.0',
					source_hash_to: null,
					classification: 'core_updated_customized',
					was_backfilled: false,
					viewed_from: 'email_list',
				} )
			);
		} );

		it( 'dedups within a session for the same (post_id, version_to)', () => {
			const { rerender, unmount } = render(
				<UpdatesCell post={ eligibleEmail } />
			);
			rerender( <UpdatesCell post={ eligibleEmail } /> );
			unmount();
			render( <UpdatesCell post={ eligibleEmail } /> );

			expect( recordEventMock ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'fires a fresh event when currentVersion advances for the same post', () => {
			const { rerender } = render(
				<UpdatesCell post={ eligibleEmail } />
			);
			rerender(
				<UpdatesCell
					post={ { ...eligibleEmail, currentVersion: '10.8.0' } }
				/>
			);

			expect( recordEventMock ).toHaveBeenCalledTimes( 2 );
			expect( recordEventMock ).toHaveBeenLastCalledWith(
				'woocommerce_block_email_update_viewed',
				expect.objectContaining( { template_version_to: '10.8.0' } )
			);
		} );

		it( 'does not fire when the cell is not eligible (status in_sync)', () => {
			render(
				<UpdatesCell
					post={ {
						...baseEmail,
						templateStatus: 'in_sync',
					} }
				/>
			);

			expect( recordEventMock ).not.toHaveBeenCalled();
		} );

		it( 'does not fire when the merchant version already matches current', () => {
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

			expect( recordEventMock ).not.toHaveBeenCalled();
		} );
	} );
} );
