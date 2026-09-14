/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';
import { BlockSupports } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { updateBlockSettings } from '../../config-tools/block-config';
import { addFilterForEmail } from '../../config-tools/filters';

const columnsEditCallback = createHigherOrderComponent(
	( BlockEdit ) =>
		function alterBlocksEdits( props ) {
			if ( props.name !== 'core/columns' ) {
				return <BlockEdit { ...props } />;
			}
			// CSS sets opacity by the class is-disabled by the toggle component from the Gutenberg package
			// To deactivating the input we use CSS pointer-events because we want to avoid JavaScript hacks
			const deactivateToggleCss = `
      .components-panel__body .components-toggle-control .components-form-toggle { opacity: 0.3; }
      .components-panel__body .components-toggle-control .components-form-toggle__input { pointer-events: none; }
      .components-panel__body .components-toggle-control label { pointer-events: none; }
    `;

			return (
				<>
					<BlockEdit { ...props } />
					<InspectorControls>
						<style>{ deactivateToggleCss }</style>
					</InspectorControls>
				</>
			);
		},
	'columnsEditCallback'
);

function deactivateStackOnMobile() {
	addFilterForEmail(
		'editor.BlockEdit',
		'woocommerce-email-editor/deactivate-stack-on-mobile',
		columnsEditCallback
	);
}

const COLUMN_BLOCKS = [ 'core/column', 'core/columns' ];

/**
 * Disables layout support for columns and column blocks because
 * the default layout `flex` add gaps between columns that it is not possible to support in emails.
 *
 * Also, enhances the columns block to support background image.
 */
function disableColumnsLayoutAndEnhanceColumnsBlock() {
	COLUMN_BLOCKS.forEach( ( blockName ) => {
		updateBlockSettings( blockName, ( current ) => ( {
			...current,
			supports: {
				...( current.supports || {} ),
				layout: false,
				background: {
					// Preserve any existing background supports and enable backgroundImage
					// @ts-expect-error BlockSupports type not complete
					...( ( current.support as BlockSupports )?.background ||
						{} ),
					backgroundImage: true,
				},
			},
		} ) );
	} );
}

export { deactivateStackOnMobile, disableColumnsLayoutAndEnhanceColumnsBlock };
