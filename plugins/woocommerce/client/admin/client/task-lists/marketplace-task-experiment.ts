/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import {
	dangerouslyGetExperimentAssignment,
	loadExperimentAssignment,
} from '@woocommerce/explat';
import type { TaskListType } from '@woocommerce/data';

export const MARKETPLACE_TASK_EXPERIMENT_NAME =
	'woocommerce_marketplace_task_202611';

const CONTROL = 'control';
const COPY_PAYMENTS_SHIPPING_MARKETING = 'copy_payments_shipping_marketing';
const COPY_FREE_AND_PAID = 'copy_free_and_paid';
const FIRST_POSITION = 'first_position';

type Variation =
	| typeof CONTROL
	| typeof COPY_PAYMENTS_SHIPPING_MARKETING
	| typeof COPY_FREE_AND_PAID
	| typeof FIRST_POSITION;

const TASK_LIST_ID = 'extended';
const TASK_ID = 'extend-store';

// 2027-03-01 00:00 UTC. No assignment is requested after this.
const END_TIMESTAMP = Date.UTC( 2027, 2, 1 );

// The ExPlat client waits 5 or 10 seconds before it falls back, too long to hold the home screen list.
export const ASSIGNMENT_TIMEOUT_MS = 3000;

const toVariation = ( variationName: string | null ): Variation => {
	switch ( variationName ) {
		case COPY_PAYMENTS_SHIPPING_MARKETING:
		case COPY_FREE_AND_PAID:
		case FIRST_POSITION:
			return variationName;
		default:
			return CONTROL;
	}
};

// The Marketplace task when `taskList` is the extended list.
const findMarketplaceTask = ( taskList: TaskListType | undefined ) =>
	taskList?.id === TASK_LIST_ID
		? taskList.tasks.find( ( { id } ) => id === TASK_ID )
		: undefined;

/**
 * Whether the test is running and the visible extended list renders the Marketplace task, so an assignment logs a real exposure.
 */
const isMarketplaceTaskInTest = ( taskLists: TaskListType[] ): boolean => {
	if ( ! window.wcTracks?.isEnabled || Date.now() >= END_TIMESTAMP ) {
		return false;
	}

	const taskList = taskLists.find( ( { id } ) => id === TASK_LIST_ID );
	const task = taskList?.isVisible
		? findMarketplaceTask( taskList )
		: undefined;

	// Same rule TaskList uses to render tasks in an extended list (getVisibleTasks drops dismissed ones).
	return !! task && ! task.isComplete && ! task.isDismissed;
};

/**
 * Apply the variation's title or position to the Marketplace task. Other lists are returned as they are.
 */
export const applyMarketplaceTaskVariation = (
	taskList: TaskListType,
	variation: Variation
): TaskListType => {
	const task = findMarketplaceTask( taskList );

	if ( ! task || variation === CONTROL ) {
		return taskList;
	}

	if ( variation === FIRST_POSITION ) {
		return {
			...taskList,
			tasks: [ task, ...taskList.tasks.filter( ( t ) => t !== task ) ],
		};
	}

	const title =
		variation === COPY_PAYMENTS_SHIPPING_MARKETING
			? __(
					'Add payments, shipping and marketing extensions',
					'woocommerce'
			  )
			: __( 'Browse free and paid extensions', 'woocommerce' );

	return {
		...taskList,
		tasks: taskList.tasks.map( ( t ) =>
			t === task ? { ...t, title } : t
		),
	};
};

type CachedAssignment = { variationName: string | null; isAlive: boolean };

/**
 * This browser's stored assignment, read without a request; null when nothing is stored.
 * The client returns expired entries as they are, and a fallback (also logged in development builds) on a miss.
 */
const readCachedAssignment = (): CachedAssignment | null => {
	const assignment = dangerouslyGetExperimentAssignment(
		MARKETPLACE_TASK_EXPERIMENT_NAME
	);
	if ( assignment.isFallbackExperimentAssignment ) {
		return null;
	}

	return {
		variationName: assignment.variationName,
		isAlive:
			Date.now() < assignment.retrievedTimestamp + assignment.ttl * 1000,
	};
};

/**
 * Variation for the Marketplace task, decided once per mount when the task is first shown: a live stored assignment
 * right away, otherwise an ExPlat request. The variation then sticks for the mount, so dismissing the task doesn't
 * reorder the list.
 */
export const useMarketplaceTaskVariation = ( {
	taskLists,
	isReady,
}: {
	taskLists: TaskListType[];
	isReady: boolean;
} ): { isLoading: boolean; variation: Variation } => {
	const [ state, setState ] = useState< Variation | 'requesting' | null >(
		null
	);

	if ( state === null && isReady && isMarketplaceTaskInTest( taskLists ) ) {
		const cached = readCachedAssignment();
		setState(
			cached?.isAlive ? toVariation( cached.variationName ) : 'requesting'
		);
	}

	const isRequesting = state === 'requesting';

	useEffect( () => {
		if ( ! isRequesting ) {
			return;
		}

		let isSettled = false;
		const settle = ( result: Variation ) => {
			if ( ! isSettled ) {
				isSettled = true;
				setState( result );
			}
		};
		const timeoutId = setTimeout(
			() => settle( CONTROL ),
			ASSIGNMENT_TIMEOUT_MS
		);

		loadExperimentAssignment( MARKETPLACE_TASK_EXPERIMENT_NAME )
			.then( ( { variationName } ) =>
				settle( toVariation( variationName ) )
			)
			.catch( () => settle( CONTROL ) );

		return () => {
			isSettled = true;
			clearTimeout( timeoutId );
		};
	}, [ isRequesting ] );

	return {
		isLoading: isRequesting,
		variation: state === null || isRequesting ? CONTROL : state,
	};
};
