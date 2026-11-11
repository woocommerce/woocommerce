/**
 * External dependencies
 */
import { useLayoutEffect, useState } from '@wordpress/element';
import { useReducedMotion } from '@wordpress/compose';
import type { ReactNode } from 'react';

// Long enough that a skeleton does not flash, short enough that it does not hold the page.
const MIN_DISPLAY_TIME = 300;

declare global {
	// eslint-disable-next-line no-var
	var wcBlocksSkeletonPhaseStart: number | undefined;
}

export interface DelayedContentWithSkeletonProps {
	/**
	 * The component to render when loading is complete
	 */
	children: ReactNode;

	/**
	 * Whether the content is currently loading
	 */
	isLoading: boolean;

	/**
	 * The skeleton component to show while loading
	 */
	skeleton: ReactNode;
}

/**
 * Whether to show a skeleton: while `isLoading`, and until `MIN_DISPLAY_TIME` after the loading phase started. The
 * start is shared, so skeletons that start during a phase end with the others. It is on
 * `globalThis` because each script bundle has its own copy of this module. With reduced motion there is no minimum.
 */
export const useDelayedSkeleton = ( isLoading: boolean ): boolean => {
	const isReducedMotion = useReducedMotion();
	const [ isHeld, setIsHeld ] = useState( false );

	// A layout effect, so a skeleton whose minimum has passed ends in the same frame as its loading.
	useLayoutEffect( () => {
		const now = Date.now();
		const phaseStart = globalThis.wcBlocksSkeletonPhaseStart ?? 0;
		if ( isLoading ) {
			if ( now - phaseStart >= MIN_DISPLAY_TIME ) {
				globalThis.wcBlocksSkeletonPhaseStart = now;
			}
			setIsHeld( ! isReducedMotion );
			return;
		}
		const timer = setTimeout(
			() => setIsHeld( false ),
			Math.max( 0, phaseStart + MIN_DISPLAY_TIME - now )
		);
		return () => clearTimeout( timer );
	}, [ isLoading, isReducedMotion ] );

	return isLoading || isHeld;
};

/**
 * Shows a skeleton while content loads, and for a short minimum time so that it does not flash.
 */
export const DelayedContentWithSkeleton = ( {
	children,
	isLoading,
	skeleton,
}: DelayedContentWithSkeletonProps ): JSX.Element => {
	const showSkeleton = useDelayedSkeleton( isLoading );

	return <>{ showSkeleton ? skeleton : children }</>;
};
