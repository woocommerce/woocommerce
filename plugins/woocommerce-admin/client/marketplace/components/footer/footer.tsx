/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './footer.scss';
import IconWithText from '../icon-with-text/icon-with-text';
export default function Footer() {
	return (
		<div className="woocommerce-marketplace__footer">
			<div className="woocommerce-marketplace__footer-columns"></div>
			<IconWithText />
			<span>Support teams across the world</span>
			<span>Safe &amp; Secure online payment</span>
		</div>
	);
}
