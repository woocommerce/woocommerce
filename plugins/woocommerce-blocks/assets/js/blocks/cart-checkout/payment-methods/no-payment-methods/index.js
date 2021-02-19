/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder, Button, Notice } from 'wordpress-components';
import { Icon, card } from '@woocommerce/icons';
import { ADMIN_URL } from '@woocommerce/settings';
import { useEditorContext } from '@woocommerce/base-context';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Render content when no payment methods are found depending on context.
 */
const NoPaymentMethods = () => {
	const { isEditor } = useEditorContext();

	return isEditor ? (
		<NoPaymentMethodsPlaceholder />
	) : (
		<NoPaymentMethodsNotice />
	);
};

/**
 * Renders a placeholder in the editor.
 */
const NoPaymentMethodsPlaceholder = () => {
	return (
		<Placeholder
			icon={ <Icon srcElement={ card } /> }
			label={ __( 'Payment methods', 'woo-gutenberg-products-block' ) }
			className="wc-block-checkout__no-payment-methods-placeholder"
		>
			<span className="wc-block-checkout__no-payment-methods-placeholder-description">
				{ __(
					'Your store does not have any payment methods configured that support the checkout block. Once you have configured a compatible payment method (e.g. Stripe) it will be shown here.',
					'woo-gutenberg-products-block'
				) }
			</span>
			<Button
				isSecondary
				href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=checkout` }
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __(
					'Configure Payment Methods',
					'woo-gutenberg-products-block'
				) }
			</Button>
		</Placeholder>
	);
};

/**
 * Renders a notice on the frontend.
 */
const NoPaymentMethodsNotice = () => {
	return (
		<Notice
			isDismissible={ false }
			className={ classnames(
				'wc-block-checkout__no-payment-methods-notice',
				'woocommerce-message',
				'woocommerce-error'
			) }
		>
			{ __(
				'There are no payment methods available. This may be an error on our side. Please contact us if you need any help placing your order.',
				'woo-gutenberg-products-block'
			) }
		</Notice>
	);
};

export default NoPaymentMethods;
