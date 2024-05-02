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
	/**
	 * Must be memoized to prevent infinite re-render loops.
	 */
	onVisible?: () => void;
	/**
	 * Must be memoized to prevent infinite re-render loops.
	 */
	onHidden?: () => void;
};

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
