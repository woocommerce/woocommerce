/**
 * Internal dependencies
 */
import { AspectRatioStyle, ImageSizing } from './types';

export const isTryingToDisplayLegacySaleBadge = ( showSaleBadge?: boolean ) => {
	// If the block is pristine, it doesn't have a showSaleBadge attribute
	// but it is supposed to be `true` by default.
	if ( showSaleBadge === undefined ) {
		return true;
	}

	// If the block was edited, it will have a showSaleBadge attribute
	// that we should respect.
	return showSaleBadge;
};

/**
 * Resolve the aspect ratio for a product image.
 *
 * Block-level overrides take priority over store thumbnail cropping settings.
 */
export const resolveAspectRatio = (
	style: AspectRatioStyle | undefined,
	aspectRatio: string | undefined,
	storeAspectRatio: string | null | undefined,
	imageSizing: ImageSizing | undefined
): string | undefined => {
	if (
		style &&
		style.dimensions &&
		style.dimensions.aspectRatio &&
		typeof style.dimensions.aspectRatio === 'string'
	) {
		return style.dimensions.aspectRatio;
	}

	if ( aspectRatio && typeof aspectRatio === 'string' ) {
		return aspectRatio;
	}

	if (
		imageSizing &&
		( imageSizing === ImageSizing.THUMBNAIL ||
			imageSizing === ImageSizing.CROPPED )
	) {
		return storeAspectRatio ?? undefined;
	}

	return undefined;
};
