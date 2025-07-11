<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Suggestions;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingProfile;
use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
use Automattic\WooCommerce\Internal\Utilities\ArrayUtil;

/**
 * Partner payments extension suggestions provider class.
 *
 * @internal
 */
class PaymentsExtensionSuggestions {
	/*
	 * The unique IDs for the payment extension suggestions.
	 *
	 * The ID is the primary extension identifier throughout the system.
	 */
	const AIRWALLEX         = 'airwallex';
	const ANTOM             = 'antom';
	const MERCADO_PAGO      = 'mercado_pago';
	const MOLLIE            = 'mollie';
	const PAYFAST           = 'payfast';
	const PAYMOB            = 'paymob';
	const PAYPAL_FULL_STACK = 'paypal_full_stack';
	const PAYPAL_WALLET     = 'paypal_wallet';
	const PAYONEER          = 'payoneer';
	const PAYSTACK          = 'paystack';
	const PAYTRAIL          = 'paytrail';
	const PAYU_INDIA        = 'payu_india';
	const RAZORPAY          = 'razorpay';
	const SQUARE            = 'square';
	const STRIPE            = 'stripe';
	const TILOPAY           = 'tilopay';
	const VIVA_WALLET       = 'viva_wallet';
	const WOOPAYMENTS       = 'woopayments';
	const AMAZON_PAY        = 'amazon_pay';
	const AFFIRM            = 'affirm';
	const AFTERPAY          = 'afterpay';
	const CLEARPAY          = 'clearpay';
	const KLARNA            = 'klarna';
	const KLARNA_CHECKOUT   = 'klarna_checkout';
	const HELIOPAY          = 'heliopay';
	const MONEI             = 'monei';
	const COINBASE          = 'coinbase';
	const BILLIE            = 'billie';
	const BOLT              = 'bolt_checkout';
	const AUTHORIZE_NET     = 'authorize_net';
	const DEPAY             = 'depay';
	const ELAVON            = 'elavon';
	const EWAY              = 'eway';
	const FORTISPAY         = 'fortis';
	const GOCARDLESS        = 'gocardless';
	const NEXI              = 'nexi';
	const PAYPAL_ZETTLE     = 'paypal_zettle';
	const RAPYD             = 'rapyd';
	const PAYPAL_BRAINTREE  = 'paypal_braintree';

	/*
	 * The extension types.
	 *
	 * The type is related to the extension's underlying payments methods scope and type.
	 */
	const TYPE_PSP              = 'psp'; // Payment Service Provider.
	const TYPE_APM              = 'apm'; // Alternative Payment Methods.
	const TYPE_EXPRESS_CHECKOUT = 'express_checkout';
	const TYPE_BNPL             = 'bnpl'; // Buy now, pay later.
	const TYPE_CRYPTO           = 'crypto';

	/*
	 * The extension plugin types.
	 *
	 * This will inform how we handle the extension installation and activation.
	 */
	const PLUGIN_TYPE_WPORG = 'wporg';

	/*
	 * The extension link types.
	 *
	 * These are hints for the UI to determine if and how to display the link.
	 */
	const LINK_TYPE_PRICING = 'pricing';
	const LINK_TYPE_ABOUT   = 'about';
	const LINK_TYPE_TERMS   = 'terms';
	const LINK_TYPE_DOCS    = 'documentation';
	const LINK_TYPE_SUPPORT = 'support';

	/*
	 * Extension tags.
	 *
	 * These are used to categorize the extensions and provide additional information to the system.
	 * Some tags may carry special meaning and will be used to influence the suggestions' behavior.
	 */
	const TAG_PREFERRED         = 'preferred';
	const TAG_PREFERRED_OFFLINE = 'preferred_offline'; // For extensions that are preferred for offline payments.
	const TAG_MADE_IN_WOO       = 'made_in_woo'; // For extensions developed by Woo.
	const TAG_RECOMMENDED       = 'recommended'; // For extensions that should be further emphasized.

	/**
	 * The memoized extensions base details to avoid computing them multiple times during a request.
	 *
	 * @var array|null
	 */
	private ?array $extensions_base_details_memo = null;

