/**
 * External dependencies
 */
import { SummaryList, SummaryNumber } from '@woocommerce/components';

export const Basic = () => (
	<SummaryList>
		{ () => {
			return [
				<SummaryNumber
					key="revenue"
					value={ '$829.40' }
					label="Total Sales"
					delta={ 29 }
					href="/analytics/report"
				>
					<span>27 orders</span>
				</SummaryNumber>,
				<SummaryNumber
					key="refunds"
					value={ '$24.00' }
					label="Refunds"
					delta={ -10.12 }
					href="/analytics/report"
					selected
				/>,
				<SummaryNumber
					key="coupons"
					value={ '$49.90' }
					label="Coupons"
					href="/analytics/report"
				/>,
			];
		} }
	</SummaryList>
);

export default {
	title: 'WooCommerce Admin/components/SummaryList',
	component: SummaryList,
};
