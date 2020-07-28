/**
 * External dependencies
 */
import { SummaryList, SummaryNumber } from '@woocommerce/components';

export default () => (
	<SummaryList>
		{ () => {
			return [
				<SummaryNumber
					key="revenue"
					value={ '$829.40' }
					label="Total Sales"
					delta={ 29 }
					href="/analytics/report"
				/>,
				<SummaryNumber
					key="refunds"
					value={ '$24.00' }
					label="Refunds"
					delta={ -10 }
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
