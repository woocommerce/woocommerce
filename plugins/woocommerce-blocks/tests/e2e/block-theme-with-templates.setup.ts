/**
 * External dependencies
 */
import { BLOCK_THEME_WITH_TEMPLATES_SLUG, cli } from '@woocommerce/e2e-utils';
import { test as setup } from '@woocommerce/e2e-playwright-utils';

setup( 'Sets up the block theme with templates', async ( { requestUtils } ) => {
	await requestUtils.activateTheme( BLOCK_THEME_WITH_TEMPLATES_SLUG );

	console.time( 'Database dump time' );
	await cli(
		'docker exec $(docker ps -aqf name=tests-mysql) mysqldump --column-statistics=0 -u root -ppassword tests-wordpress > wp_e2e_backup.sql'
	);
	console.timeEnd( 'Database dump time' );
} );
