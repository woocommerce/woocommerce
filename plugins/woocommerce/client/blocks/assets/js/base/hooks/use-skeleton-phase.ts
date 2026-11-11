/**
 * External dependencies
 */
import {
	useLayoutEffect,
	useRef,
	useState,
	useSyncExternalStore,
} from '@wordpress/element';

/**
 * Skeleton reveal rule: all skeletons in one loading phase end together, and the values that wait for them change at
 * that moment. A phase starts when a skeleton starts to load, and ends when no skeleton loads. Each skeleton shows
 * for at least `MIN_SKELETON_TIME`, so that it does not flash, except with reduced motion.
 */
export const MIN_SKELETON_TIME = 300; // The `lg` duration.

/**
 * A skeleton that never ends, such as a payment method that never initializes, must not hold the others. After this
 * time, the skeletons that have loaded end without it, and it no longer holds a phase.
 */
export const MAX_SKELETON_WAIT = 1000;

interface SkeletonPhase {
	isActive: boolean;
	// The skeletons that load and hold the phase.
	loading: Set< object >;
	lastStartTime: number;
	timer: ReturnType< typeof setTimeout > | undefined;
	listeners: Set< () => void >;
}

declare global {
	var wcBlocksSkeletonPhase: SkeletonPhase | undefined;
}

// Cart, Checkout, and the shared components package each have their own copy of this module, so the phase lives on
// `globalThis`.
const getPhase = (): SkeletonPhase => {
	globalThis.wcBlocksSkeletonPhase ??= {
		isActive: false,
		loading: new Set(),
		lastStartTime: 0,
		timer: undefined,
		listeners: new Set(),
	};
	return globalThis.wcBlocksSkeletonPhase;
};

const setIsActive = ( isActive: boolean ) => {
	const phase = getPhase();
	if ( phase.isActive === isActive ) {
		return;
	}
	phase.isActive = isActive;
	phase.listeners.forEach( ( listener ) => listener() );
};

const endPhase = () => {
	const phase = getPhase();
	clearTimeout( phase.timer );
	phase.timer = undefined;
	phase.loading.clear();
	setIsActive( false );
};

const startLoading = ( skeleton: object ) => {
	const phase = getPhase();
	clearTimeout( phase.timer );
	phase.timer = undefined;
	phase.loading.add( skeleton );
	phase.lastStartTime = Date.now();
	setIsActive( true );
};

const stopLoading = ( skeleton: object ) => {
	const phase = getPhase();
	// A skeleton that was dropped from its phase after `MAX_SKELETON_WAIT` does not hold a later one.
	if ( ! phase.loading.delete( skeleton ) ) {
		return;
	}
	clearTimeout( phase.timer );
	if ( phase.loading.size > 0 ) {
		phase.timer = setTimeout( endPhase, MAX_SKELETON_WAIT );
		return;
	}
	const isReducedMotion =
		window.matchMedia?.( '(prefers-reduced-motion: reduce)' ).matches ??
		false;
	const remainingTime = isReducedMotion
		? 0
		: phase.lastStartTime + MIN_SKELETON_TIME - Date.now();
	if ( remainingTime > 0 ) {
		phase.timer = setTimeout( endPhase, remainingTime );
		return;
	}
	endPhase();
};

const subscribe = ( listener: () => void ) => {
	const { listeners } = getPhase();
	listeners.add( listener );
	return () => {
		listeners.delete( listener );
	};
};

const getIsActive = () => getPhase().isActive;

/**
 * Whether to show a loading skeleton. The skeleton shows while `isLoading` is set, and then until all the skeletons
 * of the same loading phase have loaded (Skeleton reveal rule).
 *
 * @param isLoading Whether the content of the skeleton loads.
 * @return Whether to show the skeleton instead of the content.
 */
export function useSkeletonPhase( isLoading: boolean ): boolean {
	const isPhaseActive = useSyncExternalStore( subscribe, getIsActive );
	const [ skeleton ] = useState( () => ( {} ) );
	const hasSkeleton = useRef( isLoading );

	// Read during render, so the skeleton and the content never both show, or neither, for a frame.
	if ( isLoading ) {
		hasSkeleton.current = true;
	} else if ( ! isPhaseActive ) {
		hasSkeleton.current = false;
	}

	// A layout effect starts the phase before paint, so the values that wait for it never show early. Its cleanup ends
	// the phase in the same commit as the last skeleton loads.
	useLayoutEffect( () => {
		if ( ! isLoading ) {
			return;
		}
		startLoading( skeleton );
		return () => stopLoading( skeleton );
	}, [ isLoading, skeleton ] );

	return isLoading || ( hasSkeleton.current && isPhaseActive );
}

/**
 * Holds a value while skeletons show, and returns the new value when they end, so that it changes at the same moment
 * as the values behind the skeletons (Skeleton reveal rule).
 *
 * @param value The current value.
 * @return The value to show.
 */
export function useSkeletonPhaseValue< T >( value: T ): T {
	const isPhaseActive = useSyncExternalStore( subscribe, getIsActive );
	const heldValue = useRef( value );

	if ( ! isPhaseActive ) {
		heldValue.current = value;
	}

	return heldValue.current;
}
