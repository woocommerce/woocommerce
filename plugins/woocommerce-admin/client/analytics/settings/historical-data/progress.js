/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { isNil } from 'lodash';

function HistoricalDataProgress( { label, progress, total } ) {
	const labelText = sprintf( __( 'Imported %(label)s', 'woocommerce-admin' ), {
		label,
	} );

	const labelCounters =
		! isNil( progress ) && ! isNil( total )
			? sprintf( __( '%(progress)s of %(total)s', 'woocommerce-admin' ), {
					progress,
					total,
				} )
			: null;

	return (
		<div className="woocommerce-settings-historical-data__progress">
			<span className="woocommerce-settings-historical-data__progress-label">{ labelText }</span>
			{ labelCounters && (
				<span className="woocommerce-settings-historical-data__progress-label">
					{ labelCounters }
				</span>
			) }
			<progress
				className="woocommerce-settings-historical-data__progress-bar"
				max={ total }
				value={ progress }
			/>
		</div>
	);
}

export default HistoricalDataProgress;
