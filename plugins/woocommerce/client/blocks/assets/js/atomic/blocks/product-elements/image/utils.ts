/**
 * Internal dependencies
 */
import { ImageSizing, type BlockAttributes } from './types';

type AspectRatioAttributes = Pick<
	Partial< BlockAttributes >,
	'style' | 'aspectRatio' | 'imageSizing'
>;

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
	attributes: AspectRatioAttributes | undefined,
	storeAspectRatio: string | null | undefined
): string | undefined => {
	const { style, aspectRatio, imageSizing } = attributes ?? {};

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
