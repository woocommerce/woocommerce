/**
 * External dependencies
 */
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { ONBOARDING_STORE_NAME } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useCallback } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';

export type TaskProps = {
	query: { task: string };
};

export const Task: React.FC< TaskProps > = ( { query } ) => {
	const id = query.task;
	const {
		invalidateResolutionForStoreSelector,
		optimisticallyCompleteTask,
	} = useDispatch( ONBOARDING_STORE_NAME );

	const onComplete = useCallback( () => {
		optimisticallyCompleteTask( id );
		getHistory().push( getNewPath( {}, '/', {} ) );
		invalidateResolutionForStoreSelector( 'getTaskLists' );
	}, [ id ] );

	return (
		<WooOnboardingTask.Slot id={ id } fillProps={ { onComplete, query } } />
	);
};
