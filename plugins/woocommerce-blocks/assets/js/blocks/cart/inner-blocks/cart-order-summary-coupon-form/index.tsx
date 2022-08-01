/**
 * External dependencies
 */
import { Icon, tag } from '@wordpress/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';

registerFeaturePluginBlockType(
	'woocommerce/cart-order-summary-coupon-form-block',
	{
		icon: {
			src: (
				<Icon
					icon={ tag }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		edit: Edit,
		save: Save,
	}
);
