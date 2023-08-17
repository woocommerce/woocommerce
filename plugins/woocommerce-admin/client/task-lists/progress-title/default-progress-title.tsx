/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	getVisibleTasks,
	OnboardingSelectors,
	ONBOARDING_STORE_NAME,
} from '@woocommerce/data';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import sanitizeHTML from '../../lib/sanitize-html';

export type DefaultProgressTitleProps = {
	taskListId: string;
};

export const DefaultProgressTitle: React.FC< DefaultProgressTitleProps > = ( {
	taskListId,
} ) => {
	const { loading, tasksCount, completedCount, hasVisitedTasks } = useSelect(
		( select ) => {
			const { getTaskList, hasFinishedResolution }: OnboardingSelectors =
				select( ONBOARDING_STORE_NAME );
			const taskList = getTaskList( taskListId );
			const finishedResolution = hasFinishedResolution( 'getTaskList', [
				taskListId,
			] );
			const visibleTasks = getVisibleTasks( taskList?.tasks || [] );

			return {
				loading: ! finishedResolution,
				tasksCount: visibleTasks?.length,
				completedCount: visibleTasks?.filter(
					( task ) => task.isComplete
				).length,
				hasVisitedTasks:
					visibleTasks?.filter(
						( task ) =>
							task.isVisited && task.id !== 'store_details'
					).length > 0,
			};
		},
		[]
	);

	const title = useMemo( () => {
		if ( ! hasVisitedTasks || completedCount === tasksCount ) {
			const siteTitle = getSetting( 'siteTitle' );
			return siteTitle
				? sprintf(
						/* translators: %s = site title */
						__( 'Welcome to %s', 'woocommerce' ),
						siteTitle
				  )
				: __( 'Welcome to your store', 'woocommerce' );
		}
		if ( completedCount <= 3 ) {
			return __( "Let's get you started", 'woocommerce' ) + '   🚀';
		}
		if ( completedCount > 3 && completedCount < 6 ) {
			return __( 'You are on the right track', 'woocommerce' );
		}
		return __( 'You are almost there', 'woocommerce' );
	}, [ completedCount, hasVisitedTasks, tasksCount ] );

	if ( loading ) {
		return null;
	}

	return (
		<h1
			className="woocommerce-task-progress-header__title"
			dangerouslySetInnerHTML={ sanitizeHTML( title ) }
		/>
	);
};
