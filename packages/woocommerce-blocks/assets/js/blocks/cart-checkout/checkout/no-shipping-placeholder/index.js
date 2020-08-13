/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder, Button } from 'wordpress-components';
import { Icon, truck } from '@woocommerce/icons';
import { ADMIN_URL } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './style.scss';

const NoShippingPlaceholder = () => {
	return (
		<Placeholder
			icon={ <Icon srcElement={ truck } /> }
			label={ __( 'Shipping options', 'woocommerce' ) }
			className="wc-block-checkout__no-shipping-placeholder"
		>
			<span className="wc-block-checkout__no-shipping-placeholder-description">
				{ __(
					'Your store does not have any Shipping Options configured. Once you have added your Shipping Options they will appear here.',
					'woocommerce'
				) }
			</span>
			<Button
				isDefault
				href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping` }
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __(
					'Configure Shipping Options',
					'woocommerce'
				) }
			</Button>
		</Placeholder>
	);
};

export default NoShippingPlaceholder;
