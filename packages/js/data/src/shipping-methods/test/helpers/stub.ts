/**
 * Internal dependencies
 */
import { ShippingMethod } from '../../types';

export const shippingMethodsStub: ShippingMethod[] = [
	{
		name: 'Dummy shipping method',
		slug: 'dummy-shipping-method',
		description: 'Dummy shipping method description',
		learn_more_link: 'https://example.com',
		is_visible: true,
		available_layouts: [ 'row' ],
		layout_row: {
			image: 'https://example.com/image.png',
			features: [
				{
					icon: 'https://example.com/icon.png',
					title: 'Feature title',
					description: 'Feature description',
				},
				{
					icon: 'https://example.com/icon.png',
					title: 'Feature title 2',
					description: 'Feature description 2',
				},
			],
		},
	},
	{
		name: 'Dummy shipping method 2',
		slug: 'dummy-shipping-method-2',
		description: 'Dummy shipping method description',
		learn_more_link: 'https://example.com',
		is_visible: true,
		available_layouts: [ 'column' ],
		layout_column: {
			image: 'https://example.com/image.png',
			features: [
				{
					icon: 'https://example.com/icon.png',
					title: 'Feature title',
					description: 'Feature description',
				},
			],
		},
		dependencies: [ 'jetpack' ],
	},
];
