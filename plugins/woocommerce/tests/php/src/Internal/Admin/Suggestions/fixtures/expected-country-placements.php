<?php
/**
 * Expected payment suggestion placement, per country, per section.
 *
 * This is a transcription of the internal partnership tracking sheet. The test
 * suite proves the code matches this file — it cannot prove this file matches
 * the sheet. Only a harness run against a fresh export can do that.
 *
 * Last verified against the sheet: 2026-08-05, using the regional CSV exports of
 * that date and a clone-local audit harness.
 *
 * Sections mirror the sheet's rows. `primary_offline` describes the offline
 * profiler state; the baseline state leaves that slot empty and the test derives
 * the offline expectation from this entry.
 *
 * When editing: change entries deliberately, one country at a time, then run
 * `pnpm --filter=@woocommerce/plugin-woocommerce lint:php:fix -- <this file>`.
 * Re-verify against the sheet when a re-export lands or partner terms change,
 * and update the date above only when you have actually done so.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

return array(
	'CA' => array(
		'primary_psp'     => 'woopayments',
		'primary_apm'     => 'paypal_full_stack',
		'primary_offline' => 'square',
		'other_psp'       => array(
			'stripe',
			'square',
			'visa_as',
			'gocardless',
			'helcim',
		),
		'other_bnpl'      => array(
			'affirm',
			'afterpay',
			'klarna',
		),
	),
	'PM' => array(
		'primary_psp' => 'visa_as',
	),
	'US' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'primary_offline'        => 'square',
		'other_psp'              => array(
			'stripe',
			'square',
			'visa_as',
			'airwallex',
			'helcim',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'affirm',
			'afterpay',
			'klarna',
		),
	),
	'UM' => array(
		'primary_psp' => 'visa_as',
	),
	'GB' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'primary_offline'        => 'square',
		'other_psp'              => array(
			'stripe',
			'square',
			'mollie',
			'visa_as',
			'airwallex',
			'viva_wallet',
			'klarna_checkout',
			'gocardless',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'affirm',
			'clearpay',
			'klarna',
		),
	),
	'AX' => array(
		'primary_psp' => 'visa_as',
	),
	'AL' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_wallet',
	),
	'AD' => array(
		'primary_psp' => 'monei',
		'primary_apm' => 'paypal_wallet',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'AM' => array(
		'primary_psp' => 'visa_as',
	),
	'AT' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
			'airwallex',
			'viva_wallet',
			'gocardless',
			'klarna_checkout',
			'nexi_checkout',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'BY' => array(
		'primary_psp' => 'visa_as',
	),
	'BE' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
			'airwallex',
			'viva_wallet',
			'gocardless',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'BA' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_wallet',
	),
	'BV' => array(
		'primary_psp' => 'visa_as',
	),
	'BG' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
		),
	),
	'HR' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
			'gocardless',
		),
	),
	'CY' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
			'gocardless',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
	),
	'CZ' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
		),
		'other_bnpl'  => array(
			'klarna',
		),
	),
	'DK' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
			'gocardless',
			'klarna_checkout',
			'nexi_checkout',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'EE' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
			'gocardless',
		),
	),
	'FI' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
			'paytrail',
			'gocardless',
			'klarna_checkout',
		),
		'other_bnpl'  => array(
			'klarna',
		),
	),
	'FO' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'FR' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'primary_offline'        => 'square',
		'other_psp'              => array(
			'stripe',
			'square',
			'mollie',
			'visa_as',
			'airwallex',
			'viva_wallet',
			'gocardless',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'PF' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_wallet',
	),
	'GI' => array(
		'primary_psp' => 'stripe',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'DE' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
			'airwallex',
			'viva_wallet',
			'gocardless',
			'klarna_checkout',
			'nexi_checkout',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'GR' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
		),
		'other_bnpl'  => array(
			'klarna',
		),
	),
	'GL' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'GG' => array(
		'primary_psp' => 'visa_as',
	),
	'VA' => array(
		'primary_psp' => 'visa_as',
	),
	'HU' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'IS' => array(
		'primary_psp' => 'mollie',
		'primary_apm' => 'paypal_wallet',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'IE' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'primary_offline'        => 'square',
		'other_psp'              => array(
			'stripe',
			'square',
			'mollie',
			'visa_as',
			'airwallex',
			'viva_wallet',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'IM' => array(
		'primary_psp' => 'visa_as',
	),
	'IT' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
			'airwallex',
			'viva_wallet',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'JE' => array(
		'primary_psp' => 'visa_as',
	),
	'LV' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
		),
	),
	'LI' => array(
		'primary_psp' => 'stripe',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'mollie',
			'visa_as',
		),
	),
	'LT' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
			'airwallex',
		),
	),
	'LU' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
	),
	'MT' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
		),
	),
	'MD' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'MC' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_wallet',
	),
	'ME' => array(
		'primary_psp' => 'visa_as',
	),
	'NL' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
			'klarna_checkout',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'MK' => array(
		'primary_psp' => 'visa_as',
	),
	'NO' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
			'klarna_checkout',
			'nexi_checkout',
		),
		'other_bnpl'  => array(
			'klarna',
		),
	),
	'PL' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
			'airwallex',
			'viva_wallet',
		),
		'other_bnpl'  => array(
			'klarna',
		),
	),
	'PT' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
			'airwallex',
			'viva_wallet',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'RO' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
		),
		'other_bnpl'  => array(
			'klarna',
		),
	),
	'RU' => array(
		'primary_psp' => 'visa_as',
	),
	'SM' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'RS' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_wallet',
	),
	'SK' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
		),
		'other_bnpl'  => array(
			'klarna',
		),
	),
	'SI' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mollie',
			'visa_as',
		),
	),
	'ES' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'primary_offline'        => 'square',
		'other_psp'              => array(
			'stripe',
			'square',
			'mollie',
			'visa_as',
			'monei',
			'airwallex',
			'viva_wallet',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'SJ' => array(
		'primary_psp' => 'visa_as',
	),
	'SE' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
			'viva_wallet',
			'klarna_checkout',
			'nexi_checkout',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
	),
	'CH' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'other_psp'              => array(
			'stripe',
			'mollie',
			'visa_as',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
		'other_bnpl'             => array(
			'klarna',
		),
	),
	'TR' => array(
		'primary_psp' => 'visa_as',
	),
	'UA' => array(
		'primary_psp' => 'visa_as',
	),
	'AG' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'AI' => array(
		'primary_psp'  => 'tilopay',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'AR' => array(
		'primary_psp'  => 'mercado_pago',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'AW' => array(
		'primary_psp'  => 'tilopay',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'BS' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'BB' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'BZ' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'BM' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'BO' => array(
		'primary_psp'  => 'visa_as',
		'other_crypto' => array(
			'heliopay',
		),
	),
	'BQ' => array(
		'primary_psp'  => 'tilopay',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'BR' => array(
		'primary_psp'  => 'stripe',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'mercado_pago',
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'VG' => array(
		'primary_psp'  => 'tilopay',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'KY' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'CL' => array(
		'primary_psp'  => 'mercado_pago',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'CO' => array(
		'primary_psp'  => 'mercado_pago',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'CR' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'CU' => array(
		'primary_psp' => 'visa_as',
	),
	'CW' => array(
		'primary_psp'  => 'tilopay',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'DM' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'DO' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'EC' => array(
		'primary_psp'  => 'visa_as',
		'primary_apm'  => 'paypal_full_stack',
		'other_crypto' => array(
			'heliopay',
		),
	),
	'SV' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'FK' => array(
		'primary_psp'  => 'visa_as',
		'other_crypto' => array(
			'heliopay',
		),
	),
	'GF' => array(
		'primary_psp'  => 'visa_as',
		'primary_apm'  => 'paypal_full_stack',
		'other_crypto' => array(
			'heliopay',
		),
	),
	'GD' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'GP' => array(
		'primary_psp'  => 'visa_as',
		'primary_apm'  => 'paypal_full_stack',
		'other_crypto' => array(
			'heliopay',
		),
	),
	'GT' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'GY' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'HT' => array(
		'primary_psp' => 'visa_as',
	),
	'HN' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'JM' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'MQ' => array(
		'primary_psp'  => 'visa_as',
		'primary_apm'  => 'paypal_full_stack',
		'other_crypto' => array(
			'heliopay',
		),
	),
	'MX' => array(
		'primary_psp'  => 'stripe',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'mercado_pago',
			'visa_as',
		),
		'other_bnpl'   => array(
			'klarna',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'MS' => array(
		'primary_psp' => 'visa_as',
	),
	'NI' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'PA' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'PY' => array(
		'primary_psp'  => 'visa_as',
		'other_crypto' => array(
			'heliopay',
		),
	),
	'PE' => array(
		'primary_psp'  => 'mercado_pago',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'PR' => array(
		'primary_psp'  => 'visa_as',
		'other_crypto' => array(
			'heliopay',
		),
	),
	'BL' => array(
		'primary_psp'  => 'visa_as',
		'other_crypto' => array(
			'heliopay',
		),
	),
	'KN' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'LC' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'MF' => array(
		'primary_psp' => 'visa_as',
	),
	'VC' => array(
		'primary_psp'  => 'tilopay',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'SX' => array(
		'primary_psp'  => 'tilopay',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'GS' => array(
		'primary_psp' => 'visa_as',
	),
	'SR' => array(
		'primary_psp'  => 'tilopay',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'TT' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'TC' => array(
		'primary_psp'  => 'tilopay',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'UY' => array(
		'primary_psp'  => 'mercado_pago',
		'primary_apm'  => 'paypal_full_stack',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'VI' => array(
		'primary_psp'  => 'tilopay',
		'other_psp'    => array(
			'visa_as',
		),
		'other_crypto' => array(
			'heliopay',
		),
	),
	'VE' => array(
		'primary_apm'  => 'paypal_full_stack',
		'other_crypto' => array(
			'heliopay',
		),
	),
	'AQ' => array(
		'primary_psp' => 'visa_as',
	),
	'AS' => array(
		'primary_psp' => 'visa_as',
	),
	'AU' => array(
		'primary_psp'     => 'woopayments',
		'primary_apm'     => 'paypal_full_stack',
		'primary_offline' => 'square',
		'other_psp'       => array(
			'stripe',
			'square',
			'eway',
			'visa_as',
			'airwallex',
			'gocardless',
			'antom',
		),
		'other_bnpl'      => array(
			'afterpay',
			'klarna',
		),
	),
	'BD' => array(
		'primary_psp' => 'payoneer',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'IO' => array(
		'primary_psp' => 'visa_as',
	),
	'BN' => array(
		'primary_psp' => 'visa_as',
	),
	'KH' => array(
		'primary_psp' => 'visa_as',
	),
	'CN' => array(
		'primary_psp' => 'paypal_full_stack',
		'other_psp'   => array(
			'antom',
			'airwallex',
			'payoneer',
			'visa_as',
		),
	),
	'CX' => array(
		'primary_psp' => 'visa_as',
	),
	'CC' => array(
		'primary_psp' => 'visa_as',
	),
	'CK' => array(
		'primary_psp' => 'visa_as',
	),
	'FJ' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'GU' => array(
		'primary_psp' => 'visa_as',
	),
	'HM' => array(
		'primary_psp' => 'visa_as',
	),
	'HK' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'antom',
			'airwallex',
			'payoneer',
			'visa_as',
		),
	),
	'IN' => array(
		'primary_psp' => 'stripe',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'razorpay',
			'payu_india',
			'payoneer',
			'visa_as',
		),
	),
	'ID' => array(
		'primary_psp' => 'payoneer',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'JP' => array(
		'primary_psp'            => 'woopayments',
		'primary_apm'            => 'paypal_full_stack',
		'primary_offline'        => 'square',
		'other_psp'              => array(
			'stripe',
			'square',
			'komoju',
			'airwallex',
			'visa_as',
		),
		'other_express_checkout' => array(
			'amazon_pay',
		),
	),
	'KI' => array(
		'primary_psp' => 'visa_as',
	),
	'LA' => array(
		'primary_psp' => 'visa_as',
	),
	'MO' => array(
		'primary_psp' => 'visa_as',
	),
	'MY' => array(
		'primary_psp' => 'stripe',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'payoneer',
			'visa_as',
			'airwallex',
		),
	),
	'MV' => array(
		'primary_psp' => 'visa_as',
	),
	'MH' => array(
		'primary_psp' => 'visa_as',
	),
	'FM' => array(
		'primary_psp' => 'visa_as',
	),
	'MN' => array(
		'primary_psp' => 'visa_as',
	),
	'MM' => array(
		'primary_psp' => 'visa_as',
	),
	'NR' => array(
		'primary_psp' => 'visa_as',
	),
	'NP' => array(
		'primary_psp' => 'visa_as',
	),
	'NC' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'NZ' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'eway',
			'visa_as',
			'airwallex',
		),
		'other_bnpl'  => array(
			'afterpay',
			'klarna',
		),
	),
	'NU' => array(
		'primary_psp' => 'visa_as',
	),
	'NF' => array(
		'primary_psp' => 'visa_as',
	),
	'MP' => array(
		'primary_psp' => 'visa_as',
	),
	'PW' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'PG' => array(
		'primary_psp' => 'visa_as',
	),
	'PH' => array(
		'primary_psp' => 'payoneer',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'PN' => array(
		'primary_psp' => 'visa_as',
	),
	'WS' => array(
		'primary_psp' => 'visa_as',
	),
	'SG' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'antom',
			'airwallex',
			'visa_as',
		),
	),
	'SB' => array(
		'primary_psp' => 'visa_as',
	),
	'LK' => array(
		'primary_psp' => 'payoneer',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'KR' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'airwallex',
		),
	),
	'TW' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_wallet',
	),
	'TH' => array(
		'primary_psp' => 'stripe',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'payoneer',
			'visa_as',
		),
	),
	'TL' => array(
		'primary_psp' => 'visa_as',
	),
	'TK' => array(
		'primary_psp' => 'visa_as',
	),
	'TO' => array(
		'primary_psp' => 'visa_as',
	),
	'TV' => array(
		'primary_psp' => 'visa_as',
	),
	'VU' => array(
		'primary_psp' => 'visa_as',
	),
	'VN' => array(
		'primary_psp' => 'payoneer',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'WF' => array(
		'primary_psp' => 'visa_as',
	),
	'DZ' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'AO' => array(
		'primary_psp' => 'visa_as',
	),
	'BJ' => array(
		'primary_psp' => 'visa_as',
	),
	'BW' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'BF' => array(
		'primary_psp' => 'visa_as',
	),
	'BI' => array(
		'primary_psp' => 'visa_as',
	),
	'CV' => array(
		'primary_psp' => 'visa_as',
	),
	'CM' => array(
		'primary_psp' => 'visa_as',
	),
	'CF' => array(
		'primary_psp' => 'visa_as',
	),
	'TD' => array(
		'primary_psp' => 'visa_as',
	),
	'KM' => array(
		'primary_psp' => 'visa_as',
	),
	'CG' => array(
		'primary_psp' => 'visa_as',
	),
	'CI' => array(
		'primary_psp' => 'visa_as',
	),
	'EG' => array(
		'primary_psp' => 'mastercard',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'paymob',
			'visa_as',
		),
	),
	'CD' => array(
		'primary_psp' => 'visa_as',
	),
	'DJ' => array(
		'primary_psp' => 'visa_as',
	),
	'GQ' => array(
		'primary_psp' => 'visa_as',
	),
	'ER' => array(
		'primary_psp' => 'visa_as',
	),
	'SZ' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'ET' => array(
		'primary_psp' => 'visa_as',
	),
	'TF' => array(
		'primary_psp' => 'visa_as',
	),
	'GA' => array(
		'primary_psp' => 'visa_as',
	),
	'GH' => array(
		'primary_psp' => 'paystack',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'GM' => array(
		'primary_psp' => 'visa_as',
	),
	'GN' => array(
		'primary_psp' => 'visa_as',
	),
	'GW' => array(
		'primary_psp' => 'visa_as',
	),
	'KE' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'LS' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'LR' => array(
		'primary_psp' => 'visa_as',
	),
	'LY' => array(
		'primary_psp' => 'visa_as',
	),
	'MG' => array(
		'primary_psp' => 'visa_as',
	),
	'MW' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'ML' => array(
		'primary_psp' => 'visa_as',
	),
	'MR' => array(
		'primary_psp' => 'visa_as',
	),
	'MU' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'YT' => array(
		'primary_psp' => 'visa_as',
	),
	'MA' => array(
		'primary_psp' => 'payoneer',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'MZ' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'NA' => array(
		'primary_psp' => 'visa_as',
	),
	'NE' => array(
		'primary_psp' => 'visa_as',
	),
	'NG' => array(
		'primary_psp' => 'mastercard',
		'other_psp'   => array(
			'paystack',
			'visa_as',
		),
	),
	'RE' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'RW' => array(
		'primary_psp' => 'visa_as',
	),
	'SH' => array(
		'primary_psp' => 'visa_as',
	),
	'ST' => array(
		'primary_psp' => 'visa_as',
	),
	'SN' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'SC' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'SL' => array(
		'primary_psp' => 'visa_as',
	),
	'SO' => array(
		'primary_psp' => 'visa_as',
	),
	'ZA' => array(
		'primary_psp' => 'mastercard',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'paystack',
			'payfast',
			'visa_as',
		),
	),
	'SS' => array(
		'primary_psp' => 'visa_as',
	),
	'TZ' => array(
		'primary_psp' => 'visa_as',
	),
	'TG' => array(
		'primary_psp' => 'visa_as',
	),
	'TN' => array(
		'primary_psp' => 'visa_as',
	),
	'UG' => array(
		'primary_psp' => 'visa_as',
	),
	'EH' => array(
		'primary_psp' => 'visa_as',
	),
	'ZM' => array(
		'primary_psp' => 'visa_as',
	),
	'ZW' => array(
		'primary_psp' => 'visa_as',
	),
	'AF' => array(
		'primary_psp' => 'visa_as',
	),
	'AZ' => array(
		'primary_psp' => 'visa_as',
	),
	'BH' => array(
		'primary_psp' => 'mastercard',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'BT' => array(
		'primary_psp' => 'visa_as',
	),
	'GE' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'IR' => array(),
	'IQ' => array(
		'primary_psp' => 'visa_as',
	),
	'IL' => array(
		'primary_psp' => 'airwallex',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'JO' => array(
		'primary_psp' => 'mastercard',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'visa_as',
			'ngenius',
		),
	),
	'KZ' => array(
		'primary_psp' => 'visa_as',
		'primary_apm' => 'paypal_full_stack',
	),
	'KW' => array(
		'primary_psp' => 'mastercard',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'KG' => array(
		'primary_psp' => 'visa_as',
	),
	'LB' => array(
		'primary_psp' => 'visa_as',
	),
	'OM' => array(
		'primary_psp' => 'paymob',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'PK' => array(
		'primary_psp' => 'mastercard',
		'other_psp'   => array(
			'payoneer',
			'paymob',
			'visa_as',
		),
	),
	'PS' => array(
		'primary_psp' => 'visa_as',
	),
	'QA' => array(
		'primary_psp' => 'mastercard',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'visa_as',
		),
	),
	'SA' => array(
		'primary_psp' => 'mastercard',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'paymob',
			'visa_as',
			'ngenius',
		),
	),
	'SD' => array(
		'primary_psp' => 'visa_as',
	),
	'TJ' => array(
		'primary_psp' => 'visa_as',
	),
	'TM' => array(
		'primary_psp' => 'visa_as',
	),
	'AE' => array(
		'primary_psp' => 'woopayments',
		'primary_apm' => 'paypal_full_stack',
		'other_psp'   => array(
			'stripe',
			'mastercard',
			'payoneer',
			'paymob',
			'visa_as',
			'ngenius',
		),
	),
	'UZ' => array(
		'primary_psp' => 'visa_as',
	),
	'YE' => array(
		'primary_psp' => 'visa_as',
	),
);
