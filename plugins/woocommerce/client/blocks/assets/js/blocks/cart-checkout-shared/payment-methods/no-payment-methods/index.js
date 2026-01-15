/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import NoticeBanner from '@woocommerce/base-components/notice-banner';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Render content when no payment methods are found depending on context.
 */
const NoPaymentMethods = () => {
	return (
		<NoticeBanner
			isDismissible={ false }
			className="wc-block-checkout__no-payment-methods-notice"
			status="error"
		>
			{ __(
				'There are no payment methods available. Please contact us for help placing your order.',
				'woocommerce'
			) }
		</NoticeBanner>
	);
};

export default NoPaymentMethods;
