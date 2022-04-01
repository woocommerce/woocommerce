/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder, Button } from 'wordpress-components';
import { useExpressPaymentMethods } from '@woocommerce/base-context/hooks';
import { Icon, payment } from '@wordpress/icons';
import { ADMIN_URL } from '@woocommerce/settings';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';

/**
 * Renders a placeholder in the editor.
 */
const NoExpressPaymentMethodsPlaceholder = () => {
	return (
		<Placeholder
			icon={ <Icon icon={ payment } /> }
			label={ __( 'Express Checkout', 'woo-gutenberg-products-block' ) }
			className="wp-block-woocommerce-checkout-express-payment-block-placeholder"
		>
			<span className="wp-block-woocommerce-checkout-express-payment-block-placeholder__description">
				{ __(
					"Your store doesn't have any Payment Methods that support the Express Checkout Block. If they are added, they will be shown here.",
					'woo-gutenberg-products-block'
				) }
			</span>
			<Button
				isPrimary
				href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=checkout` }
				target="_blank"
				rel="noopener noreferrer"
				className="wp-block-woocommerce-checkout-express-payment-block-placeholder__button"
			>
				{ __(
					'Configure Payment Methods',
					'woo-gutenberg-products-block'
				) }
			</Button>
		</Placeholder>
	);
};

export const Edit = ( {
	attributes,
}: {
	attributes: { className: string };
} ): JSX.Element | null => {
	const { paymentMethods, isInitialized } = useExpressPaymentMethods();
	const hasExpressPaymentMethods = Object.keys( paymentMethods ).length > 0;
	const blockProps = useBlockProps( {
		className: classnames( {
			'wp-block-woocommerce-cart-express-payment-block--has-express-payment-methods': hasExpressPaymentMethods,
		} ),
	} );
	const { className } = attributes;

	if ( ! isInitialized ) {
		return null;
	}

	return (
		<div { ...blockProps }>
			{ hasExpressPaymentMethods ? (
				<Block className={ className } />
			) : (
				<NoExpressPaymentMethodsPlaceholder />
			) }
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
