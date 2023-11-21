/**
 * External dependencies
 */
import { useCommand } from '@wordpress/commands';
import { queueRecordEvent } from '@woocommerce/tracks';

export const useCommandWithTracking = ( { name, label, icon, callback } ) => {
	useCommand( {
		name,
		label,
		icon,
		callback: ( ...args ) => {
			queueRecordEvent( 'woocommerce_command_palette_submit', {
				name,
			} );

			callback( ...args );
		},
	} );
};
