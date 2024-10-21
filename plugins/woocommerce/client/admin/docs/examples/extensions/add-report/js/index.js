/**
 * External dependencies
 */

import { addFilter } from '@wordpress/hooks';
import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * WooCommerce dependencies
 */
import { ReportFilters, TableCard } from '@woocommerce/components';

const Report = ( { path, query } ) => {
	return (
		<Fragment>
			<ReportFilters
				query={ query }
				path={ path }
				filters={ [] }
				advancedFilters={ {} }
			/>
			<TableCard
				title="Apples"
				headers={ [
					{ label: 'Type', key: 'type', isLeftAligned: true, required: true },
					{ label: 'Color', key: 'color' },
					{ label: 'Taste', key: 'taste' },
				] }
				rows={ [
					[
						{ display: 'Granny Smith', value: 'type' },
						{ display: 'Green', value: 'color' },
						{ display: 'Tangy and sweet', value: 'taste' },
					],
					[
						{ display: 'Golden Delicious', value: 'type' },
						{ display: 'Gold', value: 'color' },
						{ display: 'Sweet and cheery', value: 'taste' },
					],
					[
						{ display: 'Gala', value: 'type' },
						{ display: 'Red', value: 'color' },
						{ display: 'Mild, sweet and crisp', value: 'taste' },
					],
					[
						{ display: 'Braeburn', value: 'type' },
						{ display: 'Red', value: 'color' },
						{ display: 'Firm, crisp and pleasing', value: 'taste' },
					],
				] }
				rowsPerPage={ 100 }
				totalRows={ 1 }
			/>
		</Fragment>
	);
};

/**
 * Use the 'woocommerce_admin_reports_list' filter to add a report page.
 */
addFilter( 'woocommerce_admin_reports_list', 'plugin-domain', ( reports ) => {
	return [
		...reports,
		{
			report: 'example',
			title: __( 'Example', 'plugin-domain' ),
			component: Report,
		},
	];
} );
