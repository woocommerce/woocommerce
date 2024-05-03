/**
 * External dependencies
 */
import { TableRow } from '@woocommerce/components/build-types/table/types';
import { gmdateI18n } from '@wordpress/date';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, plugins } from '@wordpress/icons';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { StatusLevel, Subscription } from '../../types';
import ConnectButton from '../actions/connect-button';
import Install from '../actions/install';
import RenewButton from '../actions/renew-button';
import SubscribeButton from '../actions/subscribe-button';
import Update from '../actions/update';
import StatusPopover from './status-popover';
import ActionsDropdownMenu from './actions-dropdown-menu';
import Version from './version';
import {
	appendURLParams,
	renewUrl,
	subscribeUrl,
} from '../../../../utils/functions';
import { MARKETPLACE_COLLABORATION_PATH } from '../../../constants';

type StatusBadge = {
	text: string;
	level: StatusLevel;
	explanation?: string | JSX.Element;
};

function getStatusBadge( subscription: Subscription ): StatusBadge | false {
	if ( subscription.product_key === '' ) {
		/**
		 * If there is no subscription, we don't need to check for the expiry.
		 */
		return {
			text: __( 'No subscription', 'woocommerce' ),
			level: StatusLevel.Error,
			explanation: createInterpolateElement(
				__(
					'To receive updates and support, please <purchase>purchase</purchase> a subscription or use a subscription from another account by <sharing>sharing</sharing> or <transferring>transferring</transferring>.',
					'woocommerce'
				),
				{
					purchase: (
						<a
							href={ subscribeUrl( subscription ) }
							rel="nofollow noopener noreferrer"
						>
							renew
						</a>
					),
					sharing: (
						<a
							href={ MARKETPLACE_COLLABORATION_PATH }
							rel="nofollow noopener noreferrer"
						>
							sharing
						</a>
					),
					transferring: (
						<a
							href={ MARKETPLACE_COLLABORATION_PATH }
							rel="nofollow noopener noreferrer"
						>
							sharing
						</a>
					),
				}
			),
		};
	}
	if ( subscription.local.installed && ! subscription.active ) {
		return {
			text: __( 'Not connected', 'woocommerce' ),
			level: StatusLevel.Warning,
			explanation: __(
				'To receive updates and support, please connect your subscription to this store.',
				'woocommerce'
			),
		};
	}
	if ( subscription.expired ) {
		return {
			text: __( 'Expired', 'woocommerce' ),
			level: StatusLevel.Error,
			explanation: createInterpolateElement(
				__(
					'To receive updates and support, please <renew>renew</renew> this subscription or use a subscription from another account by <sharing>sharing</sharing> or <transferring>transferring</transferring>.',
					'woocommerce'
				),
				{
					renew: (
						<a
							href={ renewUrl( subscription ) }
							rel="nofollow noopener noreferrer"
						>
							renew
						</a>
					),
					sharing: (
						<a
							href={ MARKETPLACE_COLLABORATION_PATH }
							rel="nofollow noopener noreferrer"
						>
							sharing
						</a>
					),
					transferring: (
						<a
							href={ MARKETPLACE_COLLABORATION_PATH }
							rel="nofollow noopener noreferrer"
						>
							sharing
						</a>
					),
				}
			),
		};
	}
	return false;
}

function getVersion( subscription: Subscription ): string | JSX.Element {
	if ( subscription.local.version === subscription.version ) {
		return <Version span={ subscription.local.version } />;
	}

	if ( subscription.local.version && subscription.version ) {
		return <Update subscription={ subscription } />;
	}

	if ( subscription.version ) {
		return <Version span={ subscription.version } />;
	}

	if ( subscription.local.version ) {
		return <Version span={ subscription.local.version } />;
	}

	return '';
}

function appendUTMParams( url: string ) {
	return appendURLParams( url, [
		[ 'utm_source', 'subscriptionsscreen' ],
		[ 'utm_medium', 'product' ],
		[ 'utm_campaign', 'wcaddons' ],
		[ 'utm_content', 'product-name' ],
	] );
}

export function nameAndStatus( subscription: Subscription ): TableRow {
	// This is the fallback icon element with products without
	let iconElement = <Icon icon={ plugins } size={ 40 } />;

	// If the product has an icon, use that instead.
	if ( subscription.product_icon ) {
		iconElement = (
			<img
				src={ subscription.product_icon }
				alt={ sprintf(
					/* translators: %s is the product name. */
					__( '%s icon', 'woocommerce' ),
					subscription.product_name
				) }
			/>
		);
	}

	const statusBadge = getStatusBadge( subscription );

	const displayElement = (
		<div className="woocommerce-marketplace__my-subscriptions__product">
			<a
				href={ appendUTMParams( subscription.product_url ) }
				target="_blank"
				rel="noreferrer"
			>
				<span className="woocommerce-marketplace__my-subscriptions__product-icon">
					{ iconElement }
				</span>
			</a>
			<a
				href={ appendUTMParams( subscription.product_url ) }
				className="woocommerce-marketplace__my-subscriptions__product-name"
				target="_blank"
				rel="noreferrer"
			>
				{ subscription.product_name }
			</a>
			<span className="woocommerce-marketplace__my-subscriptions__product-statuses">
				{ statusBadge && (
					<StatusPopover
						text={ statusBadge.text }
						level={ statusBadge.level }
						explanation={ statusBadge.explanation ?? '' }
					/>
				) }
			</span>
		</div>
	);

	return {
		display: displayElement,
		value: subscription.product_name,
	};
}

export function expiry( subscription: Subscription ): TableRow {
	const expiryDate = subscription.expires;

	if (
		subscription.local.installed === true &&
		subscription.product_key === ''
	) {
		return {
			display: '',
			value: '',
		};
	}

	let expiryDateElement = __( 'Never expires', 'woocommerce' );

	if ( expiryDate ) {
		expiryDateElement = gmdateI18n(
			'j M, Y',
			new Date( expiryDate * 1000 )
		);
	}

	const displayElement = (
		<span className="woocommerce-marketplace__my-subscriptions__expiry-date">
			{ expiryDateElement }
		</span>
	);

	return {
		display: displayElement,
		value: expiryDate,
	};
}

export function autoRenew( subscription: Subscription ): TableRow {
	return {
		display: subscription.autorenew
			? __( 'On', 'woocommerce' )
			: __( 'Off', 'woocommerce' ),
		value: subscription.autorenew,
	};
}

export function version( subscription: Subscription ): TableRow {
	return {
		display: getVersion( subscription ),
	};
}

export function actions( subscription: Subscription ): TableRow {
	let actionButton = null;
	if ( subscription.product_key === '' ) {
		actionButton = <SubscribeButton subscription={ subscription } />;
	} else if ( subscription.expired ) {
		actionButton = <RenewButton subscription={ subscription } />;
	} else if (
		subscription.local.installed === false &&
		subscription.subscription_installed === false
	) {
		actionButton = <Install subscription={ subscription } />;
	} else if (
		subscription.active === false &&
		subscription.subscription_available === true
	) {
		actionButton = (
			<ConnectButton subscription={ subscription } variant="link" />
		);
	}
	return {
		display: (
			<div className="woocommerce-marketplace__my-subscriptions__actions">
				{ actionButton }

				<ActionsDropdownMenu subscription={ subscription } />
			</div>
		),
	};
}
