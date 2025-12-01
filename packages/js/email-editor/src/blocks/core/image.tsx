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

const imageEditCallback = createHigherOrderComponent(
	( BlockEdit ) =>
		function alterBlocksEdits( props ) {
			if ( props.name !== 'core/image' ) {
				return <BlockEdit { ...props } />;
			}
			// Because we cannot support displaying the modal with image after clicking in the email we have to hide the toggle
			const deactivateToggleCss = `
        .components-tools-panel .components-toggle-control { display: none; }
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
	'imageEditCallback'
);

/**
 * Because CSS property filter is not supported in almost 50% of email clients we have to disable it
 */
function disableImageFilter() {
	updateBlockSettings( 'core/image', ( current ) => ( {
		...current,
		supports: {
			...( current.supports || {} ),
			filter: {
				// @ts-expect-error filter is not supported in the types
				...( ( current.supports as BlockSupports )?.filter || {} ),
				duetone: false,
			},
		},
	} ) );
}

function hideExpandOnClick() {
	addFilterForEmail(
		'editor.BlockEdit',
		'woocommerce-email-editor/hide-expand-on-click',
		imageEditCallback
	);
}

export { hideExpandOnClick, disableImageFilter };
