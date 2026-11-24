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

export const getOverlayButtonStyle = (
	attributes: BlockAttributes
): OverlayButtonStyle =>
	isOverlayButtonStyle( attributes.overlayButtonStyle )
		? attributes.overlayButtonStyle
		: 'plain';
