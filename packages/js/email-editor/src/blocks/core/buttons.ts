/**
 * Internal dependencies
 */
import { updateBlockSettings } from '../../config-tools/block-config';

/**
 * Switch layout to reduced flex email layout
 * Email render engine can't handle full flex layout se we need to switch to reduced flex layout
 */
function enhanceButtonsBlock() {
	updateBlockSettings( 'core/buttons', ( current ) => ( {
		...current,
		supports: {
			...( current.supports ?? {} ),
			layout: false, // disable block editor's layouts
			// enable email editor's reduced flex email layout
			__experimentalEmailFlexLayout: true, // eslint-disable-line
		},
	} ) );
}

export { enhanceButtonsBlock };
