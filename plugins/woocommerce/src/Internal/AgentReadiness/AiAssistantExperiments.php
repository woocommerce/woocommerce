<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * The "Features for AI assistants" feature: one switch on the Features page for a
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
	 * User meta that records the user dismissed the notice on AI tools.
	 */
	public const NOTICE_DISMISSED_META = 'woocommerce_ai_assistant_experiments_notice_dismissed';

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
	 * The AI shopping health items (DeclaredToAgents ids) that show another plugin already provides each
	 * experiment. Finding any of them means the experiment steps aside.
	 *
	 * @return array<string, array<int, string>> Item ids, keyed by experiment slug.
	 */
	public static function get_detected_items(): array {
		return array(
			'webmcp'                  => array( 'webmcp-forms-schema' ),
			'markdown_pages'          => array( 'markdown-pages' ),
			'llms_full_txt'           => array( 'llms-full' ),
			'markdown_sitemap'        => array( 'sitemap-md' ),
			self::AI_TRAINING_NOTICES => array( 'ai-txt', 'tdmrep' ),
		);
	}

	/**
	 * The line under the Features page row naming the experiments another plugin already provides, from the
	 * last AI shopping health check, or an empty string when there are none or there's no check yet. WooCommerce
	 * doesn't serve these files yet, so any it finds come from somewhere else.
	 *
	 * @param array<string, string>|null $experiments Experiments. Defaults to get_experiments().
	 */
	public static function get_provided_elsewhere_note( ?array $experiments = null ): string {
		$report = get_option( AgentReadiness::REPORT_OPTION );
		$items  = is_array( $report ) && is_array( $report['declared'] ?? null ) ? $report['declared'] : array();
		$found  = array();
		foreach ( $items as $item ) {
			if ( is_array( $item ) && true === ( $item['found'] ?? null ) ) {
				$found[] = (string) ( $item['id'] ?? '' );
			}
		}
		$provided = array_filter(
			$experiments ?? self::get_experiments(),
			static fn( string $label, string $slug ): bool => array() !== array_intersect( self::get_detected_items()[ $slug ] ?? array(), $found ),
			ARRAY_FILTER_USE_BOTH
		);
		if ( array() === $provided ) {
			return '';
		}
		return sprintf(
			/* translators: %s: list of experiments, for example "a full-content llms.txt and AI training notices". */
			__( 'Another plugin already provides %s, so WooCommerce won’t add its own.', 'woocommerce' ),
			self::get_list( $provided )
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
