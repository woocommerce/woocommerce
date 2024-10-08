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
import { StatusLevel, Subscription, MySubscriptionsTable } from '../../types';
import ConnectButton from '../actions/connect-button';
import Install from '../actions/install';
import RenewButton from '../actions/renew-button';
import AutoRenewButton from '../actions/auto-renew-button';
import SubscribeButton from '../actions/subscribe-button';
import Update from '../actions/update';
import StatusPopover from './status-popover';
import ActionsDropdownMenu from './actions-dropdown-menu';
import Version from './version';
import {
	appendURLParams,
	renewUrl,
	subscribeUrl,
	enableAutorenewalUrl,
} from '../../../../utils/functions';
import {
	MARKETPLACE_COLLABORATION_PATH,
	MARKETPLACE_SHARING_PATH,
} from '../../../constants';
import { getAdminSetting } from '../../../../../utils/admin-settings';

type StatusBadge = {
	text: string;
	level: StatusLevel;
	explanation?: string | JSX.Element;
};

function getStatusBadge(
	subscription: Subscription,
	table: MySubscriptionsTable
): StatusBadge | false {
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
							href={ MARKETPLACE_SHARING_PATH }
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
							href={ MARKETPLACE_SHARING_PATH }
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

	if ( subscription.expiring && ! subscription.autorenew ) {
		return {
			text: __( 'Expires soon', 'woocommerce' ),
			level: StatusLevel.Error,
			explanation: createInterpolateElement(
				__(
					'To receive updates and support, please <renew>renew</renew> this subscription before it expires or use a subscription from another account by <sharing>sharing</sharing> or <transferring>transferring</transferring>.',
					'woocommerce'
				),
				{
					renew: (
						<a
							href={ enableAutorenewalUrl( subscription ) }
							rel="nofollow noopener noreferrer"
						>
							renew
						</a>
					),
					sharing: (
						<a
							href={ MARKETPLACE_SHARING_PATH }
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

	if (
		table === 'installed' &&
		subscription.local.installed &&
		! subscription.active
	) {
		return {
			text: __( 'Not connected', 'woocommerce' ),
			level: StatusLevel.Warning,
			explanation: __(
				'To receive updates and support, please connect your subscription to this store.',
				'woocommerce'
			),
		};
	}

	return false;
}

function getVersion(
	subscription: Subscription,
	table: MySubscriptionsTable
): string | JSX.Element {
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );

	if ( subscription.local.version === subscription.version ) {
		return <Version span={ subscription.local.version } />;
	}

	if (
		subscription.local.version &&
		subscription.version &&
		table === 'installed'
	) {
		return (
			<Update
				subscription={ subscription }
				wooUpdateManagerActive={ wccomSettings?.wooUpdateManagerActive }
			/>
		);
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
				{ subscription.is_shared && (
					<StatusPopover
						text={ __( 'Shared with you', 'woocommerce' ) }
						level={ StatusLevel.Info }
						explanation={ createInterpolateElement(
							sprintf(
								/* translators: %s is the email address of the user who shared the subscription. */
								__(
									'This subscription was shared by <email>%s</email>. <link>Learn more</link>.',
									'woocommerce'
								),
								subscription.owner_email
							),
							{
								email: (
									<strong
										style={ { overflowWrap: 'anywhere' } }
									>
										email
									</strong>
								),
								link: (
									<a
										href={ MARKETPLACE_SHARING_PATH }
										rel="nofollow noopener noreferrer"
									>
										Learn more
									</a>
								),
							}
						) }
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

export function subscriptionStatus(
	subscription: Subscription,
	table: MySubscriptionsTable
): TableRow {
	function getStatus() {
		const statusBadge = getStatusBadge( subscription, table );
		if ( statusBadge ) {
			return (
				<StatusPopover
					text={ statusBadge.text }
					level={ statusBadge.level }
					explanation={ statusBadge.explanation ?? '' }
					explanationOnHover
				/>
			);
		}

		let status;
		if ( subscription.lifetime ) {
			status = __( 'Lifetime', 'woocommerce' );
		} else if ( subscription.autorenew ) {
			status = __( 'Active', 'woocommerce' );
		} else {
			status = __( 'Cancelled', 'woocommerce' );
		}

		return status;
	}
	return {
		display: getStatus(),
	};
}

export function version(
	subscription: Subscription,
	table: MySubscriptionsTable
): TableRow {
	return {
		display: getVersion( subscription, table ),
	};
}

export function actions( subscription: Subscription ): TableRow {
	let actionButton = null;
	if ( subscription.product_key === '' ) {
		actionButton = <SubscribeButton subscription={ subscription } />;
	} else if ( subscription.expired && ! subscription.lifetime ) {
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
	} else if ( ! subscription.autorenew && ! subscription.lifetime ) {
		actionButton = <AutoRenewButton subscription={ subscription } />;
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
