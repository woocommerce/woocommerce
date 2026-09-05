/**
 * Internal dependencies
 */
import { TaskLists } from '~/task-lists';

type QueryTypeProps = {
	query: {
		task?: string;
	};
};

export const SetupTasksPanel = ( { query }: QueryTypeProps ) => {
	return (
		<div className="woocommerce-setup-panel">
			<TaskLists query={ query } />
		</div>
	);
};

export default SetupTasksPanel;
