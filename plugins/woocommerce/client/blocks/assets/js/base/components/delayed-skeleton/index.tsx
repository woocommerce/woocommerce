/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import type { ReactNode } from 'react';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './style.scss';

export interface DelayedSkeletonProps {
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
export const DelayedSkeleton = ( {
	children,
	isLoading,
	skeleton,
}: DelayedSkeletonProps ): JSX.Element => {
	const MIN_DISPLAY_TIME = 400; // ms
	const FADE_OUT_DURATION = 150; // ms
	const [ showSkeleton, setShowSkeleton ] = useState( isLoading );
	const [ fadingOut, setFadingOut ] = useState( false );
	const [ startTime, setStartTime ] = useState< number | null >( null );

	useEffect( () => {
		let timer: ReturnType< typeof setTimeout >;

		if ( isLoading ) {
			setShowSkeleton( true );
			setFadingOut( false );
			setStartTime( Date.now() );
		} else if ( startTime ) {
			const elapsed = Date.now() - startTime;
			const remainingTime = Math.max( 0, MIN_DISPLAY_TIME - elapsed );

			timer = setTimeout( () => {
				setFadingOut( true );
				setTimeout( () => {
					setShowSkeleton( false );
					setFadingOut( false );
					setStartTime( null );
				}, FADE_OUT_DURATION );
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
