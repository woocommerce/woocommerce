<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AiShoppingExperiments;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Utilities\PluginUtil;
use WC_Unit_Test_Case;

/**
 * Tests for the AI shopping experiments feature and its Features page row.
 */
class AiShoppingExperimentsTest extends WC_Unit_Test_Case {

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
			'Turn on newer ways for AI assistants to read and shop your store: WebMCP on store forms, Markdown versions of pages, a full-content llms.txt, a Markdown sitemap and AI catalog, and AI training notices. Uses your ' . $link . 'AI training choice</a>.',
			AiShoppingExperiments::get_description()
		);
	}

	/**
	 * @testdox The AI training sentence is left out when AI training notices are not in the list.
	 */
	public function test_description_omits_ai_training_sentence_without_ai_training_notices(): void {
		$experiments = AiShoppingExperiments::get_experiments();
		unset( $experiments[ AiShoppingExperiments::AI_TRAINING_NOTICES ] );

		$description = AiShoppingExperiments::get_description( $experiments );

		$this->assertSame(
			'Turn on newer ways for AI assistants to read and shop your store: WebMCP on store forms, Markdown versions of pages, a full-content llms.txt, and a Markdown sitemap and AI catalog.',
			$description
		);
	}

	/**
	 * @testdox The description joins two experiments without a comma.
	 */
	public function test_description_joins_two_experiments_with_and(): void {
		$description = AiShoppingExperiments::get_description(
			array(
				'first'  => 'First',
				'second' => 'Second',
			)
		);

		$this->assertSame( 'Turn on newer ways for AI assistants to read and shop your store: First and Second.', $description );
	}

	/**
	 * @testdox The feature is experimental, off by default, and skips plugin compatibility checks.
	 */
	public function test_feature_definition(): void {
		$definition = $this->sut->get_feature_definition( AiShoppingExperiments::FEATURE_NAME );

		$this->assertNotNull( $definition, 'The feature should be registered.' );
		$this->assertSame( 'AI shopping experiments', $definition['name'] );
		$this->assertFalse( $definition['enabled_by_default'], 'The feature should be off by default.' );
		$this->assertTrue( $definition['is_experimental'], 'The feature should be experimental.' );
		$this->assertTrue( $this->sut->should_skip_compatibility_checks( AiShoppingExperiments::FEATURE_NAME ), 'Plugin compatibility checks should be skipped.' );
		$this->assertFalse( $this->sut->feature_is_enabled( AiShoppingExperiments::FEATURE_NAME ), 'The feature should start off.' );
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
		$option_name = $this->sut->feature_enable_option_name( AiShoppingExperiments::FEATURE_NAME );

		$rows = array_values(
			array_filter(
				$this->sut->add_feature_settings( array(), 'features' ),
				static fn( $setting ) => ( $setting['id'] ?? null ) === $option_name
			)
		);

		$this->assertCount( $expected_rows, $rows );
		if ( $expected_rows ) {
			$this->assertSame( 'AI shopping experiments', $rows[0]['title'] );
			$this->assertSame( 'checkbox', $rows[0]['type'] );
			$this->assertSame( 'no', $rows[0]['default'], 'The row should default to off.' );
			$this->assertSame( AiShoppingExperiments::get_description(), $rows[0]['desc'] );
		}
	}
}
