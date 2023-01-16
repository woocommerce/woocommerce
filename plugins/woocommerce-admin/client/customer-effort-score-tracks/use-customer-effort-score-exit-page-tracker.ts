/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	addCustomerEffortScoreExitPageListener,
	addExitPage,
	removeCustomerEffortScoreExitPageListener,
} from './customer-effort-score-exit-page';

export const useCustomerEffortScoreExitPageTracker = (
	pageId: string,
	hasUnsavedChanges: boolean
) => {
	const hasUnsavedChangesRef = useRef( hasUnsavedChanges );

	// Using unmounting as a way to see when the react router changes.
	useEffect( () => {
		hasUnsavedChangesRef.current = hasUnsavedChanges;
	}, [ hasUnsavedChanges ] );

	useEffect( () => {
		return () => {
			if ( hasUnsavedChangesRef.current ) {
				// unmounted.
				addExitPage( pageId );
			}
		};
	}, [] );

	// This effect listen to the native beforeunload event to show
	// a confirmation message
	useEffect( () => {
		addCustomerEffortScoreExitPageListener(
			pageId,
			() => hasUnsavedChanges
		);

		return () => {
			removeCustomerEffortScoreExitPageListener( pageId );
		};
	}, [ hasUnsavedChanges ] );
};
