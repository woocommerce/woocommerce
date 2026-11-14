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
	 * Checks that wait for output WooCommerce doesn't build yet, so Sales readiness doesn't show them.
	 */
	private const PENDING = array( 'product_return_policy_schema', 'product_shipping_schema', 'product_buy_action_schema', 'ucp_checkout' );

	/**
	 * @testdox Every store check Sales readiness shows has copy, headed by what to do and saying what the scan found.
	 */
	public function test_every_store_check_has_copy(): void {
		$checks = new \ReflectionMethod( StoreOutputChecks::class, 'checks' );
		$checks->setAccessible( true );
		$ids = array_diff( array_column( $checks->invoke( wc_get_container()->get( StoreOutputChecks::class ) ), 'id' ), self::PENDING );

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
	 * @testdox The checkout check, pending until its output ships, has its copy and is Required.
	 */
	public function test_checkout_check_has_copy_and_is_required(): void {
		$copy = ReadinessCopy::store_check( 'ucp_checkout' );
		$this->assertSame( 'Let AI assistants send shoppers to your checkout', $copy['action'] ?? null );
		$this->assertNotSame( '', $copy['why'] );
		$this->assertNotSame( '', $copy['found'] );
		$this->assertSame( 'Turn off AI shopping (also called UCP or agentic commerce) in the other plugin, or deactivate it.', $copy['fix'] );

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
	 * @testdox The UCP version message names the version the store serves, when the scan found one.
	 */
	public function test_ucp_version_message_names_served_version(): void {
		$this->assertStringContainsString( '(UCP 2025-01-01)', ReadinessCopy::store_check( 'ucp_profile_version', '2025-01-01' )['found'] );
		$this->assertStringContainsString( '(UCP).', ReadinessCopy::store_check( 'ucp_profile_version' )['found'] );
		$this->assertSame( ReadinessCopy::store_check( 'ucp_profile' )['found'], ReadinessCopy::store_check( 'ucp_profile', '2025-01-01' )['found'], 'Other checks ignore the source' );
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

	/**
	 * @testdox The product why lines that name ChatGPT, Google or Copilot match the bundled channel rules.
	 */
	public function test_product_why_lines_match_channel_rules(): void {
		// What each why line says, and what that means per channel; update both when the rules change.
		$said  = array(
			'missing_regular_price' => array(
				'AI assistants only list products that have a price.',
				array(
					'chatgpt' => 'not_listable',
					'google'  => 'not_listable',
					'copilot' => 'not_listable',
				),
			),
			'missing_images'        => array(
				'AI assistants only list products that have a main image.',
				array(
					'chatgpt' => 'not_listable',
					'google'  => 'not_listable',
					'copilot' => 'not_listable',
				),
			),
			'missing_product_text'  => array(
				'ChatGPT only lists products that have a description. In Google and Copilot, a description makes products easier to find.',
				array(
					'chatgpt' => 'not_listable',
					'google'  => 'gaps',
					'copilot' => 'gaps',
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
			'invalid_gtin'          => array(
				'Google doesn’t list products with a barcode number that isn’t valid. In ChatGPT and Copilot, a valid one makes products easier to find.',
				array(
					'chatgpt' => 'gaps',
					'google'  => 'not_listable',
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
}
