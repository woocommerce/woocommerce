/**
 * External dependencies
 */
import { WooFooterItem } from '@woocommerce/admin-layout';
import { __ } from '@wordpress/i18n';
import { check, commentContent, lock } from '@wordpress/icons';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './footer.scss';
import IconWithText from '../icon-with-text/icon-with-text';
import WooIcon from '../../assets/images/woo-icon.svg';

const refundPolicyTitle = createInterpolateElement(
	__( '30 day <a>money back guarantee</a>', 'woocommerce' ),
	{
		// eslint-disable-next-line jsx-a11y/anchor-has-content
		a: <a href="https://woocommerce.com/refund-policy/" />,
	}
);

const supportTitle = createInterpolateElement(
	__( '<a>Support</a> teams across the world', 'woocommerce' ),
	{
		// eslint-disable-next-line jsx-a11y/anchor-has-content
		a: <a href="https://woocommerce.com/docs/" />,
	}
);

const paymentTitle = createInterpolateElement(
	__( '<a>Safe & Secure</a> online payment', 'woocommerce' ),
	{
		// eslint-disable-next-line jsx-a11y/anchor-has-content
		a: <a href="https://woocommerce.com/products/woocommerce-payments/" />,
	}
);

function FooterContent(): JSX.Element {
	return (
		<div className="woocommerce-marketplace__footer">
			<h2 className="woocommerce-marketplace__footer-title">
				{ __(
					'Grow your business with hundreds of solutions for your store.',
					'woocommerce'
				) }
			</h2>
			<div className="woocommerce-marketplace__footer-columns">
				<IconWithText
					icon={ check }
					title={ refundPolicyTitle }
					description={ __(
						'For extensions and themes purchased from our Marketplace, we offer a full refund within 30 days of your date of purchase.',
						'woocommerce'
					) }
				/>
				<IconWithText
					icon={ commentContent }
					title={ supportTitle }
					description={ __(
						'We have happiness engineers around round the globe to help you at any given time.',
						'woocommerce'
					) }
				/>
				<IconWithText
					icon={ lock }
					title={ paymentTitle }
					description={ __(
						'Safety pay with WooCommerce payments, The only payment solution fully integrated to Woo.',
						'woocommerce'
					) }
				/>
			</div>
			<div className="woocommerce-marketplace__footer-logo">
				<img src={ WooIcon } alt="Woo Logo" aria-hidden="true" />
				<span>{ __( 'Woo Marketplace', 'woocommerce' ) }</span>
			</div>
		</div>
	);
}

export default function Footer(): JSX.Element {
	return (
		<WooFooterItem>
			<FooterContent />
		</WooFooterItem>
	);
}
