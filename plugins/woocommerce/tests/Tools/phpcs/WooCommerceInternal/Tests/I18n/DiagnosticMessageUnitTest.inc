<?php
/**
 * Fixture for DiagnosticMessageSniff.
 *
 * Each numbered case documents whether the sniff should flag it.
 *
 * @package WooCommerce\Tests\Tools\PHPCS
 */

// Case 1: translated message — FLAGGED.
wc_doing_it_wrong( __FUNCTION__, __( 'Do not call this yet.', 'woocommerce' ), '1.0.0' );

// Case 2: escaped translation nested in sprintf() across lines — FLAGGED.
_doing_it_wrong(
	__METHOD__,
	sprintf(
		esc_html__( 'Item "%s" is already registered.', 'woocommerce' ),
		esc_html( $name )
	),
	'1.0.0'
);

// Case 3: plural translation in trigger_error() — FLAGGED.
trigger_error( sprintf( _n( '%d item failed.', '%d items failed.', $count, 'woocommerce' ), $count ), E_USER_WARNING );

// Case 4: translated replacement in a deprecation notice, namespaced call — FLAGGED.
\_deprecated_function( __METHOD__, '1.0.0', _x( 'Use the new API.', 'deprecation', 'woocommerce' ) );

// Case 5: translated message passed by name — FLAGGED.
error_log( message: __( 'Missing package.', 'woocommerce' ) );

// Case 6: translated message in a deprecated hook dispatch — FLAGGED.
do_action_deprecated( 'old_hook', array( $value ), '1.0.0', 'new_hook', esc_html__( 'Use new_hook.', 'woocommerce' ) );

// Case 7: English messages, escaped or not — NOT flagged.
wc_doing_it_wrong( __FUNCTION__, 'Do not call this yet.', '1.0.0' );
_doing_it_wrong( __METHOD__, sprintf( esc_html( 'Item "%s" is already registered.' ), esc_html( $name ) ), '1.0.0' );

// Case 8: translated hook arguments, not the deprecation message — NOT flagged.
apply_filters_deprecated( 'old_filter', array( __( 'Label', 'woocommerce' ) ), '1.0.0', 'new_filter' );

// Case 9: method calls and function declarations sharing a name — NOT flagged.
$this->doing_it_wrong( __( 'Handled by the method author.', 'woocommerce' ) );
$logger->error_log( __( 'Not the PHP function.', 'woocommerce' ) );

// Case 10: translation outside the diagnostic call — NOT flagged.
$label = __( 'Save changes', 'woocommerce' );
wc_doing_it_wrong( __FUNCTION__, 'Uses a separate label.', '1.0.0' );

// Case 11: translation function names inside strings and comments — NOT flagged.
wc_doing_it_wrong( __FUNCTION__, 'Avoid calling __() here.', /* __( 'x' ) */ '1.0.0' );

// Case 12: translated message through LegacyProxy::call_function() — FLAGGED.
$this->proxy->call_function( 'wc_doing_it_wrong', __METHOD__, sprintf( __( '%s should be called later.', 'woocommerce' ), __METHOD__ ), '1.0.0' );

// Case 13: translated message in wp_trigger_error() — FLAGGED.
wp_trigger_error( __FUNCTION__, esc_html__( 'Something went wrong.', 'woocommerce' ) );

// Case 14: LegacyProxy calls that are English, or that proxy a non-diagnostic function — NOT flagged.
$this->proxy->call_function( 'wc_doing_it_wrong', __METHOD__, 'Should be called later.', '1.0.0' );
$this->proxy->call_function( 'wc_add_notice', __( 'Saved.', 'woocommerce' ) );
