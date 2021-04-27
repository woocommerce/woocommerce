/**
 * External dependencies
 */
import { cloneElement, useRef, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { PLUGINS_STORE_NAME, WCDataSelector } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { recordTaskViewEvent } from './tasks';

type TaskStepProps = {
	taskContainer?: React.ReactElement;
	query: { task?: string };
};

export const TaskStep: React.FC< TaskStepProps > = ( {
	taskContainer,
	query,
} ) => {
	const prevTaskRef = useRef< string >();
	const { isJetpackConnected, activePlugins, installedPlugins } = useSelect(
		( select: WCDataSelector ) => {
			const {
				getActivePlugins,
				getInstalledPlugins,
				isJetpackConnected: getIsJetpackConnected,
			} = select( PLUGINS_STORE_NAME );

			return {
				activePlugins: getActivePlugins(),
				isJetpackConnected: getIsJetpackConnected(),
				installedPlugins: getInstalledPlugins(),
			};
		}
	);

	const recordTaskView = () => {
		const { task: taskName } = query;

		if ( ! taskName ) {
			return;
		}

		recordTaskViewEvent(
			taskName,
			isJetpackConnected,
			activePlugins,
			installedPlugins
		);
	};

	useEffect( () => {
		const { task } = query;
		if ( prevTaskRef.current !== task ) {
			window.document.documentElement.scrollTop = 0;
		}
		prevTaskRef.current = task;
		recordTaskView();
	}, [ query ] );

	if ( ! taskContainer || ! query.task ) {
		return null;
	}
	return (
		<div className="woocommerce-task-dashboard__container">
			{ cloneElement( taskContainer, {
				query,
			} ) }
		</div>
	);
};
