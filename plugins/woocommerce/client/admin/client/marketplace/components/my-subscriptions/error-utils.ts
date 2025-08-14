/**
 * External dependencies
 */
import { __, sprintf, _n } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import type { Options as NoticeOptions } from 'wordpress__notices';

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

type StoreAction = NonNullable< NoticeOptions[ 'actions' ] >[ number ];

function trackConnectErrorActionClicked(
	action: 'manage_subscriptions' | 'contact_support' | 'try_again',
	errorCode: string
) {
	recordEvent( 'marketplace_product_connect_error_action_clicked', {
		action,
		error_code: errorCode,
	} );
}

function getConnectionErrorMessage(
	error: ConnectError,
	baseMessage: string
): string {
	const code = error?.data?.code || '';

	if ( code === 'maxed_out' ) {
		const sites = error?.data?.data?.sites_list || [];
		const domainCount = Number(
			error?.data?.data?.total_domains ?? sites.length
		);

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

			const others = domainCount - 2;
			return (
				baseMessage +
				' ' +
				sprintf(
					// translators: %1$s and %2$s are domain names, %3$d is a number of additional sites.
					_n(
						"This subscription is maxed out as it's connected to %1$s, %2$s, and %3$d other site.",
						"This subscription is maxed out as it's connected to %1$s, %2$s, and %3$d other sites.",
						others,
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

	if ( code === 'invalid_product' ) {
		return (
			baseMessage +
			' ' +
			__(
				'We are unable to activate the subscription at this time. Please try again later.',
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

function getConnectionErrorAction( error: ConnectError ): StoreAction | null {
	const code = error?.data?.code || '';
	if ( code === 'maxed_out' ) {
		return {
			label: __( 'Manage subscriptions', 'woocommerce' ),
			onClick: () => {
				trackConnectErrorActionClicked( 'manage_subscriptions', code );
				window.location.assign( MARKETPLACE_RENEW_SUBSCRIPTON_PATH );
			},
		};
	}

	if ( code === 'invalid_product_key' ) {
		return {
			label: __( 'Contact support', 'woocommerce' ),
			onClick: () => {
				trackConnectErrorActionClicked( 'contact_support', code );
				window.location.assign( MARKETPLACE_SUPPORT_PATH );
			},
		};
	}

	return null;
}

export {
	getConnectionErrorMessage,
	getConnectionErrorAction,
	trackConnectErrorActionClicked,
};

export type { ConnectError };
