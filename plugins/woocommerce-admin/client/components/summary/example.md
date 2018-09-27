```jsx
import { SummaryList, SummaryNumber } from '@woocommerce/components';

const MySummaryList = () => (
	<SummaryList>
		<SummaryNumber
			value={ '$829.40' }
			label="Gross Revenue"
			delta={ 29 }
			href="/analytics/report"
		/>
		<SummaryNumber
			value={ '$24.00' }
			label="Refunds"
			delta={ -10 }
			href="/analytics/report"
			selected
		/>
		<SummaryNumber
			value={ '$49.90' }
			label="Coupons"
			href="/analytics/report"
		/>
	</SummaryList>
);
```
