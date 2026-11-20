<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCheckPage;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCopy;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreOutputChecks;
use WC_Unit_Test_Case;

/**
 * Tests for ReadinessCopy.
 */
class ReadinessCopyTest extends WC_Unit_Test_Case {

	/**
	 * @testdox Every store check Sales readiness shows has copy, headed by what to do and saying what the scan found.
	 */
	public function test_every_store_check_has_copy(): void {
		$checks = new \ReflectionMethod( StoreOutputChecks::class, 'checks' );
		$checks->setAccessible( true );
		$ids = array_column( $checks->invoke( wc_get_container()->get( StoreOutputChecks::class ) ), 'id' );

		$this->assertNotEmpty( $ids );
		foreach ( $ids as $id ) {
			$copy = ReadinessCopy::store_check( $id );
			$this->assertNotNull( $copy, "{$id} has copy" );
			$this->assertNotSame( $copy['title'], $copy['action'], "{$id} is worded as what to do while it fails" );
			$this->assertNotSame( '', $copy['why'], "{$id} says why it matters once it passes" );
			$this->assertNotSame( '', $copy['found'], "{$id} says why it matters while it fails" );
		}
		$this->assertNull( ReadinessCopy::store_check( 'not_a_check' ), 'A check without copy keeps the report\'s' );
	}

	/**
	 * @testdox Each found line that names a product is for a check with copy, names the product once, and is used when a name is given.
	 */
	public function test_named_found_lines_name_the_product(): void {
		$named = new \ReflectionMethod( ReadinessCopy::class, 'named_found' );
		$named->setAccessible( true );
		$checks = new \ReflectionMethod( StoreOutputChecks::class, 'checks' );
		$checks->setAccessible( true );
		$ids = array_column( $checks->invoke( wc_get_container()->get( StoreOutputChecks::class ) ), 'id' );

		$with_names = array_filter( $ids, static fn( string $id ): bool => null !== $named->invoke( null, $id ) );
		$this->assertGreaterThanOrEqual( 10, count( $with_names ) );
		foreach ( $with_names as $id ) {
			$this->assertSame( 1, substr_count( (string) $named->invoke( null, $id ), '%s' ), $id );
			$this->assertStringContainsString( 'Blue hoodie', ReadinessCopy::store_check( $id, '', 'Blue hoodie' )['found'], $id );
			$this->assertStringNotContainsString( 'Blue hoodie', ReadinessCopy::store_check( $id )['found'], "{$id} without a name" );
		}
	}

	/**
	 * @testdox A broken llms.txt link is named, and the fix says where to change it for each place the file comes from.
	 */
	public function test_broken_llms_txt_link_names_the_link_and_fits_the_fix(): void {
		$copy = ReadinessCopy::store_check( 'llms_txt_links_resolve', 'file', '', 'https://example.com/old-sale/' );
		$this->assertStringContainsString( 'doesn’t open: example.com/old-sale/.', $copy['found'] );
		$this->assertStringContainsString( 'llms.txt file in your site’s root folder', $copy['fix'] );
		$this->assertStringContainsString( 'plugin that adds your llms.txt file', ReadinessCopy::store_check( 'llms_txt_links_resolve', 'other' )['fix'] );
		$this->assertStringContainsString( 'WooCommerce adds these links', ReadinessCopy::store_check( 'llms_txt_links_resolve', 'woocommerce' )['fix'] );
		$general = ReadinessCopy::store_check( 'llms_txt_links_resolve' );
		$this->assertStringStartsWith( 'Some links in the file', $general['found'], 'Without a link, the general line' );
		$this->assertSame( 'Update or remove the broken links in the file.', $general['fix'] );
	}

