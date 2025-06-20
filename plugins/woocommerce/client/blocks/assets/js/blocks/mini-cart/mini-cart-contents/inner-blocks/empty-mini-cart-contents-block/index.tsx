/**
 * External dependencies
 */
import { removeCart } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import { isExperimentalMiniCartEnabled } from '../../../../../settings/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

const blockSettings = {
	icon: {
		src: (
			<Icon
				icon={ removeCart }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: Edit,
	save: Save,
};

if ( isExperimentalMiniCartEnabled() ) {
	blockSettings.save = () => {
		return <InnerBlocks.Content />;
	};
}

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore -- TypeScript expects some required properties which we already
// registered in PHP.
registerBlockType(
	'woocommerce/empty-mini-cart-contents-block',
	blockSettings
);
