/**
 * External dependencies
 */
import {
	WooHeaderNavigationItem,
	WooHeaderPageTitle,
} from '@woocommerce/admin-layout';
import { WooOnboardingTask } from '@woocommerce/onboarding';
import { getHistory, getNewPath } from '@woocommerce/navigation';
import { ONBOARDING_STORE_NAME, TaskType } from '@woocommerce/data';
import { useCallback } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
/**
 * Internal dependencies
 */
import { BackButton } from './back-button';

export type TaskProps = {
	query: { task?: string };
	task: TaskType;
};

export const Task: React.FC< TaskProps > = ( { query, task } ) => {
	const id = query.task || '';
	if ( ! id ) {
		// eslint-disable-next-line no-console
		console.warn( 'No task id provided' );
		// eslint-enable-next-line no-console
	}

	const { invalidateResolutionForStoreSelector, optimisticallyCompleteTask } =
		useDispatch( ONBOARDING_STORE_NAME );

	const updateBadge = useCallback( () => {
		const badgeElements: Array< HTMLElement > | null = Array.from(
			document.querySelectorAll(
				'#adminmenu .woocommerce-task-list-remaining-tasks-badge'
			)
		);

		if ( ! badgeElements?.length ) {
			return;
		}

		badgeElements.forEach( ( badgeElement ) => {
			const currentBadgeCount = Number( badgeElement.innerText );

			if ( currentBadgeCount === 1 ) {
				badgeElement.remove();
			} else {
				badgeElement.innerHTML = String( currentBadgeCount - 1 );
			}
		} );
	}, [] );

	const onComplete = useCallback(
		( options ) => {
			optimisticallyCompleteTask( id );
			getHistory().push(
				options && options.redirectPath
					? options.redirectPath
					: getNewPath( {}, '/', {} )
			);
			invalidateResolutionForStoreSelector( 'getTaskLists' );
			updateBadge();
		},
		[ id ]
	);

	return (
		<>
			<WooHeaderNavigationItem>
				<BackButton title={ task.title } />
			</WooHeaderNavigationItem>
			<WooHeaderPageTitle>{ task.title }</WooHeaderPageTitle>
			<WooOnboardingTask.Slot
				id={ id }
				fillProps={ { onComplete, query, task } }
			/>
		</>
	);
};