	/**
	 * @testdox The checkout check, pending until its output ships, has its copy and is Required.
	 */
	public function test_checkout_check_has_copy_and_is_required(): void {
		$copy = ReadinessCopy::store_check( 'ucp_checkout' );
		$this->assertSame( 'Let AI assistants send shoppers to your checkout', $copy['action'] ?? null );
		$this->assertNotSame( '', $copy['why'] );
		$this->assertNotSame( '', $copy['found'] );
		$this->assertSame( 'In the other plugin, turn off any setting for AI shopping, UCP, or agentic commerce. Or deactivate the plugin.', $copy['fix'] );
		$this->assertSame( admin_url( 'plugins.php' ), $copy['url'], 'View plugins opens the Plugins screen' );

		$required = new \ReflectionMethod( ReadinessCheckPage::class, 'is_required' );
		$required->setAccessible( true );
		$check = array(
			'id'       => 'ucp_checkout',
			'blocks'   => 'ucp',
			'group'    => 'access',
			'level'    => 'recommended',
			'conflict' => false,
		);
		$this->assertTrue( $required->invoke( null, $check ), 'Without checkout, AI assistants find products but can\'t sell them' );
		$this->assertFalse( $required->invoke( null, array( 'id' => 'ucp_profile_version' ) + $check ), 'Other UCP checks stay recommended' );
	}

	/**
	 * @testdox Every store check Sales readiness shows has a fix line, so a failed row always names a next step.
	 */
	public function test_every_store_check_has_a_fix_line(): void {
		$checks = new \ReflectionMethod( StoreOutputChecks::class, 'checks' );
		$checks->setAccessible( true );
		foreach ( array_column( $checks->invoke( wc_get_container()->get( StoreOutputChecks::class ) ), 'id' ) as $id ) {
			$this->assertNotSame( '', ReadinessCopy::store_check( $id )['fix'] ?? '', $id );
		}
		$this->assertStringContainsString( 'tab=shipping', ReadinessCopy::store_check( 'product_shipping_schema' )['url'], 'The shipping fix links to the Shipping settings' );
	}

	/**
	 * @testdox The llms.txt first-line fix names the store, and keeps the generic line when the store has no name.
	 */
	public function test_llms_txt_first_line_fix_names_the_store(): void {
		$name = get_option( 'blogname' );
		update_option( 'blogname', 'Blue Sky & Co' );
		try {
			$this->assertSame( 'Make # Blue Sky & Co the first line of the file. Do this in your SEO plugin’s llms.txt settings, or in the llms.txt file in your site’s root folder.', ReadinessCopy::store_check( 'llms_txt_valid' )['fix'] );
			update_option( 'blogname', '' );
			$this->assertSame( 'Add your store’s name as the file’s first heading.', ReadinessCopy::store_check( 'llms_txt_valid' )['fix'] );
		} finally {
			update_option( 'blogname', $name );
		}
	}

	/**
	 * @testdox The brand fix says where to find the products only when there are several.
	 */
	public function test_brand_fix_only_for_several_products(): void {
		$this->assertSame( 'Find them in Products, and add a brand to each one.', ReadinessCopy::product_check( 'missing_brand', 3 )['fix'] );
		$this->assertSame( '', ReadinessCopy::product_check( 'missing_brand', 1 )['fix'] ?? '' );
	}

	/**
	 * @testdox The preview details found line names the page without them: the home page, a product page, or both, with the product's name when known.
	 */
	public function test_open_graph_found_names_the_page(): void {
		$found = static fn( string $source, string $product = '' ): string => ReadinessCopy::store_check( 'open_graph_tags', $source, $product )['found'];
		$this->assertSame( 'Your home page doesn’t have preview details, which AI assistants use to show it as a card.', $found( 'home' ) );
		$this->assertSame( 'The Blue hoodie page doesn’t have preview details, which AI assistants use to show it as a card.', $found( 'product', 'Blue hoodie' ) );
		$this->assertSame( 'A product page doesn’t have preview details, which AI assistants use to show it as a card.', $found( 'product' ) );
		$this->assertSame( 'Your home page and the Blue hoodie page don’t have preview details, which AI assistants use to show them as cards.', $found( 'both', 'Blue hoodie' ) );
		$this->assertStringStartsWith( 'Some pages don’t have preview details.', $found( '' ), 'An older report without the page keeps the general line' );
		$this->assertSame( 'Most SEO plugins can add them, often as Open Graph tags in their social settings.', ReadinessCopy::store_check( 'open_graph_tags', 'home' )['fix'] );
	}

