/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { getHistory } from '@woocommerce/navigation';
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import Homescreen from '../index';
import { getAdminSetting } from '~/utils/admin-settings';

const mockPush = jest.fn();
jest.mock( '@woocommerce/navigation', () => ( {
	getHistory: jest.fn( () => ( { push: mockPush } ) ),
	getNewPath: jest.fn( ( _query, path ) => path ),
	useQuery: jest.fn( () => ( {} ) ),
} ) );

jest.mock( '../layout', () => () => null );

// Without the REST API, the store finishes resolving the profile with its empty defaults.
jest.mock( '@woocommerce/data', () => ( {
	onboardingStore: 'wc/admin/onboarding',
	withOnboardingHydration: () => ( Component: ComponentType ) => Component,
} ) );
jest.mock( '@wordpress/data', () => ( {
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
