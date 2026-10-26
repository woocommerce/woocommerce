/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { getHistory, getNewPath, useQuery } from '@woocommerce/navigation';
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import Homescreen from '../index';
import { getAdminSetting } from '~/utils/admin-settings';

const mockPush = jest.fn();
const mockReplace = jest.fn();
jest.mock( '@woocommerce/navigation', () => ( {
	getHistory: jest.fn( () => ( { push: mockPush, replace: mockReplace } ) ),
	getNewPath: jest.fn( ( _query, path ) => path ),
	useQuery: jest.fn( () => ( {} ) ),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getAdminLink: ( path: string ) => `http://example.test/wp-admin/${ path }`,
} ) );

jest.mock( '../layout', () => () => null );

// Without the REST API, the store finishes resolving the profile with its empty defaults.
jest.mock( '@woocommerce/data', () => ( {
	onboardingStore: 'wc/admin/onboarding',
	withOnboardingHydration: () => ( Component: ComponentType ) => Component,
} ) );
const mockCreateErrorNotice = jest.fn();
jest.mock( '@wordpress/data', () => ( {
	useDispatch: () => ( { createErrorNotice: mockCreateErrorNotice } ),
	withSelect:
		( mapSelect: ( select: unknown ) => object ) =>
		( Component: ComponentType ) =>
		( props: object ) => (
			<Component
				{ ...props }
				{ ...mapSelect( () => ( {
					getProfileItems: () => ( {
						completed: null,
						skipped: null,
					} ),
					hasFinishedResolution: () => true,
				} ) ) }
			/>
		),
} ) );

// Homescreen reads this setting when it is imported, so the object lives inside the mock.
jest.mock( '~/utils/admin-settings', () => {
	const onboardingSetting = { profile: {} };
	return { getAdminSetting: jest.fn( () => onboardingSetting ) };
} );
const mockOnboardingSetting = getAdminSetting( 'onboarding' ) as {
	profile: Record< string, boolean >;
};

const renderHomescreen = () => render( <Homescreen /> );

describe( 'Homescreen setup wizard redirect', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( useQuery as jest.Mock ).mockReturnValue( {} );
	} );

	it( 'redirects to the setup wizard while setup is pending', () => {
		mockOnboardingSetting.profile = {};

		renderHomescreen();

		expect( getHistory().push ).toHaveBeenCalledWith( '/setup-wizard' );
	} );

	it.each( [ { skipped: true }, { completed: true } ] )(
		'stays on Home when the preloaded profile is %o but the REST profile request failed',
		( profile ) => {
			mockOnboardingSetting.profile = profile;

			renderHomescreen();

			expect( mockPush ).not.toHaveBeenCalled();
		}
	);
} );

describe( 'Homescreen store location notice', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockOnboardingSetting.profile = { skipped: true };
	} );

	it( 'points to the general settings and drops the flag when the skip flow could not save the store location', () => {
		( useQuery as jest.Mock ).mockReturnValue( {
			store_location_not_saved: '1',
		} );

		renderHomescreen();

		expect( mockCreateErrorNotice ).toHaveBeenCalledWith(
			expect.stringContaining(
				'Your store location and currency were not saved.'
			),
			expect.objectContaining( {
				explicitDismiss: true,
				actions: [
					{
						label: 'Go to settings',
						url: 'http://example.test/wp-admin/admin.php?page=wc-settings&tab=general',
					},
				],
			} )
		);
		expect( getNewPath ).toHaveBeenCalledWith(
			{ store_location_not_saved: undefined },
			'/',
			{ store_location_not_saved: '1' }
		);
		expect( mockReplace ).toHaveBeenCalledWith( '/' );
	} );

	it( 'shows no notice without the flag', () => {
		( useQuery as jest.Mock ).mockReturnValue( {} );

		renderHomescreen();

		expect( mockCreateErrorNotice ).not.toHaveBeenCalled();
		expect( mockReplace ).not.toHaveBeenCalled();
	} );
} );
