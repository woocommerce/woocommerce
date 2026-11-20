/**
 * Internal dependencies
 */
import { expect, test } from '../../fixtures/fixtures';
import { wpCLI } from '../../utils/cli';

const provider = 'llms-txt-provider/llms-txt-provider.php';
const fileContent = '# Existing physical llms.txt provider\n';

test.describe( 'llms.txt', () => {
	test( 'serves the fallback and leaves a physical file untouched', async ( {
		request,
	} ) => {
		const response = await request.get( '/llms.txt' );
		expect( response.status() ).toBe( 200 );
		expect( response.headers()[ 'content-type' ] ).toContain(
			'text/plain'
		);
		expect( await response.text() ).toContain( 'WooCommerce Store API' );

		const head = await request.head( '/llms.txt' );
		expect( head.status() ).toBe( 200 );
		expect( await head.text() ).toBe( '' );
		const post = await request.post( '/llms.txt' );
		expect( post.status() ).toBe( 405 );
		expect( post.headers().allow ).toBe( 'GET, HEAD' );

		await wpCLI( [
			'wp',
			'eval',
			`$file = ABSPATH . 'llms.txt'; if ( file_exists( $file ) ) { WP_CLI::error( 'llms.txt already exists' ); } file_put_contents( $file, ${ JSON.stringify( fileContent ) } );`,
		] );
		try {
			const existing = await request.get( '/llms.txt' );
			expect( existing.status() ).toBe( 200 );
			expect( await existing.text() ).toBe( fileContent );
		} finally {
			await wpCLI( [
				'wp',
				'eval',
				`$file = ABSPATH . 'llms.txt'; if ( file_exists( $file ) && file_get_contents( $file ) === ${ JSON.stringify( fileContent ) } ) { unlink( $file ); }`,
			] );
		}
	} );

	test( 'lets a template-based provider handle the request', async ( {
		request,
	} ) => {
		await wpCLI( [ 'wp', 'plugin', 'activate', provider ] );
		try {
			const response = await request.get( '/llms.txt' );
			expect( response.status() ).toBe( 200 );
			expect( await response.text() ).toBe(
				'# Existing llms.txt provider\n'
			);
		} finally {
			await wpCLI( [ 'wp', 'plugin', 'deactivate', provider ] );
		}
	} );

	test( 'respects site-wide coming-soon mode', async ( { request } ) => {
		const { stdout } = await wpCLI( [
			'wp',
			'eval',
			"echo wp_json_encode( array( get_option( 'woocommerce_coming_soon', null ), get_option( 'woocommerce_store_pages_only', null ) ) );",
		] );
		const original = Buffer.from( stdout.trim() ).toString( 'base64' );
		try {
			await wpCLI( [
				'wp',
				'option',
				'update',
				'woocommerce_coming_soon',
				'yes',
			] );
			await wpCLI( [
				'wp',
				'option',
				'update',
				'woocommerce_store_pages_only',
				'no',
			] );
			const response = await request.get( '/llms.txt' );
			const body = await response.text();
			expect( body ).toContain( 'woocommerce-coming-soon' );
			expect( body ).not.toContain( '## Agentic capabilities' );
		} finally {
			await wpCLI( [
				'wp',
				'eval',
				`$options = json_decode( base64_decode( '${ original }' ), true ); foreach ( array( 'woocommerce_coming_soon', 'woocommerce_store_pages_only' ) as $i => $name ) { null === $options[$i] ? delete_option( $name ) : update_option( $name, $options[$i] ); }`,
			] );
		}
	} );
} );
