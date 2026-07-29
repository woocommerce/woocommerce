/**
 * Internal dependencies
 */
import { expect, test } from '../../fixtures/fixtures';
import { wpCLI } from '../../utils/cli';

const FLUSH_HOOK = 'rewrite_flush_test_helper_flush';
const TEST_PLUGIN = 'rewrite-flush-test-helper';

type RewriteRules = Record< string, string >;

const getProductArchiveRewriteRules = async (): Promise< string[] > => {
	const { stdout } = await wpCLI(
		'wp option get rewrite_rules --format=json'
	);
	const rewriteRules = JSON.parse( stdout ) as RewriteRules;

	return Object.values( rewriteRules ).filter( ( query ) =>
		/^index\.php\?post_type=product(?:&|$)/.test( query )
	);
};

const establishSupportedThemeBaseline = async (): Promise< void > => {
	await wpCLI( 'wp theme activate storefront' );
	await wpCLI( 'wp option update current_theme_supports_woocommerce yes' );
	await wpCLI( 'wp rewrite flush' );
};

test.describe( 'Product archive rewrite registration', () => {
	test.beforeAll( async () => {
		await wpCLI( `wp plugin activate ${ TEST_PLUGIN }` );
	} );

	test.beforeEach( async () => {
		await establishSupportedThemeBaseline();
	} );

	test.afterAll( async () => {
		await wpCLI( 'wp theme activate twentytwentythree' );
		await wpCLI(
			'wp option update current_theme_supports_woocommerce yes'
		);
		await wpCLI( 'wp rewrite flush' );
		await wpCLI( `wp plugin deactivate ${ TEST_PLUGIN }` );
	} );

	test( 'Ordinary cron trusts unsupported runtime theme state', async () => {
		await wpCLI( 'wp theme activate unsupported-classic-theme' );
		await wpCLI(
			'wp option update woocommerce_queue_flush_rewrite_rules no'
		);
		await wpCLI(
			'wp option update current_theme_supports_woocommerce yes'
		);
		await wpCLI(
			`wp option update rewrite_rules '{"shop/?$":"index.php?post_type=product"}' --format=json`
		);
		expect( await getProductArchiveRewriteRules() ).toEqual( [
			'index.php?post_type=product',
		] );

		await wpCLI(
			`wp eval 'delete_transient( "doing_cron" ); wp_schedule_single_event( time() - 1, "${ FLUSH_HOOK }" );' && php wp-cron.php`
		);

		expect( await getProductArchiveRewriteRules() ).toEqual( [] );
	} );

	test( 'WP-CLI preserves product archive rewrites when themes are skipped', async () => {
		await wpCLI( 'wp rewrite flush --skip-themes' );

		expect( await getProductArchiveRewriteRules() ).toContain(
			'index.php?post_type=product'
		);
	} );
} );
