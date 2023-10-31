/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store } from '@wordpress/edit-post';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import WelcomeGuideDefault from './default';
import WelcomeGuideTemplate from './template';

export default function WelcomeGuide() {
	const { isActive, isTemplateMode } = useSelect( ( select ) => {
		const { isFeatureActive, isEditingTemplate } = select( store );
		const _isTemplateMode = isEditingTemplate();
		const feature = _isTemplateMode
			? 'welcomeGuideTemplate'
			: 'welcomeGuide';

		return {
			isActive: isFeatureActive( feature ),
			isTemplateMode: _isTemplateMode,
		};
	}, [] );

	if ( ! isActive ) {
		return null;
	}

	return isTemplateMode ? <WelcomeGuideTemplate /> : <WelcomeGuideDefault />;
}
