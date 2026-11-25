/**
 * Internal dependencies
 */
import type { BlockAttributes, OverlayButtonStyle } from '../types';

// The style name ends up in a class name, so only allow safe characters.
export const isOverlayButtonStyle = (
	value: unknown
): value is OverlayButtonStyle =>
	typeof value === 'string' && /^[A-Za-z0-9_-]+$/.test( value );

/**
 * Any value other than plain is the name of a core Button style, such as fill,
 * outline or a style the theme adds. Blocks saved before the style setting
 * existed have no value. They keep the plain button, so updating WooCommerce
 * doesn't change how stores look. A value that is not a safe class name also
 * means plain. Keep in sync with ProductFilters::get_overlay_button_style() in PHP.
 */
export const getOverlayButtonStyle = (
	attributes: BlockAttributes
): OverlayButtonStyle =>
	isOverlayButtonStyle( attributes.overlayButtonStyle )
		? attributes.overlayButtonStyle
		: 'plain';
