/**
 * Internal dependencies
 */
import { PaymentGateway } from '../types';

export const paymentGatewaysStub: PaymentGateway[] = [
	{
		id: 'bacs',
		title: 'direct bank',
		description: 'description',
		order: '',
		enabled: false,
		method_title: 'Direct bank transfer',
		method_description: 'method description',
		method_supports: [ 'products' ],
		settings: {
			id: 'title',
			label: 'Title',
			description:
				'This controls the title which the user sees during checkout.',
			type: 'text',
			value: 'direct bank',
			default: 'Direct bank transfer',
			tip: 'This controls the title which the user sees during checkout.',
			placeholder: '',
			is_dismissed: 'no',
		},
		settings_url: '',
	},
	{
		id: 'test',
		title: 'test',
		description: 'test',
		order: 0,
		enabled: false,
		method_title: 'test',
		method_description: 'method description',
		method_supports: [ 'products' ],
		settings: {
			id: 'title',
			label: 'Title',
			description:
				'This controls the title which the user sees during checkout.',
			type: 'text',
			value: 'direct bank',
			default: 'Direct bank transfer',
			tip: 'This controls the title which the user sees during checkout.',
			placeholder: '',
			is_dismissed: 'no',
		},
		settings_url: '',
	},
];
