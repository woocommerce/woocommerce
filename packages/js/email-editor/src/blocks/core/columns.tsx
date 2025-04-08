/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Block } from '@wordpress/blocks/index';
import { addFilter } from '@wordpress/hooks';

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
	addFilter(
		'editor.BlockEdit',
		'woocommerce-email-editor/deactivate-stack-on-mobile',
		columnsEditCallback
	);
}

/**
 * Disables layout support for columns and column blocks because
 * the default layout `flex` add gaps between columns that it is not possible to support in emails.
 */
function disableColumnsLayout() {
	addFilter(
		'blocks.registerBlockType',
		'woocommerce-email-editor/disable-columns-layout',
		( settings, name ) => {
			if ( name === 'core/columns' || name === 'core/column' ) {
				// eslint-disable-next-line @typescript-eslint/no-unsafe-return
				return {
					...settings,
					supports: {
						...settings.supports,
						layout: false,
					},
				};
			}

			// eslint-disable-next-line @typescript-eslint/no-unsafe-return
			return settings;
		}
	);
}

function enhanceColumnsBlock() {
	addFilter(
		'blocks.registerBlockType',
		'woocommerce-email-editor/change-columns',
		( settings: Block, name ) => {
			if ( name === 'core/columns' ) {
				return {
					...settings,
					supports: {
						...settings.supports,
						background: {
							backgroundImage: true,
						},
					},
				};
			}
			return settings;
		}
	);
}

export { deactivateStackOnMobile, disableColumnsLayout, enhanceColumnsBlock };
