/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
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
 * Component that ensures skeleton children are displayed for a minimum duration
 * to prevent jarring quick flashes when content loads fast
 */
export const DelayedContentWithSkeleton = ( {
	children,
	isLoading,
	skeleton,
}: DelayedContentWithSkeletonProps ): JSX.Element => {
	const MIN_DISPLAY_TIME = 300;
	const [ showSkeleton, setShowSkeleton ] = useState( isLoading );
	const [ startTime, setStartTime ] = useState< number | null >( null );

	useEffect( () => {
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
	}, [ isLoading, startTime ] );

	return <>{ showSkeleton ? skeleton : children }</>;
};
