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
	isMemorized?: boolean;
};

export function useConditionalExecution( {
	elementOrSelector,
	onVisible,
	onHidden,
	isMemorized = false,
}: UseConditionalExecutionProps ) {
	const isVisible = useVisibilityObserver( elementOrSelector );

	function getDependencies() {
		const dependenciesList = [];
		if ( isMemorized ) {
			if ( onVisible !== undefined ) {
				dependenciesList.push( onVisible );
			}
			if ( onHidden !== undefined ) {
				dependenciesList.push( onHidden );
			}
		}
		return dependenciesList;
	}

	const dependencies = getDependencies();

	useEffect( () => {
		if ( isVisible && onVisible !== undefined ) {
			onVisible();
		} else if ( onHidden !== undefined ) {
			onHidden();
		}
	}, [ isVisible, ...dependencies ] );
}
