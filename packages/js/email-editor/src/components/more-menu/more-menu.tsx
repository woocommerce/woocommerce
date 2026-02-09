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
						label={ __( 'Fullscreen mode', 'woocommerce' ) }
						info={ __(
							'Show and hide the admin user interface',
							'woocommerce'
						) }
						messageActivated={ __(
							'Fullscreen mode activated.',
							'woocommerce'
						) }
						messageDeactivated={ __(
							'Fullscreen mode deactivated.',
							'woocommerce'
						) }
						shortcut={ displayShortcut.secondary( 'f' ) }
					/>
				</ViewMoreMenuGroup>
			) }
		</>
	);
};
