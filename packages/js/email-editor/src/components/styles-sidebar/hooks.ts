/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';
import {
	useHasStylesColorPanel,
	useHasStylesBackgroundPanel,
} from '../../private-apis';

// Settings describing text and background color support only. Up to
// WordPress 7.0 the core ColorPanel handles these controls, so its gate
// returns true for them; since WordPress 7.1 text color renders in the
// typography panel and background color in the background panel, and the
// gate returns false.
const TEXT_BACKGROUND_PROBE_SETTINGS = {
	color: { text: true, background: true, custom: true },
};

/**
 * Whether the text color control belongs in the typography panel rather than
 * the Colors screen in the running WordPress version.
 */
export function useHasTextColorInTypographyPanel(): boolean {
	return ! useHasStylesColorPanel( TEXT_BACKGROUND_PROBE_SETTINGS );
}

/**
 * Theme settings scoped to the background color control only. Background
 * image and gradient stay disabled — the global styles Background screen
 * manages just the email background color; per-block background images are
 * unaffected.
 */
export function useBackgroundScreenSettings() {
	const theme = useSelect( ( select ) => select( storeName ).getTheme(), [] );
	return useMemo(
		() => ( {
			...theme?.settings,
			background: {
				backgroundImage: false,
				backgroundSize: false,
				gradient: false,
			},
		} ),
		[ theme?.settings ]
	);
}

/**
 * Whether the styles sidebar has a Background screen. True only in WordPress
 * versions whose BackgroundPanel renders the background color control
 * (7.1+) — older versions keep background color in the Colors screen.
 */
export function useHasBackgroundScreen(): boolean {
	return useHasStylesBackgroundPanel( useBackgroundScreenSettings() );
}