	/**
	 * @testdox The description fix line matches the number of products.
	 */
	public function test_description_fix_line(): void {
		$this->assertSame( 'Add a main or short description to your product.', ReadinessCopy::product_check( 'missing_product_text', 1 )['fix'] );
		$this->assertSame( 'Add a main or short description to your products.', ReadinessCopy::product_check( 'missing_product_text', 4 )['fix'] );
	}

	/**
	 * @testdox The image size line names Google, the only AI app with the rule, and no date: rules.json moves it to can't be listed when the rule starts.
	 */
	public function test_image_size_line_names_google(): void {
		$this->assertSame( 'Google asks for images of at least 500 x 500 pixels.', ReadinessCopy::product_check( 'image_too_small', 2 )['why'] );
	}

	/**
	 * @testdox The UCP version message names the version the store serves, when the scan found one.
	 */
	public function test_ucp_version_message_names_served_version(): void {
		$this->assertStringContainsString( '(UCP 2025-01-01)', ReadinessCopy::store_check( 'ucp_profile_version', '2025-01-01' )['found'] );
		$this->assertStringContainsString( '(UCP).', ReadinessCopy::store_check( 'ucp_profile_version' )['found'] );
		$this->assertSame( ReadinessCopy::store_check( 'ucp_profile' )['found'], ReadinessCopy::store_check( 'ucp_profile', '2025-01-01' )['found'], 'Other checks ignore the source' );
		$this->assertSame(
			array(
				'href' => 'https://ucpplayground.com/',
				'text' => 'UCP Playground',
			),
			ReadinessCopy::store_check( 'ucp_profile' )['found_link'],
			'The connection check links UCP Playground where its found line names it'
		);
		$this->assertStringContainsString( 'UCP Playground', ReadinessCopy::store_check( 'ucp_profile' )['found'] );
		$this->assertNull( ReadinessCopy::store_check( 'ucp_checkout' )['found_link'], 'Other checks link nothing in the text' );
	}

	/**
	 * @testdox Every product check in the rules file has copy that says what to do and to how many products.
	 */
	public function test_every_product_check_has_copy(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local file in the plugin.
		$rules  = json_decode( (string) file_get_contents( WC_ABSPATH . 'src/Internal/AgentReadiness/rules.json' ), true );
		$hidden = new \ReflectionClassConstant( ReadinessCheckPage::class, 'HIDDEN_PRODUCT_CHECKS' );
		$ids    = array_diff( array_keys( $rules['checks'] ), $hidden->getValue() );

		$this->assertNotEmpty( $ids );
		foreach ( $ids as $id ) {
			$copy = ReadinessCopy::product_check( $id, 13 );
			$this->assertNotNull( $copy, "{$id} has copy" );
			$this->assertStringContainsString( '13 products', $copy['action'], "{$id} names how many products" );
			$this->assertNotSame( '', $copy['why'], "{$id} says why it matters" );
			$this->assertStringNotContainsString( '%s', ReadinessCopy::product_check( $id, 2, array( 'Blue hoodie', 'Red cap' ) )['action'], "{$id} fills in the product names" );
		}
		$this->assertSame( 'Add a brand to Blue hoodie', ReadinessCopy::product_check( 'missing_brand', 1, array( 'Blue hoodie' ) )['action'], 'One product is named' );
		$this->assertSame( 'Add a brand to Blue hoodie and Red cap', ReadinessCopy::product_check( 'missing_brand', 2, array( 'Blue hoodie', 'Red cap' ) )['action'], 'Two products are named' );
		$this->assertSame( 'Add a brand to Blue hoodie, Red cap, and Canvas tote', ReadinessCopy::product_check( 'missing_brand', 3, array( 'Blue hoodie', 'Red cap', 'Canvas tote' ) )['action'], 'Three products are named' );
		$this->assertSame( 'Give Blue hoodie and Red cap their own barcode numbers', ReadinessCopy::product_check( 'duplicate_gtin', 2, array( 'Blue hoodie', 'Red cap' ) )['action'], 'Named titles agree with the number of products' );
		$this->assertSame( 'Add a brand to 2 products', ReadinessCopy::product_check( 'missing_brand', 2, array( 'Blue hoodie' ) )['action'], 'Names that don\'t match the count give the count' );
		$this->assertSame( 'Add a brand to 4 products', ReadinessCopy::product_check( 'missing_brand', 4, array( 'A', 'B', 'C', 'D' ) )['action'], 'More than three products are counted' );
		$this->assertSame( 'Add a brand to a product', ReadinessCopy::product_check( 'missing_brand', 1 )['action'], 'One product without a name' );
		$this->assertSame( 'Add a brand to 13 products', ReadinessCopy::product_check( 'missing_brand', 13 )['action'], 'Many products are counted' );
		$this->assertStringStartsWith( 'ChatGPT only lists products that have a brand.', ReadinessCopy::product_check( 'missing_brand', 1 )['why'] );
		$this->assertNull( ReadinessCopy::product_check( 'not_a_check', 1 ) );
	}

