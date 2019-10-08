/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';

const initiateReportExport = operations => async ( reportType, reportTitle, reportArgs ) => {
	const { createNotice } = dispatch( 'core/notices' );

	const resourceName = getResourceName( `report-export-${ reportType }`, reportArgs );

	const result = await operations.update( [ resourceName ], {
		[ resourceName ]: reportArgs,
	} );

	const response = result[ 0 ][ resourceName ];

	if ( response && response.success ) {
		createNotice(
			'success',
			sprintf( __( 'Your %s Report will be emailed to you.', 'woocommerce-admin' ), reportTitle )
		);
	}
	if ( response && response.error ) {
		createNotice(
			'error',
			sprintf(
				__(
					'There was a problem exporting your %s Report. Please try again.',
					'woocommerce-admin'
				),
				reportTitle
			)
		);
	}
};

export default {
	initiateReportExport,
};
