/**
 * Internal dependencies
 */
import Tasks from '~/tasks';

export const SetupTasksPanel = ( { query } ) => {
	return (
		<div className="woocommerce-setup-panel">
			<Tasks query={ query } />
		</div>
	);
};

export default SetupTasksPanel;
