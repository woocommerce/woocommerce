/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * WooCommerce dependencies
 */
import { ReportFilters } from '@woocommerce/components';

const Report = ( { path, query } ) => {
	return (
		<Fragment>
			<ReportFilters
				query={ query }
				path={ path }
				filters={ [] }
				advancedFilters={ {} }
			/>
		</Fragment>
	);
};

/**
 * Use the 'woocommerce_admin_reports_list' filter to add a report page.
 */
addFilter( 'woocommerce_admin_reports_list', 'plugin-domain', reports => {
	return [
		...reports,
		{
			report: 'example',
			title: __( 'Example', 'plugin-domain' ),
			component: Report,
		},
	];
} );
