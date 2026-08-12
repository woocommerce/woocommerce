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

register( Block, example, metadata, {
	deprecated,
	icon: {
		src: (
			<Icon
				icon={ folderStarred }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
} );
