/**
 * External dependencies
 */
import { registerTemplateRestrictedBlockType } from '@woocommerce/atomic-utils';
import { Icon } from '@wordpress/icons';
import { totals } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './style.scss';

const blockConfig = {
	...metadata,
	icon: {
		src: (
			<Icon
				icon={ totals }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit,
};

registerTemplateRestrictedBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
	templates: [
		'archive-product',
		'product-search-results',
		'taxonomy-product_attribute',
		'taxonomy-product_brand',
		'taxonomy-product_cat',
		'taxonomy-product_tag',
	],
} );
