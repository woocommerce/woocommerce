/**
 * External dependencies
 */
import { WooFooterItem } from '@woocommerce/admin-layout';
import { __ } from '@wordpress/i18n';
import { check, commentContent as comment, lock } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './footer.scss';
import IconWithText from '../icon-with-text/icon-with-text';
import WooIcon from '../../assets/images/woo-icon.svg';

function FooterContent(): JSX.Element {
	return (
		<div className="woocommerce-marketplace__footer">
			<h2 className="woocommerce-marketplace__footer-title">
				Grow your business with hundreds of solutions for your store.
			</h2>
			<div className="woocommerce-marketplace__footer-columns">
				<IconWithText
					icon={ check }
					// eslint-disable-next-line prettier/prettier
					title={ __(
						'30 day money back guarantee',
						'woocommerce'
					) }
					description={ __(
						'For extensions and themes purchased from our Marketplace, we offer a full refund within 30 days of your date of purchase.',
						'woocommerce'
					) }
				/>
				<IconWithText
					icon={ comment }
					title={ __(
						'Support teams across the world',
						'woocommerce'
					) }
					description={ __(
						'We have happiness engineers around round the globe to help you at any given time.',
						'woocommerce'
					) }
				/>
				<IconWithText
					icon={ lock }
					title={ __(
						'Safe & Secure online payment',
						'woocommerce'
					) }
					description={ __(
						'Safety pay with WooCommerce payments, The only payment solution fully integrated to Woo.',
						'woocommerce'
					) }
				/>
			</div>
			<div className="woocommerce-marketplace__footer-logo">
				<img src={ WooIcon } alt="Woo Logo" aria-hidden="true" />
				<span>Woo Marketplace</span>
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
