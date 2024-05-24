/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { isExperimentalBlockStylingEnabled } from '@woocommerce/block-settings';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import { queryPaginationIcon } from './icon';
import './style.scss';

const featurePluginSupport = {
	...metadata.supports,
	...( isExperimentalBlockStylingEnabled() && {
		typography: {
			...metadata.supports.typography,
			__experimentalFontFamily: true,
			__experimentalFontStyle: true,
			__experimentalFontWeight: true,
			__experimentalTextTransform: true,
			__experimentalDefaultControls: {
				fontSize: true,
			},
		},
	} ),
};

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ queryPaginationIcon }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: {
		...metadata.attributes,
	},
	supports: {
		...featurePluginSupport,
	},
	edit,
	save() {
		return null;
	},
} );
