/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import { Icon, button } from '@wordpress/icons';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { QuantitySelectorStyle } from './settings';
import AddToCartFormEdit from './edit';
import './style.scss';
import './editor.scss';
import '../../../base/components/quantity-selector/style.scss';

export interface Attributes {
	className?: string;
	quantitySelectorStyle: QuantitySelectorStyle;
}

const blockConfig = {
	...( metadata as BlockConfiguration< Attributes > ),
	edit: AddToCartFormEdit,
	icon: {
		src: (
			<Icon
				icon={ button }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	ancestor: [ 'woocommerce/single-product' ],
	save() {
		return null;
	},
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
