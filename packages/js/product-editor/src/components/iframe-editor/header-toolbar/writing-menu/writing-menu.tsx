/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { MenuGroup } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useViewportMatch } from '@wordpress/compose';
import { createElement } from '@wordpress/element';
import {
	PreferenceToggleMenuItem,
	store as preferencesStore,
	// @ts-expect-error missing types.
} from '@wordpress/preferences';

export function WritingMenu() {
	// @ts-expect-error missing types.
	const { set: setPreference } = useDispatch( preferencesStore );

	const turnOffDistractionFree = () => {
		setPreference( 'core', 'distractionFree', false );
	};

	const isLargeViewport = useViewportMatch( 'medium' );
	if ( ! isLargeViewport ) {
		return null;
	}

	return (
		<MenuGroup label={ __( 'View', 'woocommerce' ) }>
			<PreferenceToggleMenuItem
				scope="core"
				name="fixedToolbar"
				onToggle={ turnOffDistractionFree }
				label={ __( 'Top toolbar', 'woocommerce' ) }
				info={ __(
					'Access all block and document tools in a single place',
					'woocommerce'
				) }
				messageActivated={ __(
					'Top toolbar activated',
					'woocommerce'
				) }
				messageDeactivated={ __(
					'Top toolbar deactivated',
					'woocommerce'
				) }
			/>
		</MenuGroup>
	);
}
