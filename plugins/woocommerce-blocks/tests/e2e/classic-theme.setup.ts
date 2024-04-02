/**
 * External dependencies
 */
import {
	CLASSIC_THEME_NAME,
	CLASSIC_THEME_SLUG,
	cli,
} from '@woocommerce/e2e-utils';
import { test as setup, expect } from '@woocommerce/e2e-playwright-utils';

setup( 'Sets up the classic theme', async ( { admin } ) => {
	await cli(
		`npm run wp-env run tests-cli -- wp theme install ${ CLASSIC_THEME_SLUG } --activate`
	);
	await admin.page.goto( '/wp-admin/themes.php' );
	await expect(
		admin.page.getByText( `Active: ${ CLASSIC_THEME_NAME }` )
	).toBeVisible();

	console.time( 'Database dump time' );
	await cli(
		'docker exec $(docker ps -aqf name=tests-mysql) mysqldump --column-statistics=0 -u root -ppassword tests-wordpress > wp_e2e_backup.sql'
	);
	console.timeEnd( 'Database dump time' );
} );
