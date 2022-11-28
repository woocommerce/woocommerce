/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

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
	// Using unmounting as a way to see when the react router changes.
	useEffect( () => {
		return () => {
			if ( hasUnsavedChanges ) {
				addExitPage( pageId );
			}
		};
	}, [ hasUnsavedChanges ] );

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
