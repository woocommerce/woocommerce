```jsx
import { OrderStatus } from '@woocommerce/components';

const MyOrderStatus = () => (
	<div>
		<OrderStatus order={ { status: 'processing' } } />
		<OrderStatus order={ { status: 'pending' } } />
		<OrderStatus order={ { status: 'completed' } } />
	</div>
);
```
