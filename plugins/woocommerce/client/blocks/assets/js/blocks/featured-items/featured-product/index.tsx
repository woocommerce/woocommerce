/**
 * External dependencies
 */
import { Icon, starEmpty } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import Block from './block';
import { register } from '../register';
import { example } from './example';
import deprecated from './deprecated';
import metadata from './block.json';

register( Block, example, metadata, {
	deprecated,
	icon: {
		src: (
			<Icon
				icon={ starEmpty }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
} );
