/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { BusinessLocation } from '../BusinessLocation';
import { getAdminSetting } from '~/utils/admin-settings';

jest.mock( '~/utils/admin-settings', () => {
	const onboardingSetting = {};
	return { getAdminSetting: jest.fn( () => onboardingSetting ) };
} );
const mockOnboardingSetting = getAdminSetting( 'onboarding' ) as {
	siteHealthUrl?: string;
};

const SITE_HEALTH_URL = 'http://example.test/wp-admin/site-health.php';
const HOSTING_HELP =
	'If this keeps happening, ask your hosting provider to check that your site’s REST API is working.';

const renderPage = ( countries: Array< { key: string; label: string } > ) =>
	render(
		<BusinessLocation
			sendEvent={ jest.fn() }
			navigationProgress={ 80 }
			context={ { geolocatedLocation: undefined, countries } }
		/>
	);

describe( 'BusinessLocation country error help', () => {
	beforeEach( () => {
		delete mockOnboardingSetting.siteHealthUrl;
	} );

	it( 'links to Site Health when the countries cannot be loaded and the user can open it', () => {
		mockOnboardingSetting.siteHealthUrl = SITE_HEALTH_URL;

		renderPage( [] );

		expect(
			screen.getByRole( 'link', { name: 'Check Site Health' } )
		).toHaveAttribute( 'href', SITE_HEALTH_URL );
		expect( screen.queryByText( HOSTING_HELP ) ).not.toBeInTheDocument();
	} );

	it( 'points to the hosting provider when the user cannot open Site Health', () => {
		renderPage( [] );

		expect( screen.getByText( HOSTING_HELP ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'link', { name: 'Check Site Health' } )
		).not.toBeInTheDocument();
	} );

	it.each( [
		[ 'can', SITE_HEALTH_URL ],
		[ 'cannot', undefined ],
	] )(
		'shows no help when the countries load and the user %s open Site Health',
		( _, siteHealthUrl ) => {
			mockOnboardingSetting.siteHealthUrl = siteHealthUrl;

			renderPage( [ { key: 'DE:DE-BE', label: 'Germany — Berlin' } ] );

			expect(
				screen.queryByRole( 'link', { name: 'Check Site Health' } )
			).not.toBeInTheDocument();
			expect(
				screen.queryByText( HOSTING_HELP )
			).not.toBeInTheDocument();
		}
	);
} );
