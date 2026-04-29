/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useViewportMatch } from '@wordpress/compose';
import { displayShortcut } from '@wordpress/keycodes';
import { PreferenceToggleMenuItem } from '@wordpress/preferences';

/**
 * Internal dependencies
 */
import { ViewMoreMenuGroup } from '../../private-apis';
import { storeName } from '../../store';

export const MoreMenu = () => {
	const isLargeViewport = useViewportMatch( 'large' );

	return (
		<>
			{ isLargeViewport && (
				<ViewMoreMenuGroup>
					<PreferenceToggleMenuItem
						scope={ storeName }
						name="fullscreenMode"
						label={ __( 'Fullscreen mode', __i18n_text_domain__ ) }
						info={ __(
							'Show and hide the admin user interface',
							__i18n_text_domain__
						) }
						messageActivated={ __(
							'Fullscreen mode activated.',
							__i18n_text_domain__
						) }
						messageDeactivated={ __(
							'Fullscreen mode deactivated.',
							__i18n_text_domain__
						) }
						shortcut={ displayShortcut.secondary( 'f' ) }
					/>
				</ViewMoreMenuGroup>
			) }
		</>
	);
};
