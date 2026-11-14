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

/**
 * Whether to show a skeleton: while `isLoading`, and for a minimum duration
 * to prevent jarring quick flashes when content loads fast.
 */
export const useDelayedSkeleton = ( isLoading: boolean ): boolean => {
	const disableMotion = useReducedMotion();
	const [ showSkeleton, setShowSkeleton ] = useState( isLoading );
	const [ startTime, setStartTime ] = useState< number | null >( null );

	useEffect( () => {
		// If motion is disabled, just sync with isLoading state
		if ( disableMotion ) {
			setShowSkeleton( isLoading );
			return;
		}

		// For motion-enabled, use the delay logic
		const MIN_DISPLAY_TIME = 2000;
		let timer: ReturnType< typeof setTimeout >;

		if ( isLoading ) {
			setShowSkeleton( true );
			setStartTime( Date.now() );
		} else if ( startTime ) {
			const elapsed = Date.now() - startTime;
			const remainingTime = Math.max( 0, MIN_DISPLAY_TIME - elapsed );
			timer = setTimeout( () => {
				setShowSkeleton( false );
				setStartTime( null );
			}, remainingTime );
		}

		return () => {
			if ( timer ) {
				clearTimeout( timer );
			}
		};
	}, [ isLoading, startTime, disableMotion ] );

	// `isLoading` too, so the skeleton shows in the same render as loading starts, not one effect later.
	return isLoading || showSkeleton;
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
