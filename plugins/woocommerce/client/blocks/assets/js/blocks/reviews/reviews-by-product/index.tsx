/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, commentContent } from '@wordpress/icons';
/**
 * Internal dependencies
 */
import '../editor.scss';
import Editor from './edit';
import sharedAttributes from '../attributes';
import save from '../save.js';
import { example } from '../example';
import metadata from './block.json';
/**
 * Register and run the "Reviews by Product" block.
 */
registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ commentContent }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	example: {
		...example,
		attributes: {
			...example.attributes,
			productId: 1,
		},
	},
	attributes: {
		...sharedAttributes,
		/**
		 * The id of the product to load reviews for.
		 */
		productId: {
			type: 'number',
		},
	},

	/**
	 * Renders and manages the block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		return <Editor { ...props } />;
	},

	/**
	 * Save the props to post content.
	 */
	save,
} );
