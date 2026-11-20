/**
 * Internal dependencies
 */
import type { BlockAttributes, OverlayButtonStyle } from '../types';

const OVERLAY_BUTTON_STYLES: readonly OverlayButtonStyle[] = [
	'plain',
	'button',
];

export const isOverlayButtonStyle = (
	value: unknown
): value is OverlayButtonStyle =>
	OVERLAY_BUTTON_STYLES.includes( value as OverlayButtonStyle );

/**
 * Blocks saved before the style setting existed have no value. They keep the
 * plain button, so updating WooCommerce doesn't change how stores look.
 * Keep in sync with ProductFilters::get_overlay_button_style() in PHP.
 */
export const getOverlayButtonStyle = (
	attributes: BlockAttributes
): OverlayButtonStyle =>
	isOverlayButtonStyle( attributes.overlayButtonStyle )
		? attributes.overlayButtonStyle
		: 'plain';
