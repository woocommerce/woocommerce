export default [
	{
		label: 'Click & Collect',
		value: 'collect',
		schedule: 'Pickup between 12:00 - 16:00 (Mon-Fri)',
		price: 'FREE',
	},
	{
		label: 'Regular shipping',
		value: 'usps-normal',
		dispatcher: 'Dispatched via USPS',
		price: '€10.00',
		schedule: '5 business days',
	},
	{
		label: 'Express shipping',
		value: 'ups-express',
		dispatcher: 'Dispatched via USPS',
		price: '€50.00',
		schedule: '2 business days',
	},
];
