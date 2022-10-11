/**
 * External dependencies
 */
import { Icon, shipping } from '@wordpress/icons';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import attributes from './attributes';

registerExperimentalBlockType( 'woocommerce/checkout-collection-method-block', {
	icon: {
		src: (
			<Icon
				icon={ shipping }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes,
	edit: Edit,
	save: Save,
} );
