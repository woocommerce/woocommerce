/**
 * Internal dependencies
 */
import { getRequirementErrorNotice } from '../functions';

const mockSettings: Record< string, string > = {};

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: () => mockSettings,
} ) );

const reject = ( code: string, message: string ) => ( {
	success: false,
	data: { code, message },
} );

describe( 'getRequirementErrorNotice', () => {
	beforeEach( () => {
		mockSettings.updateCoreUrl =
			'https://example.test/wp-admin/update-core.php';
		mockSettings.updatePhpUrl = 'https://wordpress.org/support/update-php/';
	} );

	it( 'explains an install that needs a newer WordPress, with a link to update it', () => {
		expect(
			getRequirementErrorNotice(
				'install',
				'Product Bundles',
				reject(
					'incompatible_wp_required_version',
					'The package could not be installed.'
				)
			)
		).toEqual( {
			message:
				'Product Bundles couldn’t be installed. It requires a newer version of WordPress.',
			actions: [
				{
					label: 'Update WordPress',
					url: 'https://example.test/wp-admin/update-core.php',
				},
			],
		} );
	} );

	it( 'explains an install that needs a newer PHP, with a link to learn more', () => {
		expect(
			getRequirementErrorNotice(
				'install',
				'Product Bundles',
				reject(
					'incompatible_php_required_version',
					'The package could not be installed.'
				)
			)
		).toEqual( {
			message:
				'Product Bundles couldn’t be installed. It requires a newer version of PHP.',
			actions: [
				{
					label: 'Learn more',
					url: 'https://wordpress.org/support/update-php/',
					openInNewTab: true,
				},
			],
		} );
	} );

	it( 'shows no action when the user can’t update WordPress', () => {
		mockSettings.updateCoreUrl = '';

		expect(
			getRequirementErrorNotice(
				'install',
				'Product Bundles',
				reject( 'incompatible_wp_required_version', '' )
			)?.actions
		).toEqual( [] );
	} );

	it( 'keeps the update wording for updates', () => {
		expect(
			getRequirementErrorNotice(
				'update',
				'Product Bundles',
				reject( 'incompatible_wp_required_version', '' )
			)?.message
		).toBe(
			'Product Bundles couldn’t be updated. This version requires a newer version of WordPress.'
		);
	} );

	it.each( [
		[
			'another error code',
			reject( 'folder_exists', 'Destination folder already exists.' ),
		],
		[ 'no error code', { success: false, data: { message: 'Oops' } } ],
		[ 'a string', 'Please reload and try again' ],
		[ 'undefined', undefined ],
	] )( 'returns null for %s', ( _label, error ) => {
		expect(
			getRequirementErrorNotice( 'install', 'Product Bundles', error )
		).toBeNull();
	} );
} );
