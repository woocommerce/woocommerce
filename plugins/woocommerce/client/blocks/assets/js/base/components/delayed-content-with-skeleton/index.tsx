/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { useReducedMotion } from '@wordpress/compose';
import type { ReactNode } from 'react';

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

// When the current loading phase started. Shared, so skeletons that mount partway through a phase, such as a
// new totals row, end with the others instead of on their own clock.
let phaseStartedAt = 0;

/**
 * Whether to show a skeleton: while `isLoading`, and for a minimum duration from the start of the loading phase,
 * to prevent jarring quick flashes when content loads fast.
 */
export const useDelayedSkeleton = ( isLoading: boolean ): boolean => {
	const disableMotion = useReducedMotion();
	const [ showSkeleton, setShowSkeleton ] = useState( isLoading );

	useEffect( () => {
		// If motion is disabled, just sync with isLoading state
		if ( disableMotion ) {
			setShowSkeleton( isLoading );
			return;
		}

		// For motion-enabled, use the delay logic
		const MIN_DISPLAY_TIME = 300;
		let timer: ReturnType< typeof setTimeout >;

		if ( isLoading ) {
			setShowSkeleton( true );
			if ( Date.now() - phaseStartedAt >= MIN_DISPLAY_TIME ) {
				phaseStartedAt = Date.now();
			}
		} else {
			const elapsed = Date.now() - phaseStartedAt;
			const remainingTime = Math.max( 0, MIN_DISPLAY_TIME - elapsed );
			timer = setTimeout( () => {
				setShowSkeleton( false );
			}, remainingTime );
		}

		return () => {
			if ( timer ) {
				clearTimeout( timer );
			}
		};
	}, [ isLoading, disableMotion ] );

	return showSkeleton;
};

/**
 * Component that ensures skeleton children are displayed for a minimum duration
 * to prevent jarring quick flashes when content loads fast
 */
export const DelayedContentWithSkeleton = ( {
	children,
	isLoading,
	skeleton,
}: DelayedContentWithSkeletonProps ): JSX.Element => {
	const showSkeleton = useDelayedSkeleton( isLoading );

	return <>{ showSkeleton ? skeleton : children }</>;
};
