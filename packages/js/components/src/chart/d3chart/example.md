```jsx
import { D3Chart, D3Legend } from 'react-d3-chart';

const data =  [
	{
		date: '2018-05-30T00:00:00',
		Hoodie: { value: 21599 },
		Sunglasses: { value: 38537 },
		Cap: { value: 106010 },
	},
	{
		date: '2018-05-31T00:00:00',
		Hoodie: { value: 14205 },
		Sunglasses: { value: 24721 },
		Cap: { value: 70131 },
	},
	{
		date: '2018-06-01T00:00:00',
		Hoodie: { value: 10581 },
		Sunglasses: { value: 19991 },
		Cap: { value: 53552 },
	},
	{
		date: '2018-06-02T00:00:00',
		Hoodie: { value: 9250 },
		Sunglasses: { value: 16072 },
		Cap: { value: 47821 },
	},
];

const MyChart = () => (
	<div>
		<D3Chart data={ data } title="Example Chart" layout="item-comparison" />
	</div>
);
```
