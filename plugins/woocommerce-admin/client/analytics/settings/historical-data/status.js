/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import moment from 'moment';
import { Spinner } from '@wordpress/components';

/**
 * WooCommerce dependencies
 */
import { useFilters } from '@woocommerce/components';

const HISTORICAL_DATA_STATUS_FILTER = 'woocommerce_admin_import_status';

function HistoricalDataStatus( { importDate, status } ) {
	const statusLabels = applyFilters( HISTORICAL_DATA_STATUS_FILTER, {
		ready: __( 'Ready To Import', 'woocommerce-admin' ),
		customers: [ __( 'Importing Customers', 'woocommerce-admin' ), <Spinner key="spinner" /> ],
		orders: [ __( 'Importing Orders', 'woocommerce-admin' ), <Spinner key="spinner" /> ],
		finalizing: [ __( 'Finalizing', 'woocommerce-admin' ), <Spinner key="spinner" /> ],
		finished: sprintf(
			__( 'Historical data from %s onward imported', 'woocommerce-admin' ),
			moment( importDate ).format( 'll' )
		),
	} );

	return (
		<span className="woocommerce-settings-historical-data__status">
			{ __( 'Status:', 'woocommerce-admin' ) + ' ' }
			{ statusLabels[ status ] }
		</span>
	);
}

export default useFilters( HISTORICAL_DATA_STATUS_FILTER )( HistoricalDataStatus );
