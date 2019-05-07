/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

function HistoricalDataProgress( { label, progress, total } ) {
	return (
		<div className="woocommerce-settings-historical-data__progress">
			<span className="woocommerce-settings-historical-data__progress-label">
				{ sprintf( __( 'Imported %(label)s', 'woocommerce-admin' ), {
					label,
				} ) +
					' ' +
					sprintf( __( '%(progress)s of %(total)s', 'woocommerce-admin' ), {
						progress,
						total,
					} ) }
			</span>
			<progress
				className="woocommerce-settings-historical-data__progress-bar"
				max={ total }
				value={ progress }
			/>
		</div>
	);
}

export default HistoricalDataProgress;
