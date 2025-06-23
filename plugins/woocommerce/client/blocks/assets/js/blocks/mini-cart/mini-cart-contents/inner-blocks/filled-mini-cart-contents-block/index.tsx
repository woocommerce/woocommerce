/**
 * External dependencies
 */
import { filledCart } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import { isExperimentalMiniCartEnabled } from '../../../../../settings/blocks';

const blockSettings = {
	icon: {
		src: (
			<Icon
				icon={ filledCart }
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
	'woocommerce/filled-mini-cart-contents-block',
	blockSettings
);
