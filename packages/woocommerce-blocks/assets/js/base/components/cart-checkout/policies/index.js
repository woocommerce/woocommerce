/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	PRIVACY_URL,
	TERMS_URL,
	PRIVACY_PAGE_NAME,
	TERMS_PAGE_NAME,
} from '@woocommerce/block-settings';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import './style.scss';

const Policies = () => {
	return (
		<ul className="wc-block-components-checkout-policies">
			{ PRIVACY_URL && (
				<li className="wc-block-components-checkout-policies__item">
					<a
						href={ PRIVACY_URL }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ PRIVACY_PAGE_NAME
							? decodeEntities( PRIVACY_PAGE_NAME )
							: __(
									'Privacy Policy',
									'woocommerce'
							  ) }
					</a>
				</li>
			) }
			{ TERMS_URL && (
				<li className="wc-block-components-checkout-policies__item">
					<a
						href={ TERMS_URL }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ TERMS_PAGE_NAME
							? decodeEntities( TERMS_PAGE_NAME )
							: __(
									'Terms and Conditions',
									'woocommerce'
							  ) }
					</a>
				</li>
			) }
		</ul>
	);
};

export default Policies;
