/**
 * External dependencies
 */
import { currencyDollar, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import { supports } from './supports';
import metadata from './block.json';

export const ProductPriceBlockSettings = {
	...sharedConfig,
	title: metadata.title,
	description: metadata.description,
	usesContext: [ 'query', 'queryId', 'postId' ],
	icon: (
		<Icon
			icon={ currencyDollar }
			className="wc-block-editor-components-block-icon"
		/>
	),
	attributes: metadata.attributes,
	supports,
	edit,
};
