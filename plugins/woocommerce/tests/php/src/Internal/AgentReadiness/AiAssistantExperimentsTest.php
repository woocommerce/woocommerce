<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgentReadiness;
use Automattic\WooCommerce\Internal\AgentReadiness\AiAssistantExperiments;
use Automattic\WooCommerce\Internal\AgentReadiness\ReadinessCheckPage;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\PluginUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the Features for AI assistants feature and its Features page row.
 */
class AiAssistantExperimentsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var FeaturesController
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$plugin_util = $this->getMockBuilder( PluginUtil::class )->disableOriginalConstructor()->getMock();
		$plugin_util->method( 'get_woocommerce_aware_plugins' )->willReturn( array() );

		$this->sut = new FeaturesController();
		$this->sut->init( wc_get_container()->get( LegacyProxy::class ), $plugin_util );
	}

	/**
	 * @testdox The description lists every experiment in order and links the AI training choice.
	 */
	public function test_description_matches_the_approved_copy(): void {
		$link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=site-visibility' ) ) . '">';

		$this->assertSame(
			'Turn on newer ways for AI assistants to read your store: WebMCP on store forms, Markdown versions of pages, a full-content llms.txt, a Markdown sitemap and AI catalog, and AI training notices. Uses your ' . $link . 'AI training choice</a>.',
			AiAssistantExperiments::get_description()
		);
	}

	/**
	 * @testdox The AI training sentence is left out when AI training notices are not in the list.
	 */
	public function test_description_omits_ai_training_sentence_without_ai_training_notices(): void {
		$experiments = AiAssistantExperiments::get_experiments();
		unset( $experiments[ AiAssistantExperiments::AI_TRAINING_NOTICES ] );

		$description = AiAssistantExperiments::get_description( $experiments );

		$this->assertSame(
			'Turn on newer ways for AI assistants to read your store: WebMCP on store forms, Markdown versions of pages, a full-content llms.txt, and a Markdown sitemap and AI catalog.',
			$description
		);
	}

	/**
	 * @testdox The description joins two experiments without a comma.
	 */
	public function test_description_joins_two_experiments_with_and(): void {
		$description = AiAssistantExperiments::get_description(
			array(
				'first'  => 'First',
				'second' => 'Second',
			)
		);

		$this->assertSame( 'Turn on newer ways for AI assistants to read your store: First and Second.', $description );
	}

	/**
	 * @testdox The feature is experimental, off by default, and skips plugin compatibility checks.
	 */
	public function test_feature_definition(): void {
		$definition = $this->sut->get_feature_definition( AiAssistantExperiments::FEATURE_NAME );

		$this->assertNotNull( $definition, 'The feature should be registered.' );
		$this->assertSame( 'Features for AI assistants', $definition['name'] );
		$this->assertFalse( $definition['enabled_by_default'], 'The feature should be off by default.' );
		$this->assertTrue( $definition['is_experimental'], 'The feature should be experimental.' );
		$this->assertTrue( $this->sut->should_skip_compatibility_checks( AiAssistantExperiments::FEATURE_NAME ), 'Plugin compatibility checks should be skipped.' );
		$this->assertFalse( $this->sut->feature_is_enabled( AiAssistantExperiments::FEATURE_NAME ), 'The feature should start off.' );
	}

	/**
	 * @testdox The Features page shows the row only when agent readiness is on.
	 *
	 * @testWith ["yes", 1]
	 *           ["no", 0]
	 *
	 * @param string $agent_readiness Value of the agent readiness option.
	 * @param int    $expected_rows   Expected number of rows for the feature.
	 */
	public function test_row_is_shown_only_when_agent_readiness_is_on( string $agent_readiness, int $expected_rows ): void {
		update_option( 'woocommerce_feature_agent_readiness_enabled', $agent_readiness );
		$option_name = $this->sut->feature_enable_option_name( AiAssistantExperiments::FEATURE_NAME );

		$rows = array_values(
			array_filter(
				$this->sut->add_feature_settings( array(), 'features' ),
				static fn( $setting ) => ( $setting['id'] ?? null ) === $option_name
			)
		);

		$this->assertCount( $expected_rows, $rows );
		if ( $expected_rows ) {
			$this->assertSame( 'Features for AI assistants', $rows[0]['title'] );
			$this->assertSame( 'checkbox', $rows[0]['type'] );
			$this->assertSame( 'no', $rows[0]['default'], 'The row should default to off.' );
			$this->assertSame( AiAssistantExperiments::get_description(), $rows[0]['desc'] );
		}
	}

	/**
	 * @testdox The AI tools Overview card has a description and a button for each state.
	 */
	public function test_overview_card_copy(): void {
		update_option( 'woocommerce_feature_ai_assistant_experiments_enabled', 'no' );

		$card = $this->overview_card();

		$this->assertSame( 'Features for AI assistants', $card['title'] );
		$this->assertSame(
			array(
				'intent' => 'none',
				'text'   => 'Experimental',
			),
			$card['badge']
		);
		$this->assertFalse( $card['enabled'] );
		$this->assertSame(
			array(
				'text'   => 'Try newer standards for AI assistants before they settle, such as WebMCP and Markdown versions of your pages.',
				'button' => 'Turn on',
				'error'  => 'We couldn’t turn on features for AI assistants. Try again.',
			),
			$card['off']
		);
		$this->assertSame(
			array(
				'text'   => 'You’re trying newer standards for AI assistants, such as WebMCP and Markdown versions of your pages.',
				'button' => 'Turn off',
				'error'  => 'We couldn’t turn off features for AI assistants. Try again.',
			),
			$card['on']
		);
	}

	/**
	 * @testdox Once the feature is on, the Overview card says so.
	 */
	public function test_overview_card_is_on_when_feature_is_on(): void {
		update_option( 'woocommerce_feature_ai_assistant_experiments_enabled', 'yes' );

		$this->assertTrue( $this->overview_card()['enabled'] );
	}

	/**
	 * @testdox A user who can't manage WooCommerce gets no card.
	 */
	public function test_overview_card_hidden_without_manage_woocommerce(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->assertNull( $this->overview_card() );
	}

	/**
	 * @testdox AI shopping health's Extra details card doesn't mention the experiments.
	 */
	public function test_declared_card_has_no_experiments(): void {
		StoreOutputChecksTest::block_requests();
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		update_option(
			AgentReadiness::REPORT_OPTION,
			array(
				'generated_at' => gmdate( 'c' ),
				'store_checks' => array(),
				'declared'     => array(
					array(
						'id'    => 'ai-txt',
						'name'  => 'ai.txt usage file',
						'found' => false,
					),
				),
			)
		);

		$declared = wc_get_container()->get( ReadinessCheckPage::class )->view()['declared'];

		$this->assertArrayNotHasKey( 'experiments', $declared );
	}

	/**
	 * The Features for AI assistants card from the AI tools Overview.
	 *
	 * @return array<string, mixed>|null
	 */
	private function overview_card(): ?array {
		StoreOutputChecksTest::block_requests();
		update_option( 'woocommerce_feature_agent_readiness_enabled', 'yes' );
		return wc_get_container()->get( ReadinessCheckPage::class )->view()['overview']['experiments'];
	}
}
