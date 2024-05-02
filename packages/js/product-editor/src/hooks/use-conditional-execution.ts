/**
 * External dependencies
 */
import { RefObject } from 'react';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useVisibilityObserver } from './use-visibility-observer';

type UseConditionalExecutionProps = {
	elementOrSelector: RefObject< HTMLInputElement > | string;
	onVisible?: () => void;
	onHidden?: () => void;
};

// WARNING: Ensure `onVisible` and `onHidden` are memoized when passed to this hook.
// Not memoizing these functions can lead to infinite re-render loops due to them
// being dependencies in this effect.
export function useConditionalExecution( {
	elementOrSelector,
	onVisible,
	onHidden,
}: UseConditionalExecutionProps ) {
	const isVisible = useVisibilityObserver( elementOrSelector );

	useEffect( () => {
		if ( isVisible ) {
			onVisible?.();
		} else {
			onHidden?.();
		}
	}, [ isVisible, onVisible, onHidden ] );
}
