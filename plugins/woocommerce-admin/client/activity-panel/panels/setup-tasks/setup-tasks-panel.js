/**
 * External dependencies
 */
import {
	getQuery,
	onQueryChange,
	addHistoryListener,
} from '@woocommerce/navigation';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Tasks from '~/tasks';

export const SetupTasksPanel = ( { query } ) => {
	console.debug( 'query', query );

	return (
		<div className="woocommerce-setup-panel">
			<Tasks query={ query } />
		</div>
	);
};

export default SetupTasksPanel;
