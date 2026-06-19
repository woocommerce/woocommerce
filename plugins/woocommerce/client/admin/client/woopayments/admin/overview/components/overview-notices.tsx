/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const getQueryParam = ( key: string ) =>
	new URLSearchParams( window.location.search ).get( key ) === '1';

export const OverviewNotices = () => {
	const notices = [
		getQueryParam( 'wcpay-login-error' ) && {
			className: 'wcpay-login-error',
			message: __(
				'There was a problem redirecting you to the account dashboard. Please try again.',
				'woocommerce'
			),
		},
		getQueryParam( 'wcpay-loan-offer-error' ) && {
			className: 'wcpay-loan-offer-error',
			message: __(
				'There was a problem redirecting you to the loan offer. Please check that it is not expired and try again.',
				'woocommerce'
			),
		},
		getQueryParam( 'wcpay-server-link-error' ) && {
			className: 'wcpay-server-link-error',
			message: __(
				'There was a problem redirecting you to the requested link. Please check that it is valid and try again.',
				'woocommerce'
			),
		},
		getQueryParam( 'wcpay-reset-account-error' ) && {
			className: 'wcpay-reset-account-error',
			message: __(
				'There was a problem resetting your account. Please wait a few seconds and try again.',
				'woocommerce'
			),
		},
	].filter( Boolean ) as Array< { className: string; message: string } >;

	if ( notices.length === 0 ) {
		return null;
	}

	return (
		<div className="woocommerce-woopayments-overview-query-notices">
			{ notices.map( ( notice ) => (
				<Notice
					key={ notice.className }
					className={ notice.className }
					status="error"
					isDismissible={ false }
				>
					{ notice.message }
				</Notice>
			) ) }
		</div>
	);
};
