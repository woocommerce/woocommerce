/**
 * External dependencies
 */
import { totals } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { registerFeaturePluginBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';

registerFeaturePluginBlockType(
	'woocommerce/checkout-order-summary-shipping-block',
	{
		icon: {
			src: (
				<Icon
					icon={ totals }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		edit: Edit,
		save: Save,
	}
);
