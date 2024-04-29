/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { connectUrl } from '~/marketplace/utils/functions';
import Notice from '~/marketplace/components/notice/notice';
import { getAdminSetting } from '~/utils/admin-settings';

export default function ConnectNotice(): JSX.Element | null {
	const localStorageKey = 'woo-connect-notice-marketplace-dismissed';
	const wccomSettings = getAdminSetting( 'wccomHelper', {} );
	const noticeType = wccomSettings?.woocomConnectNoticeType || 'none';

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

	let description = '';

	if ( noticeType === 'long' ) {
		description = description.concat(
			__(
				'Your store might be at risk as you are running old versions of WooCommerce plugins.',
				'woocommerce'
			)
		);

		description = description.concat( ' ' );
	}

	description = description.concat(
		__(
			'<strong>Connect your store to WooCommerce.com</strong> to get updates and streamlined support for your subscriptions.',
			'woocommerce'
		)
	);

	const handleClick = () => {
		recordEvent( 'woo_connect_notice_in_marketplace_clicked' );
		return true;
	};

	const handleClose = () => {
		localStorage.setItem( localStorageKey, new Date().toString() );
		recordEvent( 'woo_connect_notice_in_marketplace_dismissed' );
	};

	const handleLoad = () => {
		recordEvent( 'woo_connect_notice_in_marketplace_shown' );
	};

	return (
		<Notice
			id="woo-connect-notice"
			description={ description }
			isDismissible={ true }
			variant="error"
			onClose={ handleClose }
			onLoad={ handleLoad }
		>
			<Button
				href={ connectUrl() }
				variant="secondary"
				onClick={ handleClick }
			>
				{ __( 'Connect', 'woocommerce' ) }
			</Button>
		</Notice>
	);
}
