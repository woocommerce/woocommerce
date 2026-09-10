/**
 * External dependencies
 */
import { folderStarred } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import Block from './block';
import metadata from './block.json';
import { register } from '../register';
import { example } from './example';
import deprecated from './deprecated';
import { registerTermImageBinding } from './register-term-image-binding';

// Keep this explicit so production builds retain the client-side binding source.
registerTermImageBinding();

register( Block, example, metadata, {
	deprecated,
	supports: {
		align: [ 'wide', 'full' ],
		ariaLabel: true,
		html: false,
		interactivity: { clientNavigation: true },
	},
	// Namespaced keys keep undefined selected-category attributes from
	// overwriting the term context supplied by Terms Query in the editor.
	providesContext: {
		'woocommerce/featuredCategoryTermId': 'categoryId',
		'woocommerce/featuredCategoryTaxonomy': 'termTaxonomy',
	},
	icon: {
		src: (
			<Icon
				icon={ folderStarred }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
} );
