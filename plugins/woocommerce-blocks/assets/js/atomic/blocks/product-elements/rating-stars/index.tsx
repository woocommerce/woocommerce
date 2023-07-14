/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, starFilled } from '@wordpress/icons';
import { isExperimentalBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import { supports } from './support';

if ( isExperimentalBuild() ) {
	registerBlockType( metadata, {
		icon: {
			src: (
				<Icon
					icon={ starFilled }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		supports,
		edit,
	} );
}
