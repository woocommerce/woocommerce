/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { PRIVACY_URL, TERMS_URL } from '@woocommerce/block-settings';

const termsPageLink = TERMS_URL
	? `<a href="${ TERMS_URL }">${ __(
			'Terms and Conditions',
			'woo-gutenberg-product-blocks'
	  ) }</a>`
	: __( 'Terms and Conditions', 'woo-gutenberg-product-blocks' );

const privacyPageLink = PRIVACY_URL
	? `<a href="${ PRIVACY_URL }">${ __(
			'Privacy Policy',
			'woo-gutenberg-product-blocks'
	  ) }</a>`
	: __( 'Privacy Policy', 'woo-gutenberg-product-blocks' );

export const termsConsentDefaultText = sprintf(
	/* translators: %1$s terms page link, %2$s privacy page link. */
	__(
		'By proceeding with your purchase you agree to our %1$s and %2$s',
		'woo-gutenberg-product-blocks'
	),
	termsPageLink,
	privacyPageLink
);

export const termsCheckboxDefaultText = sprintf(
	/* translators: %1$s terms page link, %2$s privacy page link. */
	__(
		'You must accept our %1$s and %2$s to continue with your purchase.',
		'woo-gutenberg-product-blocks'
	),
	termsPageLink,
	privacyPageLink
);
