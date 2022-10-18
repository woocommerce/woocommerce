/**
 * Internal dependencies
 */
import Chart from '../';

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
		Tshirt: {
			label: 'Tshirt',
			value: 26784,
		},
		Jeans: {
			label: 'Jeans',
			value: 35645,
		},
		Headphones: {
			label: 'Headphones',
			value: 19500,
		},
		Lamp: {
			label: 'Lamp',
			value: 21599,
		},
		Socks: {
			label: 'Socks',
			value: 32572,
		},
		Mug: {
			label: 'Mug',
			value: 10991,
		},
		Case: {
			label: 'Case',
			value: 35537,
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
		Tshirt: {
			label: 'Tshirt',
			value: 16784,
		},
		Jeans: {
			label: 'Jeans',
			value: 25645,
		},
		Headphones: {
			label: 'Headphones',
			value: 39500,
		},
		Lamp: {
			label: 'Lamp',
			value: 15599,
		},
		Socks: {
			label: 'Socks',
			value: 27572,
		},
		Mug: {
			label: 'Mug',
			value: 110991,
		},
		Case: {
			label: 'Case',
			value: 21537,
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
		Tshirt: {
			label: 'Tshirt',
			value: 41784,
		},
		Jeans: {
			label: 'Jeans',
			value: 17645,
		},
		Headphones: {
			label: 'Headphones',
			value: 22500,
		},
		Lamp: {
			label: 'Lamp',
			value: 25599,
		},
		Socks: {
			label: 'Socks',
			value: 14572,
		},
		Mug: {
			label: 'Mug',
			value: 20991,
		},
		Case: {
			label: 'Case',
			value: 11537,
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
		Tshirt: {
			label: 'Tshirt',
			value: 18784,
		},
		Jeans: {
			label: 'Jeans',
			value: 29645,
		},
		Headphones: {
			label: 'Headphones',
			value: 24500,
		},
		Lamp: {
			label: 'Lamp',
			value: 18599,
		},
		Socks: {
			label: 'Socks',
			value: 23572,
		},
		Mug: {
			label: 'Mug',
			value: 20991,
		},
		Case: {
			label: 'Case',
			value: 16537,
		},
	},
];

export default {
	title: 'WooCommerce Admin/components/Chart',
	component: Chart,
	args: {
		legendPosition: undefined,
	},
	argTypes: {
		legendPosition: {
			control: { type: 'select' },
			options: [ undefined, 'bottom', 'side', 'top', 'hidden' ],
		},
	},
};

export const Default = ( { legendPosition } ) => (
	<Chart data={ data } legendPosition={ legendPosition } />
);