	/**
	 * The payment extension list for each country.
	 *
	 * The order is important as it will be used to determine the priority of the suggestions.
	 *
	 * Each entry is keyed by the two-letter country code and consists of a list of payment extensions.
	 * Each payment extension can be identified by its ID (the shorthand version) or by an array with the following format:
	 * array(
	 *   'id' => 'woopayments', // This is required.
	 *   '_type' => 'provider', // Overrides the '_type' key.
	 *   // Special entry that instructs the system to append the given items to a list-type entry.
	 *   // If the original entry is not a list, we will throw an exception.
	 *   // If the original entry does not exist, we will create it.
	 *   // This is useful when you want to add tags to a suggestion's default list of tags.
	 *   '_append' => array(
	 *       'tags' => array( self::TAG_PREFERRED ),
	 *   ),
	 *   // Special entry that instructs the system to remove the given items from a list-type entry.
	 *   // If the original entry is not a list, we will throw an exception.
	 *   // If the original entry does not exist, we will ignore the instruction.
	 *   // This is useful when you want to remove tags from a suggestion's default list of tags.
	 *   '_remove' => array(
	 *       'tags' => array( self::TAG_PREFERRED ),
	 *   ),
	 *   // Special entry that instructs the system to merge a list of items based on their _type key value,
	 *   // overriding the original entry with the provided one.
	 *   // If the original entry is not a list of arrays each with a _type entry, we will throw an exception.
	 *   // If the provided entry is not a list of arrays each with a _type entry, we will throw an exception.
	 *   // If the original entry does not exist, we will create it.
	 *   // This is useful when you want to override certain default details for a particular country.
	 *   '_merge_on_type' => array(
	 *       'links' => array(
	 *           array(
	 *               _type' => self::LINK_TYPE_PRICING,
	 *               'url'  => 'https://www.example.com/pricing',
	 *           ),
	 *       ),
	 *   ),
	 * )
	 * Use the extended format when you need to override the extension's default details for a particular country.
	 *
	 * @see plugins/woocommerce/i18n/countries.php for the list of supported country codes and their names.
	 *
	 * @var array
	 */
	private array $country_extensions = array(
		// North America.
		'CA' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::SQUARE     => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://squareup.com/ca/en/pricing',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://squareup.com/ca/en/legal/general/ua',
						),
					),
				),
			),
			self::GOCARDLESS => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://gocardless.com/en-ca/pricing/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::AFFIRM,
			self::AFTERPAY,
			self::KLARNA     => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/ca/business/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/ca/legal/',
						),
					),
				),
			),
		),
		'US' => array(
			self::WOOPAYMENTS => array(
				'_append' => array(
					'tags' => array( 'woopay_eligible' ), // Add a special tag that will be used to determine if the merchant is eligible for WooPay.
				),
			),
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::SQUARE, // Use the default details.
			self::AIRWALLEX,
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::AFFIRM,
			self::AFTERPAY,
			self::KLARNA, // Use the default details.
		),

		// UK + Europe.
		'GB' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::SQUARE          => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://squareup.com/gb/en/pricing',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://squareup.com/gb/en/legal/general/ua',
						),
					),
				),
			),
			self::MOLLIE,
			self::AIRWALLEX,
			self::VIVA_WALLET,
			self::KLARNA_CHECKOUT => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/uk/business/payment-methods/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/uk/terms-and-conditions/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::AFFIRM          => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.affirm.com/en-gb/business',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.affirm.com/en-gb/terms',
						),
					),
				),
			),
			self::CLEARPAY,
			self::KLARNA          => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/uk/business/payment-methods/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/uk/terms-and-conditions/',
						),
					),
				),
			),
		),
		'AL' => array(
			self::PAYPAL_WALLET => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'AD' => array(
			self::MONEI,
			self::PAYPAL_WALLET => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'AT' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::AIRWALLEX,
			self::VIVA_WALLET,
			self::GOCARDLESS      => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://gocardless.com/en-ie/pricing/',
						),
					),
				),
			),
			self::KLARNA_CHECKOUT => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/at/verkaeufer/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/at/agb/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA          => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/at/verkaeufer/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/at/agb/',
						),
					),
				),
			),
		),
		'BE' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::AIRWALLEX,
			self::VIVA_WALLET,
			self::GOCARDLESS => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://gocardless.com/en-ie/pricing/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA     => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/be/fr/entreprise/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/be/fr/conditions-generales/',
						),
					),
				),
			),
		),
		'BA' => array(
			self::PAYPAL_WALLET => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'BG' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
		),
		'HR' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::GOCARDLESS => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://gocardless.com/en-ie/pricing/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
		),
		'CY' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::GOCARDLESS => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://gocardless.com/en-ie/pricing/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
		),
		'CZ' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/cz/firmy/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/cz/obchodni-podminky/',
						),
					),
				),
			),
		),
		'DK' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::GOCARDLESS      => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://gocardless.com/da-dk/priser/',
						),
					),
				),
			),
			self::KLARNA_CHECKOUT => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/dk/erhverv/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/dk/vilkar/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA          => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/dk/erhverv/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/dk/vilkar/',
						),
					),
				),
			),
		),
		'EE' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::GOCARDLESS => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://gocardless.com/en-ie/pricing/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
		),
		'FI' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::GOCARDLESS      => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://gocardless.com/en-ie/pricing/',
						),
					),
				),
			),
			self::KLARNA_CHECKOUT => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/fi/yritys/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/fi/ehdot/',
						),
					),
				),
			),
			self::PAYTRAIL,
			self::PAYPAL_WALLET,
			self::KLARNA          => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/fi/yritys/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/fi/ehdot/',
						),
					),
				),
			),
		),
		'FO' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'FR' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::SQUARE     => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://squareup.com/fr/fr/pricing',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://squareup.com/fr/fr/legal/general/ua',
						),
					),
				),
			),
			self::MOLLIE,
			self::AIRWALLEX,
			self::VIVA_WALLET,
			self::GOCARDLESS => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://gocardless.com/fr-fr/tarifs/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA     => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/fr/entreprise/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/fr/legal/',
						),
					),
				),
			),
		),
		'PF' => array(
			self::PAYPAL_WALLET => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'GI' => array(
			self::STRIPE => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'DE' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::AIRWALLEX,
			self::VIVA_WALLET,
			self::GOCARDLESS      => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://gocardless.com/de-de/preise/',
						),
					),
				),
			),
			self::KLARNA_CHECKOUT => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/de/verkaeufer/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/de/agb/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA          => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/de/verkaeufer/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/de/agb/',
						),
					),
				),
			),
		),
		'GR' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/gr/business/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/gr/oroi-kai-proypotheseis/',
						),
					),
				),
			),
		),
		'GL' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'HU' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/hu/uzlet/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/hu/jogi-informaciok/',
						),
					),
				),
			),
		),
		'IS' => array(
			self::MOLLIE        => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_WALLET => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'IE' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::SQUARE => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://squareup.com/ie/en/pricing',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://squareup.com/ie/en/legal/general/ua',
						),
					),
				),
			),
			self::MOLLIE,
			self::AIRWALLEX,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/ie/business/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/ie/terms-and-conditions/',
						),
					),
				),
			),
		),
		'IT' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::AIRWALLEX,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/it/aziende/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/it/legal/',
						),
					),
				),
			),
		),
		'LV' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::PAYPAL_WALLET,
		),
		'LI' => array(
			self::STRIPE => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::MOLLIE,
			self::PAYPAL_WALLET,
		),
		'LT' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::AIRWALLEX,
			self::PAYPAL_WALLET,
		),
		'LU' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
		),
		'MT' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
		),
		'MD' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'MC' => array(
			self::PAYPAL_WALLET => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'NL' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::KLARNA_CHECKOUT => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/nl/zakelijk/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/nl/voorwaarden/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA          => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/nl/zakelijk/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/nl/voorwaarden/',
						),
					),
				),
			),
		),
		'NO' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::KLARNA_CHECKOUT => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/no/bedrift/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/no/vilkar/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::KLARNA          => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/no/bedrift/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/no/vilkar/',
						),
					),
				),
			),
		),
		'PL' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::AIRWALLEX,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/pl/biznes/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/pl/zasady-i-warunki/',
						),
					),
				),
			),
		),
		'PT' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::AIRWALLEX,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/pt/empresa/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/pt/termos-e-condicoes/',
						),
					),
				),
			),
		),
		'RO' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/ro/companii/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/ro/aspecte-juridice/',
						),
					),
				),
			),
		),
		'SM' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'RS' => array(
			self::PAYPAL_WALLET => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'SK' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::PAYPAL_WALLET,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/sk/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/sk/zmluvne-podmienky/',
						),
					),
				),
			),
		),
		'SI' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::PAYPAL_WALLET,
		),
		'ES' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::SQUARE => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://squareup.com/es/es/pricing',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://squareup.com/es/es/legal/general/ua',
						),
					),
				),
			),
			self::MOLLIE,
			self::MONEI,
			self::AIRWALLEX,
			self::VIVA_WALLET,
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/es/empresa/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/es/legal/',
						),
					),
				),
			),
		),
		'SE' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::VIVA_WALLET,
			self::KLARNA_CHECKOUT => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/international/enterprise/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/se/villkor/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
		),
		'CH' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::MOLLIE,
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/ch/fr/entreprise/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/ch/fr/conditions-generales-de-vente/',
						),
					),
				),
			),
		),

		// LATAM & Caribbeans.
		'AG' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'AI' => array(
			self::TILOPAY,
			self::HELIOPAY,
		),
		'AR' => array(
			self::MERCADO_PAGO => array(
				'_merge_on_type' => array(
					'links' => array(
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.mercadopago.com.ar/costs-section',
						),
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.mercadopago.com.ar/ayuda/terminos-y-politicas_194',
						),
					),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'AW' => array(
			self::TILOPAY,
			self::HELIOPAY,
		),
		'BS' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'BB' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'BZ' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'BM' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'BO' => array(
			self::HELIOPAY,
		),
		'BQ' => array(
			self::TILOPAY,
			self::HELIOPAY,
		),
		'BR' => array(
			self::STRIPE       => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::MERCADO_PAGO => array(
				'_merge_on_type' => array(
					'links' => array(
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.mercadopago.com.br/costs-section',
						),
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.mercadopago.com.br/ajuda/termos-e-politicas_194',
						),
					),
				),
				'_remove'        => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'VG' => array(
			self::TILOPAY,
			self::HELIOPAY,
		),
		'KY' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'CL' => array(
			self::MERCADO_PAGO => array(
				'_merge_on_type' => array(
					'links' => array(
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.mercadopago.cl/costs-section',
						),
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.mercadopago.cl/ayuda/terminos-y-politicas_194',
						),
					),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'CO' => array(
			self::MERCADO_PAGO => array(
				'_merge_on_type' => array(
					'links' => array(
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.mercadopago.com.co/costs-section',
						),
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.mercadopago.com.co/ayuda/terminos-y-politicas_194',
						),
					),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'CR' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'CW' => array(
			self::TILOPAY,
			self::HELIOPAY,
		),
		'DM' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'DO' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'EC' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'SV' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'FK' => array(
			self::HELIOPAY,
		),
		'GF' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'GD' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'GP' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'GT' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'GY' => array(
			self::TILOPAY,
			self::HELIOPAY,
		),
		'HN' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'JM' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'MQ' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'MX' => array(
			self::STRIPE       => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::MERCADO_PAGO => array(
				'_merge_on_type' => array(
					'links' => array(
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.mercadopago.com.mx/costs-section',
						),
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.mercadopago.com.mx/ayuda/terminos-y-politicas_194',
						),
					),
				),
				'_remove'        => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_WALLET,
			self::KLARNA       => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/mx/negocios/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/mx/terminos-y-condiciones/',
						),
					),
				),
			),
			self::HELIOPAY,
		),
		'NI' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'PA' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'PY' => array(
			self::HELIOPAY,
		),
		'PE' => array(
			self::MERCADO_PAGO => array(
				'_merge_on_type' => array(
					'links' => array(
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.mercadopago.com.pe/costs-section',
						),
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.mercadopago.com.pe/ayuda/terminos-y-politicas_194',
						),
					),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'KN' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'LC' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'SX' => array(
			self::TILOPAY,
			self::HELIOPAY,
		),
		'VC' => array(
			self::TILOPAY,
			self::HELIOPAY,
		),
		'SR' => array(
			self::TILOPAY,
			self::HELIOPAY,
		),
		'TT' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'TC' => array(
			self::TILOPAY,
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'UY' => array(
			self::MERCADO_PAGO => array(
				'_merge_on_type' => array(
					'links' => array(
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.mercadopago.com.uy/costs-section',
						),
						// See the extension code -> \MercadoPago\Woocommerce\Helpers\Links class.
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.mercadopago.com.uy/ayuda/terminos-y-politicas_194',
						),
					),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),
		'VI' => array(
			self::TILOPAY,
			self::HELIOPAY,
		),
		'VE' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
			self::HELIOPAY,
		),

		// APAC.
		'AU' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::SQUARE     => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://squareup.com/au/en/pricing',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://squareup.com/au/en/legal/general/ua',
						),
					),
				),
			),
			self::AIRWALLEX,
			self::ANTOM,
			self::GOCARDLESS => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://gocardless.com/en-au/pricing/',
						),
					),
				),
			),
			self::PAYPAL_WALLET,
			self::AFTERPAY,
			self::KLARNA     => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/au/business/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/au/legal/',
						),
					),
				),
			),
		),
		'BD' => array(
			self::PAYONEER => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'CN' => array(
			self::PAYPAL_FULL_STACK => array(
				'_type'   => self::TYPE_PSP, // Change the type to PSP.
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::ANTOM,
			self::AIRWALLEX,
			self::PAYONEER,
		),
		'FJ' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'GU' => array(),
		'HK' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::ANTOM,
			self::AIRWALLEX,
			self::PAYONEER,
			self::PAYPAL_FULL_STACK,
		),
		'IN' => array(
			self::STRIPE => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::RAZORPAY,
			self::PAYU_INDIA,
			self::PAYONEER,
			self::PAYPAL_WALLET,
		),
		'ID' => array(
			self::ANTOM => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYONEER,
			self::PAYPAL_WALLET,
		),
		'JP' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::SQUARE => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://squareup.com/jp/ja/pricing',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://squareup.com/jp/ja/legal/general/ua',
						),
					),
				),
			),
			self::ANTOM,
			self::PAYPAL_WALLET,
			self::AMAZON_PAY,
		),
		'MY' => array(
			self::STRIPE => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::ANTOM,
			self::PAYONEER,
			self::PAYPAL_WALLET,
		),
		'NC' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'NZ' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::AIRWALLEX,
			self::PAYPAL_WALLET,
			self::AFTERPAY,
			self::KLARNA => array(
				'_merge_on_type' => array(
					'links' => array(
						array(
							'_type' => self::LINK_TYPE_PRICING,
							'url'   => 'https://www.klarna.com/nz/business/',
						),
						array(
							'_type' => self::LINK_TYPE_TERMS,
							'url'   => 'https://www.klarna.com/nz/legal/',
						),
					),
				),
			),
		),
		'PW' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'PH' => array(
			self::ANTOM => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYONEER,
			self::PAYPAL_WALLET,
		),
		'SG' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::ANTOM,
			self::AIRWALLEX,
			self::PAYPAL_WALLET,
		),
		'LK' => array(
			self::PAYONEER => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'KR' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'TW' => array(
			self::PAYPAL_WALLET => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'TH' => array(
			self::STRIPE => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::ANTOM,
			self::PAYONEER,
			self::PAYPAL_WALLET,
		),
		'VN' => array(
			self::ANTOM => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYONEER,
			self::PAYPAL_WALLET,
		),

		// Africa.
		'DZ' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'AO' => array(),
		'BJ' => array(),
		'BW' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'BF' => array(),
		'BI' => array(),
		'CM' => array(),
		'CV' => array(),
		'CF' => array(),
		'TD' => array(),
		'KM' => array(),
		'CG' => array(),
		'CI' => array(),
		'EG' => array(
			self::PAYMOB => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'CD' => array(),
		'DJ' => array(),
		'GQ' => array(),
		'ER' => array(),
		'SZ' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'ET' => array(),
		'GA' => array(),
		'GH' => array(
			self::PAYSTACK => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'GM' => array(),
		'GN' => array(),
		'GW' => array(),
		'KE' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'LS' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'LR' => array(),
		'LY' => array(),
		'MG' => array(),
		'MW' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'ML' => array(),
		'MR' => array(),
		'MU' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'MA' => array(
			self::PAYONEER => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'MZ' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'NA' => array(),
		'NE' => array(),
		'NG' => array(
			self::PAYSTACK => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'RE' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'RW' => array(),
		'ST' => array(),
		'SN' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'SC' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'SL' => array(),
		'SO' => array(),
		'ZA' => array(
			self::PAYFAST => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYSTACK,
			self::PAYPAL_WALLET,
		),
		'SS' => array(),
		'TZ' => array(),
		'TG' => array(),
		'TN' => array(),
		'UG' => array(),
		'EH' => array(),
		'ZM' => array(),
		'ZW' => array(),

		// Middle East.
		'AF' => array(),
		'BH' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'GE' => array(
			self::PAYPAL_WALLET => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'IQ' => array(),
		'IL' => array(
			self::AIRWALLEX => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'JO' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'KZ' => array(
			self::PAYPAL_WALLET => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
		),
		'KW' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'LB' => array(),
		'OM' => array(
			self::PAYMOB => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'PK' => array(
			self::PAYONEER => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYMOB,
		),
		'QA' => array(
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'SA' => array(
			self::PAYMOB => array(
				'_append' => array(
					'tags' => array( self::TAG_PREFERRED ),
				),
			),
			self::PAYPAL_FULL_STACK,
			self::PAYPAL_WALLET,
		),
		'AE' => array(
			self::WOOPAYMENTS,
			self::PAYPAL_FULL_STACK,
			self::STRIPE,
			self::PAYONEER,
			self::PAYMOB,
			self::PAYPAL_WALLET,
		),
		'YE' => array(),
	);

	/**
	 * The context to incentive type map.
	 *
	 * @var array|string[]
	 */
	private array $context_to_incentive_type_map = array(
		Payments::SUGGESTIONS_CONTEXT => 'wc_settings_payments',
	);

	/**
	 * The suggestion incentives provider.
	 *
	 * @var PaymentsExtensionSuggestionIncentives
	 */
	private PaymentsExtensionSuggestionIncentives $suggestion_incentives;

	/**
	 * Initialize the class instance.
	 *
	 * @param PaymentsExtensionSuggestionIncentives $suggestion_incentives The suggestion incentives provider.
	 *
	 * @internal
	 */
	final public function init( PaymentsExtensionSuggestionIncentives $suggestion_incentives ) {
		$this->suggestion_incentives = $suggestion_incentives;
	}

	/**
	 * Get the list of payment extensions details for a specific country.
	 *
	 * @param string $country_code The two-letter country code.
	 * @param string $context      Optional. The context ID of where these extensions are being used.
	 *
	 * @return array The list of payment extensions (their full details) for the given country.
	 *               Empty array if no extensions are available for the country or the country is not supported.
	 * @throws \Exception If there were malformed or invalid extension details.
	 */
	public function get_country_extensions( string $country_code, string $context = '' ): array {
		$country_code = strtoupper( $country_code );

		if ( empty( $this->country_extensions[ $country_code ] ) ||
			! is_array( $this->country_extensions[ $country_code ] ) ) {

			return array();
		}

		// Process the extensions.
		$processed_extensions = array();
		$priority             = 0;
		foreach ( $this->country_extensions[ $country_code ] as $key => $details ) {
			// Check the formats we support.
			if ( is_int( $key ) && is_string( $details ) ) {
				$extension_id              = $details;
				$extension_country_details = array();
			} elseif ( is_string( $key ) && is_array( $details ) ) {
				$extension_id              = $key;
				$extension_country_details = $details;
			} else {
				// Just ignore the entry as it is malformed.
				continue;
			}

			// Determine if the extension should be included based on the store's state, the provided country and context.
			if ( ! $this->is_extension_allowed( $extension_id, $country_code, $context ) ) {
				continue;
			}

			// Determine the extension details for the given country.
			$extension_base_details = $this->get_extension_base_details( $extension_id ) ?? array();
			$extension_details      = $this->with_country_details( $extension_base_details, $extension_country_details );

			// Apply any changes to the extension details based on the store's state.
			$extension_details = $this->with_store_state_details( $extension_id, $extension_details );

			// Check if there is an incentive for this extension and attach its details.
			$incentive = $this->get_extension_incentive( $extension_id, $country_code, $context );
			if ( is_array( $incentive ) && ! empty( $incentive ) ) {
				$extension_details['_incentive'] = $incentive;
			}

			// Include the extension ID.
			$extension_details['id'] = $extension_id;

			// Lock in the priority for ordering purposes.
			// We respect the order in the country extensions list.
			// We use increments of 10 to allow for easy insertions.
			$priority                      += 10;
			$extension_details['_priority'] = $priority;

			$processed_extensions[] = $this->standardize_extension_details( $extension_details );
		}

		return $processed_extensions;
	}

	/**
	 * Get the base details of a payment extension by its ID.
	 *
	 * @param string $extension_id The extension id.
	 *
	 * @return array|null The extension details for the given ID. Null if not found.
	 */
	public function get_by_id( string $extension_id ): ?array {
		$extension_id = sanitize_title( $extension_id );

		$extensions = $this->get_all_extensions_base_details();
		if ( isset( $extensions[ $extension_id ] ) ) {
			$extension_details              = $extensions[ $extension_id ];
			$extension_details['id']        = $extension_id;
			$extension_details['_priority'] = 0;

			return $this->standardize_extension_details( $extension_details );
		}

		return null;
	}

	/**
	 * Get the base details of a payment extension by its plugin slug.
	 *
	 * If there are multiple extensions with the same plugin slug, the first one found will be returned.
	 *
	 * @param string $plugin_slug  The plugin slug.
	 * @param string $country_code Optional. The two-letter country code for which the extension suggestion should be retrieved.
	 * @param string $context      Optional. The context ID of where this extension suggestion is being used.
	 *
	 * @return array|null The extension details for the given plugin slug. Null if not found or the slug is empty.
	 */
	public function get_by_plugin_slug( string $plugin_slug, string $country_code = '', string $context = '' ): ?array {
		$plugin_slug = sanitize_title( $plugin_slug );
		if ( empty( $plugin_slug ) ) {
			return null;
		}

		// If we have a country code, try to find a fully localized extension suggestion.
		if ( ! empty( $country_code ) ) {
			$extensions = $this->get_country_extensions( $country_code, $context );
			foreach ( $extensions as $extension_details ) {
				if ( isset( $extension_details['plugin']['slug'] ) &&
					$plugin_slug === $extension_details['plugin']['slug']
				) {
					// The extension details are already standardized.
					return $extension_details;
				}
			}
		}

		// Fallback to the base details.
		$extensions = $this->get_all_extensions_base_details();
		foreach ( $extensions as $extension_id => $extension_details ) {
			if ( isset( $extension_details['plugin']['slug'] ) &&
				$plugin_slug === $extension_details['plugin']['slug']
			) {
				$extension_details['id']        = $extension_id;
				$extension_details['_priority'] = 0;

				return $this->standardize_extension_details( $extension_details );
			}
		}

		return null;
	}

	/**
	 * Dismiss an incentive for a specific payment extension suggestion.
	 *
	 * @param string $incentive_id  The incentive ID.
	 * @param string $suggestion_id The suggestion ID.
	 * @param string $context       Optional. The context ID for which the incentive should be dismissed.
	 *                              If not provided, the incentive will be dismissed for all contexts.
	 *
	 * @return bool True if the incentive was not previously dismissed and now it is.
	 *              False if the incentive was already dismissed or could not be dismissed.
	 * @throws \Exception If the incentive could not be dismissed due to an error.
	 */
	public function dismiss_incentive( string $incentive_id, string $suggestion_id, string $context = 'all' ): bool {
		return $this->suggestion_incentives->dismiss_incentive( $incentive_id, $suggestion_id, $context );
	}

	/**
	 * Determine if a payment extension is allowed to be suggested.
	 *
	 * @param string $extension_id The extension ID.
	 * @param string $country_code The two-letter country code.
	 * @param string $context      Optional. The context ID of where the extension is being used.
	 *
	 * @return bool True if the extension is allowed, false otherwise.
	 *              Defaults to true if there is no specific logic for the extension.
	 */
	private function is_extension_allowed( string $extension_id, string $country_code, string $context = '' ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Add per-extension exclusion logic here.
		// Returning true for now to avoid excluding any extensions.
		return true;
	}

	/**
	 * Merges country-specific details into the base details of a payment extension.
	 *
	 * This function processes special `_append`, `_remove`, and `_merge_on_type` instructions to modify
	 * list-type entries within the base details.
	 *
	 * @param array $base_details    The base details of the payment extension.
	 * @param array $country_details The country-specific details, which may include
	 *                               special `_append` and `_remove` instructions.
	 *
	 * @return array The merged details, with country-specific modifications applied.
	 *
	 * @throws \Exception If the country extension details are malformed or invalid.
	 */
	private function with_country_details( array $base_details, array $country_details ): array {
		// Process any append instructions.
		if ( isset( $country_details['_append'] ) ) {
			if ( ! is_array( $country_details['_append'] ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new \Exception( 'Malformed country extension details _append entry.' );
			}
			foreach ( $country_details['_append'] as $append_key => $append_list ) {
				// Sanity checks.
				if ( ! is_string( $append_key ) ||
					! is_array( $append_list ) ||
					! ArrayUtil::array_is_list( $append_list )
				) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new \Exception( 'Malformed country extension details _append details.' );
				}
				// If the target entry doesn't exist, create it as an empty list.
				if ( ! isset( $base_details[ $append_key ] ) ) {
					$base_details[ $append_key ] = array();
				}
				if ( ! is_array( $base_details[ $append_key ] ) ||
					! ArrayUtil::array_is_list( $base_details[ $append_key ] )
				) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new \Exception( 'Invalid country extension details _append target.' );
				}

				$base_details[ $append_key ] = array_merge( $base_details[ $append_key ], $append_list );
			}

			// Remove the special entry because we don't need it anymore.
			unset( $country_details['_append'] );
		}

		// Process any remove instructions.
		if ( isset( $country_details['_remove'] ) ) {
			if ( ! is_array( $country_details['_remove'] ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new \Exception( 'Malformed country extension details _remove entry.' );
			}
			foreach ( $country_details['_remove'] as $removal_key => $removal_list ) {
				// Sanity checks.
				if ( ! is_string( $removal_key ) ||
					! is_array( $removal_list ) ||
					! ArrayUtil::array_is_list( $removal_list )
				) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new \Exception( 'Malformed country extension details _remove details.' );
				}
				if ( ! isset( $base_details[ $removal_key ] ) ) {
					// If the target entry doesn't exist, we don't need to do anything.
					continue;
				}
				if ( ! is_array( $base_details[ $removal_key ] ) ||
					! ArrayUtil::array_is_list( $base_details[ $removal_key ] )
				) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new \Exception( 'Invalid country extension details _remove target.' );
				}

				$base_details[ $removal_key ] = array_diff( $base_details[ $removal_key ], $removal_list );
			}

			// Remove the special entry because we don't need it anymore.
			unset( $country_details['_remove'] );
		}

		// Process any merge on type instructions.
		if ( isset( $country_details['_merge_on_type'] ) ) {
			if ( ! is_array( $country_details['_merge_on_type'] ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new \Exception( 'Malformed country extension details _merge_on_type entry.' );
			}
			foreach ( $country_details['_merge_on_type'] as $merge_key => $merge_list ) {
				// Sanity checks.
				if ( ! is_string( $merge_key ) ||
					! is_array( $merge_list ) ||
					! ArrayUtil::array_is_list( $merge_list ) ||
					count( array_column( $merge_list, '_type' ) ) !== count( $merge_list )
				) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new \Exception( 'Malformed country extension details _merge_on_type details.' );
				}
				if ( ! isset( $base_details[ $merge_key ] ) ) {
					// If the target entry doesn't exist, create it.
					$base_details[ $merge_key ] = array();
				}
				if ( ! is_array( $base_details[ $merge_key ] ) ||
					! ArrayUtil::array_is_list( $base_details[ $merge_key ] ) ||
					count( array_column( $base_details[ $merge_key ], '_type' ) ) !== count( $base_details[ $merge_key ] )
				) {
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new \Exception( 'Invalid country extension details _merge_on_type target.' );
				}

				// Merge the lists based on the '_type' values.
				$base_details[ $merge_key ] = ArrayUtil::merge_by_key( $base_details[ $merge_key ], $merge_list, '_type' );
			}

			// Remove the special entry because we don't need it anymore.
			unset( $country_details['_merge_on_type'] );
		}

		// Merge any remaining country details so they overwrite the base details.
		return array_merge( $base_details, $country_details );
	}

	/**
	 * Apply customizations to the extension details based on the store's state.
	 *
	 * The customizations may be general or specific to certain extensions.
	 * The store's state refers to various aspects of the store's configuration, collected data,
	 * store setup/launch process, onboarding task completion, etc.
	 *
	 * @param string $extension_id      The extension ID.
	 * @param array  $extension_details The extension details.
	 *
	 * @return array The modified extension details.
	 */
	private function with_store_state_details( string $extension_id, array $extension_details ): array {
		// For Square, we add the preferred tags if the merchant self-identified as selling offline via the core profiler.
		if ( self::SQUARE === $extension_id && $this->is_merchant_selling_offline() ) {
			if ( empty( $extension_details['tags'] ) ) {
				$extension_details['tags'] = array();
			}
			$extension_details['tags'][] = self::TAG_PREFERRED;
			$extension_details['tags'][] = self::TAG_PREFERRED_OFFLINE;
		}

		return $extension_details;
	}

	/**
	 * Get the incentive details for a given extension and country, if any.
	 *
	 * @param string $extension_id The extension ID.
	 * @param string $country_code The two-letter country code.
	 * @param string $context      Optional. The context ID of where the extension incentive is being used.
	 *
	 * @return array|null The incentive details for the given extension and country. Null if not found.
	 */
	private function get_extension_incentive( string $extension_id, string $country_code, string $context = '' ): ?array {
		// Try to map the context to an incentive type.
		$incentive_type = '';
		if ( isset( $this->context_to_incentive_type_map[ $context ] ) ) {
			$incentive_type = $this->context_to_incentive_type_map[ $context ];
		}

		$incentives = $this->suggestion_incentives->get_incentives( $extension_id, $country_code, $incentive_type );
		if ( empty( $incentives ) ) {
			return null;
		}

		// Use the first incentive, in case there are multiple.
		$incentive = reset( $incentives );

		// Sanitize the incentive details.
		$incentive = $this->sanitize_extension_incentive( $incentive );

		// Enhance the incentive details.
		$incentive['_suggestion_id'] = $extension_id;
		// Add the dismissals list.
		$incentive['_dismissals'] = $this->suggestion_incentives->get_incentive_dismissals( $incentive['id'], $extension_id );

		return $incentive;
	}

	/**
	 * Sanitize the incentive details for a payment extension.
	 *
	 * @param array $incentive The incentive details.
	 *
	 * @return array The sanitized incentive details.
	 */
	private function sanitize_extension_incentive( array $incentive ): array {
		// Apply a very lose sanitization. Stricter sanitization can be applied downstream, if needed.
		return array_map(
			function ( $value ) {
				// Make sure that if we have HTML tags, we only allow a limited set of tags (only stylistic ones).
				if ( is_string( $value ) && preg_match( '/<[^>]+>/', $value ) ) {
						$value = wp_kses( $value, wp_kses_allowed_html( 'data' ) );
				}

				return $value;
			},
			$incentive
		);
	}

	/**
	 * Get the base details of all extensions.
	 *
	 * @return array[] The base details of all extensions.
	 */
	private function get_all_extensions_base_details(): array {
		if ( isset( $this->extensions_base_details_memo ) ) {
			return $this->extensions_base_details_memo;
		}
		$this->extensions_base_details_memo = array(
			self::AIRWALLEX         => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Airwallex Payments', 'woocommerce' ),
				'description' => esc_html__( 'Boost international sales and save on FX fees. Accept 60+ local payment methods including Apple Pay and Google Pay.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/airwallex.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/airwallex.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'airwallex-online-payments-gateway',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.airwallex.com/pricing',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/airwallexpayments/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.airwallex.com/terms/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://www.airwallex.com/docs/payments__plugins__woocommerce__install-the-woocommerce-plugin',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://help.airwallex.com/',
					),
				),
			),
			self::ANTOM             => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Antom', 'woocommerce' ),
				'description' => esc_html__( 'Your trusted payments partner in Asia and around the world.', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/antom.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'antom-payments',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/antom-payments/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://global.alipay.com/docs/ac/Platform/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/antom-payment/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=antom-payments',
					),
				),
			),
			self::MERCADO_PAGO      => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Mercado Pago', 'woocommerce' ),
				'description' => esc_html__( 'Set up your payment methods and accept credit and debit cards, cash, bank transfers and money from your Mercado Pago account. Offer safe and secure payments with Latin America’s leading processor.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/mercadopago.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/mercadopago.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-mercadopago',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/mercado-pago-checkout/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/mercado-pago/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=mercado-pago-checkout',
					),
				),
				'tags'        => array( self::TAG_PREFERRED ),
			),
			self::MOLLIE            => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Mollie', 'woocommerce' ),
				'description' => esc_html__( 'Effortless payments by Mollie: Offer global and local payment methods, get onboarded in minutes, and supported in your language.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/mollie.svg', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/mollie.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'mollie-payments-for-woocommerce',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.mollie.com/pricing',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/mollie-payments-for-woocommerce/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.mollie.com/user-agreement',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/mollie-payments-for-woocommerce/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://discord.com/invite/mollie',
					),
				),
			),
			self::PAYFAST           => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Payfast', 'woocommerce' ),
				'description' => esc_html__( 'The Payfast extension for WooCommerce enables you to accept payments by Credit Card and EFT via one of South Africa\'s most popular payment gateways. No setup fees or monthly subscription costs. Selecting this extension will configure your store to use South African rands as the selected currency.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/payfast.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/payfast.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-payfast-gateway',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://payfast.io/fees/',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/payfast-payment-gateway/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://payfast.io/legal/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/payfast-payment-gateway/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=payfast-payment-gateway',
					),
				),
				'tags'        => array( self::TAG_MADE_IN_WOO ),
			),
			self::PAYMOB            => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Paymob', 'woocommerce' ),
				'description' => esc_html__( 'Paymob is a leading payment gateway in the Middle East and Africa. Accept payments online and in-store with Paymob.', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/paymob.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'paymob-for-woocommerce',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://paymob.com/en/pricing',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/paymob/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://paymob.com/en/policy',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/paymob-for-woocommerce/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=paymob',
					),
				),
			),
			self::PAYPAL_FULL_STACK => array(
				'_type'       => self::TYPE_APM,
				'title'       => esc_html__( 'PayPal Payments', 'woocommerce' ),
				'description' => esc_html__( 'PayPal Payments lets you offer PayPal, Venmo (US only), Pay Later options and more.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/paypal.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/paypal.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-paypal-payments',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.paypal.com/webapps/mpp/merchant-fees',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/woocommerce-paypal-payments/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.paypal.com/legalhub/home',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/woocommerce-paypal-payments/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=woocommerce-paypal-payments',
					),
				),
				'tags'        => array( self::TAG_MADE_IN_WOO, self::TAG_PREFERRED ),
			),
			self::PAYPAL_WALLET     => array(
				'_type'       => self::TYPE_EXPRESS_CHECKOUT,
				'title'       => esc_html__( 'PayPal Payments', 'woocommerce' ),
				'description' => esc_html__( 'Safe and secure payments using your customer\'s PayPal account.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/paypal.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/paypal.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-paypal-payments',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.paypal.com/webapps/mpp/merchant-fees#advanced_cd_payments',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/woocommerce-paypal-payments/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.paypal.com/legalhub/home',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/woocommerce-paypal-payments/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=woocommerce-paypal-payments',
					),
				),
				'tags'        => array( self::TAG_MADE_IN_WOO ),
			),
			self::PAYONEER          => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Payoneer Checkout', 'woocommerce' ),
				'description' => esc_html__( 'Payoneer Checkout is the next generation of payment processing platforms, giving merchants around the world the solutions and direction they need to succeed in today\'s hyper-competitive global market.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/payoneer.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/payoneer.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'payoneer-checkout',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.payoneer.com/about/pricing/',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/payoneer-checkout/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.payoneer.com/legal-agreements/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://checkoutdocs.payoneer.com/docs/about-woocommerce-integration',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://checkoutdocs.payoneer.com/docs/troubleshoot-woocommerce',
					),
				),
			),
			self::PAYSTACK          => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Paystack', 'woocommerce' ),
				'description' => esc_html__( 'Paystack helps African merchants accept one-time and recurring payments online with a modern, safe, and secure payment gateway.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/paystack.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/paystack.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woo-paystack',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://paystack.com/pricing',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/paystack/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://paystack.com/terms',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/paystack/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://support.paystack.com/en/articles/2130754',
					),
				),
			),
			self::PAYTRAIL          => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Paytrail', 'woocommerce' ),
				'description' => esc_html__( 'Accept all popular payment methods for Finnish B2C and B2B customers', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/paytrail.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'paytrail-for-woocommerce',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.paytrail.com/en/pricing',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/paytrail/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.paytrail.com/en/terms-conditions',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/paytrail-for-woocommerce/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://www.paytrail.com/en/customer-service#merchants',
					),
				),
			),
			self::PAYU_INDIA        => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'PayU India', 'woocommerce' ),
				'description' => esc_html__( 'Enable PayU\'s exclusive plugin for WooCommerce to start accepting payments in 100+ payment methods available in India including credit cards, debit cards, UPI, & more!', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/payu.svg', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/payu.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'payu-india',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://payu.in/pricing/',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/payu-india/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://payu.in/payu-terms-and-conditions/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://payu.in/plugins/payment-gateway-for-woocommerce-plugin',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://help.payu.in/',
					),
				),
			),
			self::RAZORPAY          => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Razorpay', 'woocommerce' ),
				'description' => esc_html__( 'The official Razorpay extension for WooCommerce allows you to accept credit cards, debit cards, netbanking, wallet, and UPI payments.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/razorpay.svg', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/razorpay.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woo-razorpay',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://razorpay.com/pricing/',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/razorpay-for-woocommerce/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://razorpay.com/terms/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://razorpay.com/docs/payment-gateway/ecommerce-plugins/woocommerce/woocommerce-pg/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://razorpay.com/support/',
					),
				),
			),
			self::SQUARE            => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Square', 'woocommerce' ),
				'description' => esc_html__( 'Securely accept credit and debit cards with one low rate, no surprise fees (custom rates available). Sell in store and track sales and inventory in one place.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/square-black.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/square.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-square',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://squareup.com/pricing',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/square/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://squareup.com/legal/general/ua',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/woocommerce-square/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=square',
					),
				),
				'tags'        => array( self::TAG_MADE_IN_WOO ),
			),
			self::STRIPE            => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Stripe', 'woocommerce' ),
				'description' => esc_html__( 'Accept debit and credit cards in 135+ currencies, methods such as Alipay, and one-touch checkout with Apple Pay.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/stripe.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/stripe.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-gateway-stripe',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://stripe.com/pricing',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/stripe/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://stripe.com/legal/connect-account',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/stripe',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=stripe',
					),
				),
				'tags'        => array( self::TAG_MADE_IN_WOO ),
			),
			self::TILOPAY           => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Tilopay', 'woocommerce' ),
				'description' => esc_html__( 'Accept credit and debit cards on your WooCommerce store with advanced features like partial refunds, full/partial captures, and 3D Secure security.', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/tilopay.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'tilopay',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://tilopay.com/tarifas',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://tilopay.com/tilopay-checkout',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://tilopay.com/terminos-condiciones',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://tilopay.com/documentacion/plataforma-woocommerce',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://cst.support.tilopay.com/servicedesk/customer/portals',
					),
				),
				'tags'        => array( self::TAG_PREFERRED ),
			),
			self::VIVA_WALLET       => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Viva.com Smart Checkout', 'woocommerce' ),
				'description' => esc_html__( 'A European payments solution that allows you to accept payments in over 25 countries and multiple currencies.', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/vivacom.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'viva-com-smart-for-woocommerce',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.viva.com/pricing',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/viva-com-smart-for-woocommerce/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.viva.com/terms-portal',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/viva-com-smart-for-woocommerce/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=viva-com-smart-for-woocommerce',
					),
				),
			),
			self::WOOPAYMENTS       => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Accept payments with Woo', 'woocommerce' ),
				'description' => esc_html__( 'Credit/debit cards, Apple Pay, Google Pay, and more.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/woopayments.svg', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/woo.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-payments',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://woocommerce.com/document/woopayments/fees-and-debits/',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/payments/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://woocommerce.com/document/woopayments/our-policies/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/woopayments/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=woopayments',
					),
				),
				'tags'        => array( self::TAG_MADE_IN_WOO, self::TAG_PREFERRED ),
			),
			self::AMAZON_PAY        => array(
				'_type'       => self::TYPE_EXPRESS_CHECKOUT,
				'title'       => esc_html__( 'Amazon Pay', 'woocommerce' ),
				'description' => esc_html__( 'Enable a familiar, fast checkout for hundreds of millions of active Amazon customers globally.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/amazonpay.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/amazonpay.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-gateway-amazon-payments-advanced',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://pay.amazon.com/help/201212280',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/pay-with-amazon/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://pay.amazon.com/help/201212430',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/amazon-payments-advanced/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=pay-with-amazon',
					),
				),
				'tags'        => array( self::TAG_MADE_IN_WOO ),
			),
			self::AFFIRM            => array(
				'_type'       => self::TYPE_BNPL,
				'title'       => esc_html__( 'Affirm', 'woocommerce' ),
				'description' => esc_html__( 'Affirm\'s tailored Buy Now Pay Later programs remove price as a barrier, turning browsers into buyers, increasing average order value, and expanding your customer base.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/affirm.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/affirm.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-gateway-affirm',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.affirm.com/business',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/woocommerce-gateway-affirm/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.affirm.com/terms',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/woocommerce-gateway-affirm/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=woocommerce-gateway-affirm',
					),
				),
				'tags'        => array( self::TAG_MADE_IN_WOO ),
			),
			self::AFTERPAY          => array(
				'_type'       => self::TYPE_BNPL,
				'title'       => esc_html__( 'Afterpay', 'woocommerce' ),
				'description' => esc_html__( 'Afterpay allows customers to receive products immediately and pay for purchases over four installments, always interest-free.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/afterpay.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/afterpay-clearpay.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'afterpay-gateway-for-woocommerce',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.afterpay.com/for-retailers',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/afterpay/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.afterpay.com/terms-of-service',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/afterpay/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=afterpay',
					),
				),
			),
			self::CLEARPAY          => array(
				'_type'       => self::TYPE_BNPL,
				'title'       => esc_html__( 'Clearpay', 'woocommerce' ),
				'description' => esc_html__( 'Clearpay allows customers to receive products immediately and pay for purchases over four installments, always interest-free.', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/afterpay-clearpay.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'clearpay-gateway-for-woocommerce',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.clearpay.co.uk/en-GB/for-retailers',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/clearpay/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.clearpay.co.uk/terms-of-service',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/clearpay/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=clearpay',
					),
				),
			),
			self::KLARNA            => array(
				'_type'       => self::TYPE_BNPL,
				'title'       => esc_html__( 'Klarna Payments', 'woocommerce' ),
				'description' => esc_html__( 'Choose the payment that you want, pay now, pay later or slice it. No credit card numbers, no passwords, no worries.', 'woocommerce' ),
				'image'       => plugins_url( 'assets/images/onboarding/klarna-black.png', WC_PLUGIN_FILE ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/klarna.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'klarna-payments-for-woocommerce',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.klarna.com/us/business/payment-methods/',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/klarna-payments/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.klarna.com/us/legal/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/klarna-payments/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=klarna-payments',
					),
				),
			),
			self::KLARNA_CHECKOUT   => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Klarna Checkout', 'woocommerce' ),
				'description' => esc_html__( 'A full checkout experience embedded on your site that includes all popular payment methods (Pay Now, Pay Later, Financing, Installments).', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/klarna-checkout.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'klarna-checkout-for-woocommerce',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.klarna.com/us/business/payment-methods/',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/klarna-checkout/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://www.klarna.com/us/legal/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/klarna-checkout/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=klarna-checkout',
					),
				),
			),
			self::HELIOPAY          => array(
				'_type'       => self::TYPE_CRYPTO,
				'title'       => esc_html__( 'Helio Pay', 'woocommerce' ),
				'description' => esc_html__( 'Effortlessly accept cryptocurrency payments in your store.', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/heliopay.png', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'helio',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://www.hel.io/pricing',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/helio-pay/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://info.docs.hel.io/terms-of-service',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/helio-pay/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=helio-pay',
					),
				),
			),
			self::MONEI             => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'MONEI', 'woocommerce' ),
				'description' => esc_html__( 'Accept Cards, Apple Pay, Google Pay, Bizum, PayPal, and many more payment methods in your store.', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/monei.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'monei',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://monei.com/pricing/',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://monei.com/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://monei.com/legal-notice/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://support.monei.com/hc/en-us/articles/360017801677-Get-started-with-MONEI',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://support.monei.com/hc/en-us/requests/new',
					),
				),
			),
			self::COINBASE          => array(
				'_type'  => self::TYPE_CRYPTO,
				'icon'   => plugins_url( 'assets/images/onboarding/icons/coinbase.svg', WC_PLUGIN_FILE ),
				'plugin' => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'coinbase-commerce',
				),
			),
			self::AUTHORIZE_NET     => array(
				'_type'  => self::TYPE_PSP,
				'icon'   => plugins_url( 'assets/images/onboarding/icons/authorize.svg', WC_PLUGIN_FILE ),
				'plugin' => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-gateway-authorize-net-cim',
				),
			),
			self::BILLIE            => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'Billie', 'woocommerce' ),
				'description' => esc_html__( 'Billie is the leading provider of Buy Now, Pay Later payment methods for B2B stores.', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/billie.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'billie-for-woocommerce',
				),
			),
			self::BOLT              => array(
				'_type'  => self::TYPE_PSP,
				'icon'   => plugins_url( 'assets/images/onboarding/icons/bolt.svg', WC_PLUGIN_FILE ),
				'plugin' => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'bolt-checkout-woocommerce',
				),
			),
			self::DEPAY             => array(
				'_type'  => self::TYPE_PSP,
				'icon'   => plugins_url( 'assets/images/onboarding/icons/depay.svg', WC_PLUGIN_FILE ),
				'plugin' => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'depay-payments-for-woocommerce',
				),
			),
			self::ELAVON            => array(
				'_type'  => self::TYPE_PSP,
				'icon'   => plugins_url( 'assets/images/onboarding/icons/elavon.svg', WC_PLUGIN_FILE ),
				'plugin' => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-gateway-converge',
				),
			),
			self::EWAY              => array(
				'_type'  => self::TYPE_PSP,
				'icon'   => plugins_url( 'assets/images/onboarding/icons/eway.svg', WC_PLUGIN_FILE ),
				'plugin' => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-gateway-eway',
				),
			),
			self::FORTISPAY         => array(
				'_type'  => self::TYPE_PSP,
				'icon'   => plugins_url( 'assets/images/onboarding/icons/fortispay.svg', WC_PLUGIN_FILE ),
				'plugin' => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'fortis-for-woocommerce',
				),
			),
			self::GOCARDLESS        => array(
				'_type'       => self::TYPE_PSP,
				'title'       => esc_html__( 'GoCardless', 'woocommerce' ),
				'description' => esc_html__( 'Accept Direct Debit, ACH Pull, and open baking payments.', 'woocommerce' ),
				'icon'        => plugins_url( 'assets/images/onboarding/icons/gocardless.svg', WC_PLUGIN_FILE ),
				'plugin'      => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-gateway-gocardless',
				),
				'links'       => array(
					array(
						'_type' => self::LINK_TYPE_PRICING,
						'url'   => 'https://gocardless.com/pricing/',
					),
					array(
						'_type' => self::LINK_TYPE_ABOUT,
						'url'   => 'https://woocommerce.com/products/gocardless/',
					),
					array(
						'_type' => self::LINK_TYPE_TERMS,
						'url'   => 'https://gocardless.com/legal/',
					),
					array(
						'_type' => self::LINK_TYPE_DOCS,
						'url'   => 'https://woocommerce.com/document/gocardless/',
					),
					array(
						'_type' => self::LINK_TYPE_SUPPORT,
						'url'   => 'https://woocommerce.com/my-account/contact-support/?select=gocardless',
					),
				),
			),
			self::NEXI              => array(
				'_type'  => self::TYPE_PSP,
				'icon'   => plugins_url( 'assets/images/onboarding/icons/nexi.svg', WC_PLUGIN_FILE ),
				'plugin' => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'dibs-easy-for-woocommerce',
				),
			),
			self::PAYPAL_ZETTLE     => array(
				'_type'  => self::TYPE_PSP,
				'icon'   => plugins_url( 'assets/images/onboarding/icons/paypal-zettle.svg', WC_PLUGIN_FILE ),
				'plugin' => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'zettle-pos-integration',
				),
			),
			self::RAPYD             => array(
				'_type'  => self::TYPE_PSP,
				'icon'   => plugins_url( 'assets/images/onboarding/icons/rapyd.svg', WC_PLUGIN_FILE ),
				'plugin' => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'rapyd-payments-plugin-for-woocommerce',
				),
			),
			self::PAYPAL_BRAINTREE  => array(
				'_type'  => self::TYPE_PSP,
				'icon'   => plugins_url( 'assets/images/onboarding/icons/paypal-braintree.svg', WC_PLUGIN_FILE ),
				'plugin' => array(
					'_type' => self::PLUGIN_TYPE_WPORG,
					'slug'  => 'woocommerce-gateway-paypal-powered-by-braintree',
				),
			),
		);

		return $this->extensions_base_details_memo;
	}

	/**
	 * Get the base details for a specific extension.
	 *
	 * @see self::standardize_extension_details() for the supported entries.
	 *
	 * @param string $extension_id The extension ID.
	 *
	 * @return ?array The extension base details.
	 *                Null if the extension is not one we have details for.
	 */
	private function get_extension_base_details( string $extension_id ): ?array {
		$extensions = $this->get_all_extensions_base_details();
		if ( ! isset( $extensions[ $extension_id ] ) ) {
			return null;
		}

		return $extensions[ $extension_id ];
	}

	/**
	 * Standardize the details for an extension.
	 *
	 * Ensures that the details array has all the required fields, and fills in any missing optional fields with defaults.
	 * We also enforce a consistent order for the fields.
	 *
	 * @param array $extension_details The extension details.
	 *
	 * @return array The standardized extension details.
	 */
	private function standardize_extension_details( array $extension_details ): array {
		$standardized = array();

		// Required fields.
		$standardized['id']        = $extension_details['id'];
		$standardized['_priority'] = $extension_details['_priority'];
		$standardized['_type']     = $extension_details['_type'];
		$standardized['plugin']    = $extension_details['plugin'];

		// Optional fields.
		$standardized['title']       = $extension_details['title'] ?? '';
		$standardized['description'] = $extension_details['description'] ?? '';
		$standardized['image']       = $extension_details['image'] ?? '';
		$standardized['icon']        = $extension_details['icon'] ?? '';
		$standardized['links']       = $extension_details['links'] ?? array();
		$standardized['tags']        = $extension_details['tags'] ?? array();
		$standardized['_incentive']  = $extension_details['_incentive'] ?? null;

		return $standardized;
	}

	/**
	 * Based on the WC onboarding profile, determine if the merchant is selling online.
	 *
	 * If the user skipped the profiler (no data points provided), we assume they are selling online.
	 *
	 * @return bool True if the merchant is selling online, false otherwise.
	 */
	private function is_merchant_selling_online(): bool {
		/*
		 * We consider a merchant to be selling online if:
		 * - The profiler was skipped (no data points provided).
		 *   OR
		 * - The merchant answered 'Which one of these best describes you?' with 'I’m already selling' AND:
		 *   - Didn't answer to the 'Are you selling online?' question.
		 *      OR
		 *   - Answered the 'Are you selling online?' question with either:
		 *     - 'Yes, I’m selling online'.
		 *        OR
		 *     - 'I’m selling both online and offline'.
		 *
		 * @see plugins/woocommerce/client/admin/client/core-profiler/pages/UserProfile.tsx for the values.
		 */
		$onboarding_profile = get_option( OnboardingProfile::DATA_OPTION, array() );
		if (
			! isset( $onboarding_profile['business_choice'] ) ||
			(
				'im_already_selling' === $onboarding_profile['business_choice'] &&
				(
					! isset( $onboarding_profile['selling_online_answer'] ) ||
					(
						'yes_im_selling_online' === $onboarding_profile['selling_online_answer'] ||
						'im_selling_both_online_and_offline' === $onboarding_profile['selling_online_answer']
					)
				)
			)
		) {
			return false;
		}

		return true;
	}

	/**
	 * Based on the WC onboarding profile, determine if the merchant is selling offline.
	 *
	 * If the user skipped the profiler (no data points provided), we assume they are NOT selling offline.
	 *
	 * @return bool True if the merchant is selling offline, false otherwise.
	 */
	private function is_merchant_selling_offline(): bool {
		/*
		 * We consider a merchant to be selling offline if:
		 * - The profiler was NOT skipped (data points provided).
		 *   AND
		 * - The merchant answered 'Which one of these best describes you?' with 'I’m already selling' AND:
		 *   - Answered the 'Are you selling online?' question with either:
		 *     - 'No, I’m selling offline'.
		 *        OR
		 *     - 'I’m selling both online and offline'.
		 *
		 * @see plugins/woocommerce/client/admin/client/core-profiler/pages/UserProfile.tsx for the values.
		 */
		$onboarding_profile = get_option( OnboardingProfile::DATA_OPTION, array() );
		if (
			isset( $onboarding_profile['business_choice'] ) &&
			(
				'im_already_selling' === $onboarding_profile['business_choice'] &&
				(
					isset( $onboarding_profile['selling_online_answer'] ) &&
					(
						'no_im_selling_offline' === $onboarding_profile['selling_online_answer'] ||
						'im_selling_both_online_and_offline' === $onboarding_profile['selling_online_answer']
					)
				)
			)
		) {
			return true;
		}

		return false;
	}
}
