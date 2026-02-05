/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, button } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ button }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: () => {
		return (
			<div
				style={ {
					padding: '20px',
					border: '2px dashed #7f54b3',
					borderRadius: '8px',
					background: '#f8f4fb',
					textAlign: 'center',
				} }
			>
				<p style={ { margin: 0, color: '#7f54b3', fontWeight: 'bold' } }>
					🧪 Batching Demo Block
				</p>
				<p style={ { margin: '10px 0 0', color: '#666', fontSize: '14px' } }>
					This block demonstrates the mutation batcher on the frontend.
					View this page to see the demo buttons.
				</p>
			</div>
		);
	},
	save: () => null, // Dynamic block - rendered by PHP
} );
