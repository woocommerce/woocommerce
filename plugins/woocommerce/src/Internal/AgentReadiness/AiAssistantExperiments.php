<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * The "AI assistant experiments" feature: one switch on the Features page for a
 * bundle of experimental ways for AI assistants to read the store.
 *
 * @since 11.5.0
 */
final class AiAssistantExperiments {

	/**
	 * Feature slug used with FeaturesController.
	 */
	public const FEATURE_NAME = 'ai_assistant_experiments';

	/**
	 * Experiment that relies on the store's AI training choice.
	 */
	public const AI_TRAINING_NOTICES = 'ai_training_notices';

	/**
	 * The experiments the switch covers, in display order.
	 *
	 * @return array<string, string> Labels, keyed by experiment slug.
	 */
	public static function get_experiments(): array {
		return array(
			'webmcp'                  => __( 'WebMCP on store forms', 'woocommerce' ),
			'markdown_pages'          => __( 'Markdown versions of pages', 'woocommerce' ),
			'llms_full_txt'           => __( 'a full-content llms.txt', 'woocommerce' ),
			'markdown_sitemap'        => __( 'a Markdown sitemap and AI catalog', 'woocommerce' ),
			self::AI_TRAINING_NOTICES => __( 'AI training notices', 'woocommerce' ),
		);
	}

	/**
	 * The experiments' labels joined into one list, for example "A, B, and C".
	 *
	 * @param array<string, string>|null $experiments Experiments. Defaults to get_experiments().
	 */
	public static function get_list( ?array $experiments = null ): string {
		return wp_sprintf( '%l', array_values( $experiments ?? self::get_experiments() ) );
	}

	/**
	 * Build the feature description shown next to the checkbox on the Features page.
	 *
	 * @param array<string, string>|null $experiments Experiments to list. Defaults to get_experiments().
	 * @return string Description HTML.
	 */
	public static function get_description( ?array $experiments = null ): string {
		$experiments = $experiments ?? self::get_experiments();

		$description = sprintf(
			/* translators: %s: list of experiments, for example "WebMCP on store forms, Markdown versions of pages, and AI training notices". */
			__( 'Turn on newer ways for AI assistants to read your store: %s.', 'woocommerce' ),
			self::get_list( $experiments )
		);

		if ( isset( $experiments[ self::AI_TRAINING_NOTICES ] ) ) {
			$description .= ' ' . sprintf(
				/* translators: %1$s: opening link tag, %2$s: closing link tag. */
				__( 'Uses your %1$sAI training choice%2$s.', 'woocommerce' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=site-visibility' ) ) . '">',
				'</a>'
			);
		}

		return $description;
	}
}
