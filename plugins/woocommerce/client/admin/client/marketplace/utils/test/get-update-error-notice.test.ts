/**
 * Internal dependencies
 */
import { getUpdateErrorNotice } from '../functions';

const mockSettings: Record< string, string > = {};

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: () => mockSettings,
} ) );

const reject = ( code: string, message: string ) => ( {
	success: false,
	data: { code, message },
} );

describe( 'getUpdateErrorNotice', () => {
	const onRetry = jest.fn();

	beforeEach( () => {
		mockSettings.updateCoreUrl =
			'https://example.test/wp-admin/update-core.php';
		mockSettings.updatePhpUrl = 'https://wordpress.org/support/update-php/';
	} );

	it( 'links to WordPress updates when the new version needs a newer WordPress', () => {
		expect(
			getUpdateErrorNotice(
				'Product Add-Ons',
				reject(
					'incompatible_wp_required_version',
					'The package could not be installed.'
				),
				onRetry
			)
		).toEqual( {
			message:
				'Product Add-Ons couldn’t be updated. This version requires a newer version of WordPress.',
			actions: [
				{
					label: 'Update WordPress',
					url: 'https://example.test/wp-admin/update-core.php',
				},
			],
		} );
	} );

	it( 'shows no action for a newer WordPress when the user can’t update core', () => {
		mockSettings.updateCoreUrl = '';

		expect(
			getUpdateErrorNotice(
				'Product Add-Ons',
				reject(
					'incompatible_wp_required_version',
					'The package could not be installed.'
				),
				onRetry
			).actions
		).toEqual( [] );
	} );

	it( 'links to the Update PHP page when the new version needs a newer PHP', () => {
		expect(
			getUpdateErrorNotice(
				'Product Add-Ons',
				reject(
					'incompatible_php_required_version',
					'The package could not be installed.'
				),
				onRetry
			)
		).toEqual( {
			message:
				'Product Add-Ons couldn’t be updated. This version requires a newer version of PHP.',
			actions: [
				{
					label: 'Learn more',
					url: 'https://wordpress.org/support/update-php/',
					openInNewTab: true,
				},
			],
		} );
	} );

	it( 'appends core’s message and offers a retry for other errors', () => {
		expect(
			getUpdateErrorNotice(
				'Product Add-Ons',
				reject( 'download_failed', 'Download failed.' ),
				onRetry
			)
		).toEqual( {
			message: 'Product Add-Ons couldn’t be updated. Download failed.',
			actions: [ { label: 'Try again', onClick: onRetry } ],
		} );
	} );

	it( 'decodes HTML entities in core’s message', () => {
		expect(
			getUpdateErrorNotice(
				'Product Add-Ons',
				reject( 'folder_exists', 'Can&#039;t replace the folder.' ),
				onRetry
			).message
		).toBe(
			"Product Add-Ons couldn’t be updated. Can't replace the folder."
		);
	} );

	it( 'ignores a message that isn’t a string', () => {
		expect(
			getUpdateErrorNotice(
				'Product Add-Ons',
				{ success: false, data: { message: { text: 'Nope' } } },
				onRetry
			).message
		).toBe( 'Product Add-Ons couldn’t be updated.' );
	} );

	it( 'falls back to the base sentence when there is no message', () => {
		expect(
			getUpdateErrorNotice(
				'Product Add-Ons',
				'Please reload and try again',
				onRetry
			).message
		).toBe( 'Product Add-Ons couldn’t be updated.' );
	} );
} );
