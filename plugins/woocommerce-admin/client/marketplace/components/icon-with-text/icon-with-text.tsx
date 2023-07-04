/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './icon-with-text.scss';
import checkIcon from '../../assets/img/check.svg';

export default function IconWithText() {
	return (
		<span className="icon-group">
			<img
				className="icon"
				src={ checkIcon }
				alt={ __( 'Checkmark', 'woocommerce' ) }
			/>
			<p>30-day money back guarantee</p>
		</span>
	);
}
