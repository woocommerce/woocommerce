/**
 * Internal dependencies
 */
import type { BlockAttributes, OverlayMode } from '../types';

const OVERLAY_MODES: readonly OverlayMode[] = [ 'off', 'mobile', 'always' ];

export const isOverlayMode = ( value: unknown ): value is OverlayMode =>
	OVERLAY_MODES.includes( value as OverlayMode );

export const getOverlayMode = ( attributes: BlockAttributes ): OverlayMode => {
	if ( isOverlayMode( attributes.overlayMode ) ) {
		return attributes.overlayMode;
	}

	return 'mobile';
};
