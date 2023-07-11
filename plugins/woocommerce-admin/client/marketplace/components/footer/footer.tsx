/**
 * External dependencies
 */
import { WooFooterItem } from '@woocommerce/admin-layout';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './footer.scss';
import IconWithText from '../icon-with-text/icon-with-text';
import checkIcon from '../../assets/img/check.svg';
import lockIcon from '../../assets/img/lock.svg';
import commentIcon from '../../assets/img/comment-content.svg';

function FooterContent(): JSX.Element {
	return (
		<div className="woocommerce-marketplace__footer">
			<div className="woocommerce-marketplace__footer-columns">
				<IconWithText
					icon={ checkIcon }
					text={ __( '30 day money back guarantee', 'woocommerce' ) }
				/>
				<IconWithText
					icon={ commentIcon }
					text={ __( 'Support teams across the world', 'woocommerce' ) }
				/>
				<IconWithText
					icon={ lockIcon }
					text={ __( 'Safe & Secure online payment', 'woocommerce' ) }
				/>
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
