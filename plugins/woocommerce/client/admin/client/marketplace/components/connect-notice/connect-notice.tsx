/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { appendURLParams, connectUrl } from '~/marketplace/utils/functions';
import Notice from '~/marketplace/components/notice/notice';
import { getAdminSetting } from '~/utils/admin-settings';

export default function ConnectNotice(): JSX.Element | null {
	const localStorageKey = 'woo-connect-notice-marketplace-dismissed';
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const noticeType: 'none' | 'short' | 'long' =
		wccomSettings?.woocomConnectNoticeType || 'none';

	const defaultStoreName = __( 'Your store', 'woocommerce' );
	const storeName: string = wccomSettings?.storeName || defaultStoreName;

	const formattedStoreName =
		storeName !== defaultStoreName
			? `<strong>${ storeName }</strong>`
			: storeName;

	if ( noticeType === 'none' ) {
		return null;
	}

	const lastDismissed = localStorage.getItem( localStorageKey );
	const parsedDismissedDate = new Date( lastDismissed || '' );
	const aMonthAgo = new Date();
	aMonthAgo.setMonth( aMonthAgo.getMonth() - 1 );

	// try to re-show the notice if it was dismissed more than a month ago.
	// removing these 2 local storage items will make the notice reappear.
	if (
		lastDismissed === null ||
		isNaN( parsedDismissedDate.valueOf() ) ||
		aMonthAgo.valueOf() > parsedDismissedDate.valueOf()
	) {
		localStorage.removeItem(
			'wc-marketplaceNoticeClosed-woo-connect-notice'
		);
		localStorage.removeItem( localStorageKey );
	}

	const noticeText = {
		long: sprintf(
			/* translators: %s: store name set from the store settings, if not set, it will be "Your store" */
			__(
				'%s might be at risk because it’s running outdated WooCommerce plugins and is not yet connected to a WooCommerce.com account. Please complete the connection to get updates and streamlined support.',
				'woocommerce'
			),
			formattedStoreName
		),
		short: sprintf(
			/* translators: %s: store name set from the store settings, if not set, it will be "Your store" */
			__(
				'%s is not yet connected to a WooCommerce.com account. Please complete the connection to get updates and streamlined support.',
				'woocommerce'
			),
			formattedStoreName
		),
	};

	const description = noticeText[ noticeType ];

	const handleClick = () => {
		recordEvent( 'woo_connect_notice_in_marketplace_clicked' );
		return true;
	};

	const handleLearnMoreClick = () => {
		recordEvent( 'woo_connect_notice_learn_more_clicked' );
		return true;
	};

	const handleClose = () => {
		localStorage.setItem( localStorageKey, new Date().toString() );
		recordEvent( 'woo_connect_notice_in_marketplace_dismissed' );
	};

	const handleLoad = () => {
		recordEvent( 'woo_connect_notice_in_marketplace_shown' );
	};

	const connectUrlWithUTM = appendURLParams( connectUrl(), [
		[ 'utm_source', 'pu' ],
		[ 'utm_campaign', 'pu_in_apps_screen_connect' ],
	] );

	return (
		<Notice
			id="woo-connect-notice"
			description={ description }
			isDismissible={ true }
			variant="warning"
			className="woocommerce-marketplace__connect-notice"
			onClose={ handleClose }
			onLoad={ handleLoad }
		>
			<Button
				href={ connectUrlWithUTM }
				variant="primary"
				onClick={ handleClick }
			>
				{ __( 'Connect your store', 'woocommerce' ) }
			</Button>
			<Button
				href="https://woocommerce.com/document/managing-woocommerce-com-subscriptions/#connect-your-site-woocommercecom-account"
				target="_blank"
				variant="tertiary"
				onClick={ handleLearnMoreClick }
			>
				{ __( 'Learn more', 'woocommerce' ) }
			</Button>
		</Notice>
	);
}
