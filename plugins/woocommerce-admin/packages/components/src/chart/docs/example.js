/** @format */
/**
 * Internal dependencies
 */
import { Chart } from '@woocommerce/components';

const data = [
	{
		date: '2018-05-30T00:00:00',
		Hoodie: {
			label: 'Hoodie',
			value: 21599,
		},
		Sunglasses: {
			label: 'Sunglasses',
			value: 38537,
		},
		Cap: {
			label: 'Cap',
			value: 106010,
		},
	},
	{
		date: '2018-05-31T00:00:00',
		Hoodie: {
			label: 'Hoodie',
			value: 14205,
		},
		Sunglasses: {
			label: 'Sunglasses',
			value: 24721,
		},
		Cap: {
			label: 'Cap',
			value: 70131,
		},
	},
	{
		date: '2018-06-01T00:00:00',
		Hoodie: {
			label: 'Hoodie',
			value: 10581,
		},
		Sunglasses: {
			label: 'Sunglasses',
			value: 19991,
		},
		Cap: {
			label: 'Cap',
			value: 53552,
		},
	},
	{
		date: '2018-06-02T00:00:00',
		Hoodie: {
			label: 'Hoodie',
			value: 9250,
		},
		Sunglasses: {
			label: 'Sunglasses',
			value: 16072,
		},
		Cap: {
			label: 'Cap',
			value: 47821,
		},
	},
];

export default () => (
	<div>
		<Chart data={ data } title="Example Chart" layout="item-comparison" />
	</div>
);
