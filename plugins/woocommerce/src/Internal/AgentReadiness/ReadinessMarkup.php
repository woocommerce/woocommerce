<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\Traits\ScriptDebug;

defined( 'ABSPATH' ) || exit;

/**
 * Markup of the AI tools tab's AI shopping section, and the wording of setup steps that Sales
 * readiness shares: check rows, their badges and actions, folds, and the script for the switch and the
 * copy button.
 *
 * @since 11.4.0
 */
trait ReadinessMarkup {

	use ScriptDebug;

	/**
	 * One row: an icon for its state, the title, then the lines under it.
	 *
	 * @param string             $state open, required, unknown, pass or off.
	 * @param string             $title Escaped title HTML.
	 * @param array<int, string> $lines Escaped HTML lines; empty ones are skipped.
	 */
	private static function render_item( string $state, string $title, array $lines ): void {
		$states = array(
			'open'     => __( 'Issue:', 'woocommerce' ),
			'required' => __( 'Issue:', 'woocommerce' ),
			'unknown'  => __( 'Couldn\'t check:', 'woocommerce' ),
			'pass'     => __( 'Passed:', 'woocommerce' ),
			'off'      => __( 'Not found:', 'woocommerce' ),
		);
		echo '<li class="wc-agentic-commerce-item wc-agentic-commerce-item--' . esc_attr( $state ) . '"><span class="wc-agentic-commerce-item__icon" aria-hidden="true"></span><div>';
		echo '<p class="wc-agentic-commerce-item__title"><span class="screen-reader-text">' . esc_html( $states[ $state ] ?? '' ) . ' </span>' . wp_kses_post( $title ) . '</p>';
		foreach ( array_filter( $lines, static fn( string $line ): bool => '' !== trim( $line ) ) as $line ) {
			echo '<p class="description">' . wp_kses_post( $line ) . '</p>';
		}
		echo '</div></li>';
	}

	/**
	 * A setup step's badge: Pass once done, otherwise Fix for a required step and Check for a recommended one.
	 *
	 * @param array{ok:bool|null,level:string} $check Store condition.
	 * @return string Badge HTML.
	 */
	private static function step_badge( array $check ): string {
		if ( true === $check['ok'] ) {
			$badge = array( 'pass', __( 'Pass', 'woocommerce' ) );
		} elseif ( StoreConditions::LEVEL_REQUIRED === $check['level'] ) {
			$badge = array( 'fix', __( 'Fix', 'woocommerce' ) );
		} else {
			$badge = array( 'check', __( 'Check', 'woocommerce' ) );
		}
		return '<mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--' . esc_attr( $badge[0] ) . '">' . esc_html( $badge[1] ) . '</mark>';
	}

	/**
	 * Open a fold: "Show …" while closed, "Hide" while open. The caller closes the details element.
	 *
	 * @param string $show Text while closed.
	 */
	private static function open_more( string $show ): void {
		printf(
			'<details class="wc-agentic-commerce-more"><summary><span class="wc-agentic-commerce-more__show">%1$s</span><span class="wc-agentic-commerce-more__hide">%2$s</span></summary>',
			esc_html( $show ),
			esc_html__( 'Hide', 'woocommerce' )
		);
	}

	/**
	 * What an open check asks the merchant to do: the action, why it matters and where to do it.
	 * A policy page that is chosen but not published links to its editor; a published store page asks for another page.
	 *
	 * @param array{id:string,label?:string,why:string,fix_url:string} $check Store condition.
	 * @return array{0:string,1:string,2:string}
	 */
	private static function check_action( array $check ): array {
		$policies = array(
			'privacy_page'         => array( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, __( 'Publish your privacy policy page', 'woocommerce' ), __( 'Choose a privacy policy page', 'woocommerce' ) ),
			'terms_page'           => array( AgenticCommerceSettings::TERMS_PAGE_OPTION, __( 'Publish your terms and conditions page', 'woocommerce' ), __( 'Choose a terms and conditions page', 'woocommerce' ) ),
			'refund_returns_page'  => array( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION, __( 'Publish your refund and returns policy page', 'woocommerce' ), __( 'Choose a refund and returns policy page', 'woocommerce' ) ),
			'shipping_policy_page' => array( AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION, __( 'Publish your shipping policy page', 'woocommerce' ), __( 'Choose a shipping policy page', 'woocommerce' ) ),
		);
		if ( isset( $policies[ $check['id'] ] ) ) {
			$page_id = (int) get_option( $policies[ $check['id'] ][0] );
			$edit    = $page_id > 0 && get_post( $page_id ) && 'publish' !== get_post_status( $page_id ) ? (string) get_edit_post_link( $page_id, 'raw' ) : '';
			return '' !== $edit
				? array( $policies[ $check['id'] ][1], __( 'It\'s still a draft. AI assistants only link to published pages.', 'woocommerce' ), $edit )
				: array( $policies[ $check['id'] ][2], $check['why'], $check['fix_url'] );
		}
		$actions = array(
			'launched'       => __( 'Take your store out of coming soon mode', 'woocommerce' ),
			'https'          => __( 'Move your store to HTTPS', 'woocommerce' ),
			'search_visible' => __( 'Let search engines index your store', 'woocommerce' ),
			'guest_checkout' => __( 'Allow guest checkout', 'woocommerce' ),
			'order_emails'   => __( 'Turn on order confirmation emails', 'woocommerce' ),
			'payment_method' => __( 'Turn on a payment method', 'woocommerce' ),
		);
		return array( $actions[ $check['id'] ] ?? ( $check['label'] ?? '' ), $check['why'], $check['fix_url'] );
	}

	/**
	 * Load the script for the switch and the copy button.
	 */
	private function enqueue_script(): void {
		wp_enqueue_script(
			'wc-agentic-commerce-settings',
			plugins_url( 'assets/js/admin/wc-agentic-commerce-settings' . $this->get_script_suffix() . '.js', WC_PLUGIN_FILE ),
			array( 'wp-api-fetch' ),
			WC_VERSION,
			true
		);
		wp_localize_script(
			'wc-agentic-commerce-settings',
			'wcAgenticCommerceSettings',
			array(
				'path' => '/wc/v3/agent-readiness/ucp',
				'i18n' => array(
					'error'  => __( 'The setting could not be changed. Reload the page and try again.', 'woocommerce' ),
					'copied' => __( 'Copied', 'woocommerce' ),
				),
			)
		);
	}
}
