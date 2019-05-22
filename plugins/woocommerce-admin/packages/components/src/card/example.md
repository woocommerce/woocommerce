```jsx
import { Card } from '@woocommerce/components';

const MyCard = () => (
	<div>
		<Card title={ "Store Performance" } description={ "Key performance metrics" }>
			<p>Your stuff in a Card.</p>
		</Card>
		<Card title={ "Inactive Card" } isInactive={ true }>
			<p>This Card is grayed out and has no box-shadow.</p>
		</Card>
	</div>
);
```
