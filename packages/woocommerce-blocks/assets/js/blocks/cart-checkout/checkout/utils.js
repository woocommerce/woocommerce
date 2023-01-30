/**
 * External dependencies
 */
import { LOGIN_URL } from '@woocommerce/block-settings';

export const LOGIN_TO_CHECKOUT_URL = `${ LOGIN_URL }?redirect_to=${ encodeURIComponent(
	window.location.href
) }`;
