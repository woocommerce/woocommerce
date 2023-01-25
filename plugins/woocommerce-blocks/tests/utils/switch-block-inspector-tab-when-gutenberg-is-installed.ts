/**
 * External dependencies
 */
import { switchBlockInspectorTab } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { GUTENBERG_EDITOR_CONTEXT } from '../e2e/utils';

// @todo Remove this function when WP 6.2 is released. We can use the "switchBlockInspectorTab" function directly.
export const switchBlockInspectorTabWhenGutenbergIsInstalled = async (
	tabName: string
) => {
	if ( GUTENBERG_EDITOR_CONTEXT === 'core' ) {
		return;
	}
	await switchBlockInspectorTab( tabName );
};
