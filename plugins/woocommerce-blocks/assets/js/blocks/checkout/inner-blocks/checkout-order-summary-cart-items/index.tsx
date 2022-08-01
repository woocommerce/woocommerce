/**
 * External dependencies
 */
import { cart } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';

registerFeaturePluginBlockType(
	'woocommerce/checkout-order-summary-cart-items-block',
	{
		icon: {
			src: (
				<Icon
					icon={ cart }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		edit: Edit,
		save: Save,
	}
);
