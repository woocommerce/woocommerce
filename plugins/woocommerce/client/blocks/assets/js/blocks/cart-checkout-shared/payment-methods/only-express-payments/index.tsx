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
const OnlyExpressPayments = () => {
	return (
		<NoticeBanner
			isDismissible={ false }
			className="wc-block-checkout__only-express-payments-notice"
			status="info"
		>
			{ __(
				'Only express payment methods are available for this order. Please select one to continue.',
				'woocommerce'
			) }
		</NoticeBanner>
	);
};

export default OnlyExpressPayments;
