/**
 * External dependencies
 */

import { addFilter } from '@wordpress/hooks';
import { Rating } from '@woocommerce/components';

addFilter(
	'woocommerce_admin_report_table',
	'plugin-domain',
	( reportTableData ) => {
		if ( reportTableData.endpoint !== 'products' ) {
			return reportTableData;
		}

		reportTableData.headers = [
			...reportTableData.headers,
			{
				label: 'ID',
				key: 'product_id',
			},
			{
				label: 'Rating',
				key: 'product_rating',
			},
		];

		if (
			! reportTableData.items ||
			! reportTableData.items.data ||
			! reportTableData.items.data.length
		) {
			return reportTableData;
		}

		const newRows = reportTableData.rows.map( ( row, index ) => {
			const product = reportTableData.items.data[ index ];
			const newRow = [
				...row,
				// product_id is already returned in the response for productData.
				{
					display: product.product_id,
					value: product.product_id,
				},
				// average_rating can be found on extended_info on productData.
				{
					display: (
						<Rating
							rating={ Number(
								product.extended_info.average_rating
							) }
							totalStars={ 5 }
						/>
					),
					value: product.extended_info.average_rating,
				},
			];
			return newRow;
		} );

		reportTableData.rows = newRows;

		return reportTableData;
	}
);
