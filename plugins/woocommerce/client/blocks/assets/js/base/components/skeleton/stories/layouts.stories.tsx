/**
 * External dependencies
 */
import { Meta, StoryObj } from '@storybook/react';

/**
 * Internal dependencies
 */
import { Skeleton, SkeletonProps } from '../';
import { CartSkeleton } from '../layouts/cart';
import { CartFullSkeleton } from '../layouts/cart-full';
import { CheckoutSkeleton } from '../layouts/checkout';
import { CheckoutFullSkeleton } from '../layouts/checkout-full';

export default {
	title: 'Base Components/Skeleton/Layouts',
	component: Skeleton,
	argTypes: {
		width: { control: 'text' },
		height: { control: 'text' },
		borderRadius: { control: 'text' },
		className: { control: 'text' },
		tag: {
			control: { type: 'select' },
			options: [ 'div' ],
		},
	},
	parameters: {
		docs: {
			description: {
				component:
					'Layout skeletons compose pattern skeletons into full-page or complex layouts.',
			},
		},
	},
} as Meta< SkeletonProps >;

export const CartSkeletonStory: StoryObj = {
	render: () => <CartSkeleton />,
	storyName: 'Cart skeleton excluding express payments',
	parameters: {
		docs: {
			source: {
				code: '<CartSkeleton />',
			},
			description: {
				story: 'The skeleton for the Cart block without express payments.',
			},
		},
	},
};

export const CartFullSkeletonStory: StoryObj = {
	render: () => <CartFullSkeleton />,
	storyName: 'Cart full skeleton including express payments',
	parameters: {
		docs: {
			source: {
				code: '<CartFullSkeleton />',
			},
			description: {
				story: 'The skeleton for the Cart full layout with express payments.',
			},
		},
	},
};

export const CheckoutSkeletonStory: StoryObj = {
	render: () => <CheckoutSkeleton />,
	storyName: 'Checkout skeleton excluding express payments',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutSkeleton />',
			},
			description: {
				story: 'The skeleton for the Checkout block without express payments.',
			},
		},
	},
};

export const CheckoutFullSkeletonStory: StoryObj = {
	render: () => <CheckoutFullSkeleton />,
	storyName: 'Checkout full skeleton including express payments',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutFullSkeleton />',
			},
			description: {
				story: 'The skeleton for the Checkout full layout with express payments.',
			},
		},
	},
};
