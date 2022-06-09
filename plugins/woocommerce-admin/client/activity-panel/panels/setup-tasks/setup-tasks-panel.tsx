/**
 * Internal dependencies
 */
import { Tasks } from '~/tasks';

type QueryTypeProps = {
	query: {
		task?: string;
	};
};

export const SetupTasksPanel = ( { query }: QueryTypeProps ) => {
	return (
		<div className="woocommerce-setup-panel">
			<Tasks query={ query } />
		</div>
	);
};

export default SetupTasksPanel;