	/**
	 * @testdox The product why lines that name ChatGPT, Google or Copilot match the bundled channel rules.
	 */
	public function test_product_why_lines_match_channel_rules(): void {
		// What each why line says, and what that means per channel; update both when the rules change. Lines that name no channel are left out.
		$said  = array(
			'missing_regular_price' => array(
				'AI shopping apps only list products that have a price.',
				array(
					'chatgpt' => 'not_listable',
					'google'  => 'not_listable',
					'copilot' => 'not_listable',
				),
			),
			'missing_images'        => array(
				'AI shopping apps need a main image to list a product.',
				array(
					'chatgpt' => 'not_listable',
					'google'  => 'not_listable',
					'copilot' => 'not_listable',
				),
			),
			'missing_brand'         => array(
				'ChatGPT only lists products that have a brand. In Google and Copilot, a brand makes products easier to find.',
				array(
					'chatgpt' => 'not_listable',
					'google'  => 'gaps',
					'copilot' => 'gaps',
				),
			),
		);
		$rules = json_decode( (string) file_get_contents( WC_ABSPATH . 'src/Internal/AgentReadiness/rules.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file.

		foreach ( $said as $id => list( $why, $channels ) ) {
			$this->assertSame( $why, ReadinessCopy::product_check( $id, 2 )['why'], "The why line of {$id} is the one checked here" );
			$this->assertSame(
				$channels,
				array_map( static fn( array $channel ): string => $channel['effect'], $rules['checks'][ $id ]['channels'] ),
				"The why line of {$id} matches what each channel does in rules.json"
			);
		}
	}

	/**
	 * @testdox Each llms.txt source has a line for a served file or a fix for a missing one, and an unknown source has none.
	 */
	public function test_llms_txt_source_copy(): void {
		foreach ( array( 'woocommerce', 'file', 'other' ) as $source ) {
			$this->assertNotEmpty( ReadinessCopy::llms_txt_source( $source )['pass'] ?? '', "{$source} says where llms.txt comes from" );
		}
		foreach ( array( 'disabled', 'not_reached' ) as $source ) {
			$this->assertNotEmpty( ReadinessCopy::llms_txt_source( $source )['fix'] ?? '', "{$source} has its own fix" );
		}
		$this->assertNull( ReadinessCopy::llms_txt_source( '' ) );
		$this->assertNull( ReadinessCopy::llms_txt_source( 'plugin' ) );
	}
}
