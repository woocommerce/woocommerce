/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import WelcomeGuideDefault from './default';

/**
 * The components from this directory are copied from gutenberg.
 * They are used to show the welcome guide if the user opens the block editor for the first time from the product editor.
 * Ideally it should be exposed from gutenberg and we should use it from there.
 */

export default function ModalEditorWelcomeGuide() {
	const { isActive } = useSelect( ( select ) => {
		const { get } = select( 'core/preferences' );
		return {
			isActive: get( 'core/edit-post', 'welcomeGuide' ),
		};
	}, [] );

	if ( ! isActive ) {
		return null;
	}

	return <WelcomeGuideDefault />;
}
