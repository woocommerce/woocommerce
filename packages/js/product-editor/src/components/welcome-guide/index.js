/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import WelcomeGuideDefault from './default';

export default function WelcomeGuide() {
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
