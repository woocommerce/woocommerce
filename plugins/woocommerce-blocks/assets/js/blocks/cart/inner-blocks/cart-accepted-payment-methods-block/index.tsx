/**
 * External dependencies
 */
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';
import { Icon, payment } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';

registerFeaturePluginBlockType(
	'woocommerce/cart-accepted-payment-methods-block',
	{
		icon: {
			src: (
				<Icon
					icon={ payment }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		edit: Edit,
		save: Save,
	}
);
