/**
 * External dependencies
 */
import { TableRow } from '@woocommerce/components/build-types/table/types';
import { gmdateI18n } from '@wordpress/date';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, plugins } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { Subscription } from '../../types';
import ConnectButton from '../actions/connect-button';
import Install from '../actions/install';
import RenewButton from '../actions/renew-button';
import SubscribeButton from '../actions/subscribe-button';
import Update from '../actions/update';
import ActionsDropdownMenu from './actions-dropdown-menu';
import StatusPopover from './status-popover';
import Version from './version';

// TODO: Add explanations.
function getStatus( subscription: Subscription ): {
	text: string;
	warning: boolean;
	explanation?: string;
} {
	if ( subscription.active ) {
		return {
			text: __( 'Active', 'woocommerce' ),
			warning: false,
		};
	}

	if ( subscription.product_key === '' ) {
		return {
			text: __( 'Not found', 'woocommerce' ),
			warning: true,
			explanation: __( 'This subscription is not found.', 'woocommerce' ),
		};
	}

	if ( subscription.expired ) {
		return {
			text: __( 'Expired', 'woocommerce' ),
			warning: true,
			explanation: __(
				'To get updates and support for this extension, you need to purchase a new subscription, or else share or transfer a subscription for this extension from another account.',
				'woocommerce'
			),
		};
	}

	return {
		text: __( 'Inactive', 'woocommerce' ),
		warning: true,
		explanation: __(
			'To receive updates and support for this extension, you need to purchase a new subscription or consolidate your extensions to one connected account by sharing or transferring this extension to this connected account.',
			'woocommerce'
		),
	};
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

export function productName( subscription: Subscription ): TableRow {
	// This is the fallback icon element with products without an icon.
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
				className="woocommerce-marketplace__my-subscriptions__product-icon"
			/>
		);
	}

	const displayElement = (
		<div className="woocommerce-marketplace__my-subscriptions__product">
			{ iconElement }
			<span className="woocommerce-marketplace__my-subscriptions__product-name">
				{ subscription.product_name }
			</span>
		</div>
	);

	return {
		display: displayElement,
		value: subscription.product_name,
	};
}

export function status( subscription: Subscription ): TableRow {
	const subscriptionStatus = getStatus( subscription );

	let statusElement = <>{ subscriptionStatus.text }</>;

	if ( subscriptionStatus.warning ) {
		statusElement = (
			<StatusPopover
				text={ subscriptionStatus.text }
				explanation={ subscriptionStatus.explanation ?? '' }
			/>
		);
	}

	const displayElement = (
		<span className="woocommerce-marketplace__my-subscriptions__status">
			{ statusElement }
		</span>
	);

	return {
		display: displayElement,
		value: subscriptionStatus.text,
	};
}

export function expiry( subscription: Subscription ): TableRow {
	const expiryDate = subscription.expires;

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

				<ActionsDropdownMenu />
			</div>
		),
	};
}
