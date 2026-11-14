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
	 * One row: an icon for its state, the title, the lines under it, then a button when there is one.
	 *
	 * @param string             $state  open, required, unknown, pass or off.
	 * @param string             $title  Escaped title HTML.
	 * @param array<int, string> $lines  Escaped HTML lines; empty ones are skipped.
	 * @param string             $action Escaped HTML at the end of the row, such as a Review button.
	 */
	private static function render_item( string $state, string $title, array $lines, string $action = '' ): void {
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
		echo '</div>' . wp_kses_post( $action ) . '</li>';
	}

	/**
	 * The badge on a setup step AI assistants can't shop the store without.
	 *
	 * @return string Badge HTML.
	 */
	private static function required_badge(): string {
		return '<mark class="wc-agentic-commerce-badge wc-agentic-commerce-badge--check">' . esc_html__( 'Required', 'woocommerce' ) . '</mark>';
	}

	/**
	 * Open a fold: "Show …" while closed, "Hide completed steps" while open. The caller closes the details element.
	 *
	 * @param string $show Text while closed.
	 */
	private static function open_more( string $show ): void {
		printf(
			'<details class="wc-agentic-commerce-more"><summary><span class="wc-agentic-commerce-more__show">%1$s</span><span class="wc-agentic-commerce-more__hide">%2$s</span></summary>',
			esc_html( $show ),
			esc_html__( 'Hide completed steps', 'woocommerce' )
		);
	}

	/**
	 * What an open check asks the merchant to do: the action, why it matters and where to do it.
	 * A policy page that is chosen but not published links to its editor; a published store page asks for another page.
	 *
	 * @param array{id:string,label?:string,why:string,fix_url:string} $check Store condition.
	 * @return array{0:string,1:string,2:string,3?:string} The action, why, where to do it, and the link text when the URL alone doesn't say it.
	 */
	private static function check_action( array $check ): array {
		$policies = array(
			'privacy_page'         => array( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, __( 'Publish your privacy policy page', 'woocommerce' ), __( 'Choose a privacy policy page', 'woocommerce' ), __( 'Shoppers can read how you handle their information before they buy.', 'woocommerce' ), __( 'Edit privacy policy', 'woocommerce' ) ),
			'terms_page'           => array( AgenticCommerceSettings::TERMS_PAGE_OPTION, __( 'Publish your terms and conditions page', 'woocommerce' ), __( 'Choose a terms and conditions page', 'woocommerce' ), __( 'Terms and conditions set out what shoppers agree to when they place an order.', 'woocommerce' ), __( 'Edit terms and conditions', 'woocommerce' ) ),
			'refund_returns_page'  => array( AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION, __( 'Publish your refund and returns policy page', 'woocommerce' ), __( 'Choose a refund and returns policy page', 'woocommerce' ), __( 'AI assistants link to it so customers can read it before they buy.', 'woocommerce' ), __( 'Edit refund policy', 'woocommerce' ), __( 'A refund policy needs to be available for AI assistants to share with shoppers.', 'woocommerce' ) ),
			'shipping_policy_page' => array( AgenticCommerceSettings::SHIPPING_POLICY_PAGE_OPTION, __( 'Publish your shipping policy page', 'woocommerce' ), __( 'Choose a shipping policy page', 'woocommerce' ), __( 'AI assistants share it so shoppers can check how and when you deliver before they buy.', 'woocommerce' ), __( 'Edit shipping policy', 'woocommerce' ) ),
		);
		if ( isset( $policies[ $check['id'] ] ) ) {
			$policy  = $policies[ $check['id'] ];
			$page_id = (int) get_option( $policy[0] );
			$edit    = $page_id > 0 && get_post( $page_id ) && 'publish' !== get_post_status( $page_id ) ? (string) get_edit_post_link( $page_id, 'raw' ) : '';
			return '' !== $edit
				? array( $policy[1], $policy[5] ?? __( 'It\'s still a draft. AI assistants only link to published pages.', 'woocommerce' ), $edit, $policy[4] )
				: array( $policy[2], $policy[3], $check['fix_url'] );
		}
		$actions = array(
			'launched'       => array( __( 'Make your store live', 'woocommerce' ), __( 'AI assistants can only find your products when your store is live.', 'woocommerce' ) ),
			'https'          => array( __( 'Use a secure connection (HTTPS)', 'woocommerce' ), __( 'AI assistants only connect to stores with a secure connection.', 'woocommerce' ) ),
			'search_visible' => array( __( 'Let search engines index your store', 'woocommerce' ), __( 'AI assistants use search engines to find stores and products.', 'woocommerce' ) ),
			'guest_checkout' => array( __( 'Allow guest checkout', 'woocommerce' ), __( 'Guest checkout lets shoppers from AI assistants pay without creating an account.', 'woocommerce' ) ),
			'order_emails'   => array( __( 'Turn on order confirmation emails', 'woocommerce' ), __( 'An email lets customers know their order went through.', 'woocommerce' ) ),
			'payment_method' => array( __( 'Set up payments', 'woocommerce' ), __( 'A payment method is needed so shoppers can pay on your checkout.', 'woocommerce' ) ),
		);
		$action  = $actions[ $check['id'] ] ?? array( $check['label'] ?? '', $check['why'] );
		// Site Health says how to move to HTTPS, and offers to do it where the host allows.
		return array( $action[0], $action[1], 'https' === $check['id'] ? admin_url( 'site-health.php' ) : $check['fix_url'] );
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
					'error'  => __( 'The setting didn’t save. Reload the page and try again.', 'woocommerce' ),
					'copied' => __( 'Copied', 'woocommerce' ),
				),
			)
		);
	}
}
