/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import { percent, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';
import deprecated from './deprecated';

const blockConfig = {
	...metadata,
	icon: (
		<Icon
			icon={ percent }
			className="wc-block-editor-components-block-icon"
		/>
	),
	edit,
	save: () => null,
	deprecated,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
