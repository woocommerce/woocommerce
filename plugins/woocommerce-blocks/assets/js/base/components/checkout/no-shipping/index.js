/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Placeholder, Button } from '@wordpress/components';
import { Icon, truck } from '@woocommerce/icons';
import { ADMIN_URL } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import './style.scss';

const NoShipping = () => {
	return (
		<Placeholder
			icon={ <Icon srcElement={ truck } /> }
			label={ __( 'Shipping options', 'woo-gutenberg-products-block' ) }
			className="wc-block-checkout__no-shipping"
		>
			<span className="wc-block-checkout__no-shipping-description">
				{ __(
					'Your store does not have any Shipping Options configured. Once you have added your Shipping Options they will appear here.',
					'woo-gutenberg-products-block'
				) }
			</span>
			<Button
				isDefault
				href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping` }
			>
				{ __(
					'Configure Shipping Options',
					'woo-gutenberg-products-block'
				) }
			</Button>
		</Placeholder>
	);
};

export default NoShipping;
