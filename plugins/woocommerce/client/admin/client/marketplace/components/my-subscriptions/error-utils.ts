/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	MARKETPLACE_RENEW_SUBSCRIPTON_PATH,
	MARKETPLACE_SUPPORT_PATH,
} from '../constants';
import { ERROR_CODES_WITH_MESSAGES } from './constants';

type ConnectError = {
	data?: {
		message?: string;
		code?: string;
		data?: {
			sites_list?: string[];
			total_domains?: number;
		};
	};
};

type ConnectionErrorAction = {
	label: string;
	url: string;
} | null;

function getConnectionErrorMessage(
	error: ConnectError,
	baseMessage: string
): string {
	const code = error?.data?.code || '';

	if ( code === 'maxed_out' ) {
		const sites = error?.data?.data?.sites_list || [];
		const domainCount = error?.data?.data?.total_domains ?? sites.length;

		if ( domainCount >= 2 ) {
			const first = sites[ 0 ] || '';
			const second = sites[ 1 ] || '';
			if ( domainCount === 2 ) {
				return (
					baseMessage +
					' ' +
					sprintf(
						// translators: %1$s and %2$s are domain names.
						__(
							"This subscription is maxed out as it's connected to %1$s and %2$s.",
							'woocommerce'
						),
						first,
						second
					)
				);
			}

			const others = Math.max( domainCount - 2, 1 );
			return (
				baseMessage +
				' ' +
				sprintf(
					// translators: %1$s and %2$s are domain names, %3$d is a number of additional sites.
					__(
						"This subscription is maxed out as it's connected to %1$s, %2$s, and %3$d other sites.",
						'woocommerce'
					),
					first,
					second,
					others
				)
			);
		}
	}

	if ( code === 'invalid_product_key' ) {
		return (
			baseMessage +
			' ' +
			__(
				'The product key is invalid. Please contact support for assistance.',
				'woocommerce'
			)
		);
	}

	if (
		ERROR_CODES_WITH_MESSAGES.includes(
			code as ( typeof ERROR_CODES_WITH_MESSAGES )[ number ]
		)
	) {
		const serverMessage = error?.data?.message || '';
		return serverMessage ? baseMessage + ' ' + serverMessage : baseMessage;
	}

	return baseMessage;
}

function getConnectionErrorAction(
	error: ConnectError
): ConnectionErrorAction {
	const code = error?.data?.code || '';
	if ( code === 'maxed_out' ) {
		return {
			label: __( 'Manage subscriptions', 'woocommerce' ),
			url: MARKETPLACE_RENEW_SUBSCRIPTON_PATH,
		};
	}

	if ( code === 'invalid_product_key' ) {
		return {
			label: __( 'Contact support', 'woocommerce' ),
			url: MARKETPLACE_SUPPORT_PATH,
		};
	}

	return null;
}

export { getConnectionErrorMessage, getConnectionErrorAction };
