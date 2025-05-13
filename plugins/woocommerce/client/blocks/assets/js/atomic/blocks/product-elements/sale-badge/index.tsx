/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import { percent, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import save from '../save';
import edit from './edit';
import metadata from './block.json';

const blockConfig = {
	...metadata,
	icon: (
		<Icon
			icon={ percent }
			className="wc-block-editor-components-block-icon"
		/>
	),
	edit,
	save,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
