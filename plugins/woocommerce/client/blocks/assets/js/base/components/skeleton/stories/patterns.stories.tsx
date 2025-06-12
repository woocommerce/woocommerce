/**
 * External dependencies
 */
import { Meta, StoryObj } from '@storybook/react';

/**
 * Internal dependencies
 */
import { Skeleton, SkeletonProps } from '../';
import { ProductShortDescriptionSkeleton } from '../patterns/product-short-description';
import { CartExpressPaymentsSkeleton } from '../patterns/cart-express-payments';
import { CartLineItemsSkeleton } from '../patterns/cart-line-items';
import { CartOrderSummarySkeleton } from '../patterns/cart-order-summary';
import { CheckoutExpressPaymentsSkeleton } from '../patterns/checkout-express-payments';
import { CheckoutContactSkeleton } from '../patterns/checkout-contact';
import { CheckoutDeliverySkeleton } from '../patterns/checkout-delivery';
import { CheckoutShippingSkeleton } from '../patterns/checkout-shipping';
import { CheckoutPaymentSkeleton } from '../patterns/checkout-payment';
import { CheckoutOrderSummarySkeleton } from '../patterns/checkout-order-summary';
import { CheckoutOrderSummaryMobileSkeleton } from '../patterns/checkout-order-summary-mobile';

export default {
	title: 'Base Components/Skeleton/Patterns',
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
		isStatic: {
			control: 'boolean',
			defaultValue: false,
		},
	},
	parameters: {
		docs: {
			description: {
				component:
					'Pattern skeletons are reusable structures built from base skeletons for common UI patterns.',
			},
		},
	},
} as Meta< SkeletonProps >;

export const ProductShortDescriptionSkeletonStory: StoryObj = {
	render: ( args ) => <ProductShortDescriptionSkeleton { ...args } />,
	storyName: 'Product Short Description skeleton',
	parameters: {
		docs: {
			source: {
				code: '<ProductShortDescriptionSkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the product short description.',
			},
		},
	},
};

export const CartLineItemsSkeletonStory: StoryObj = {
	render: () => <CartLineItemsSkeleton />,
	storyName: 'Cart Line Items skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CartLineItemsSkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Cart Line Items section on the Cart block.',
			},
		},
	},
};

export const CartOrderSummarySkeletonStory: StoryObj = {
	render: () => <CartOrderSummarySkeleton />,
	storyName: 'Cart Order Summary skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CartOrderSummarySkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Order Summary section on the Cart block.',
			},
		},
	},
};

export const CartExpressPaymentsSkeletonStory: StoryObj = {
	render: () => <CartExpressPaymentsSkeleton />,
	storyName: 'Cart Express Payments skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CartExpressPayments />',
			},
			description: {
				story: 'The skeleton pattern for the Express Payments section on the Cart block.',
			},
		},
	},
};

export const CheckoutExpressPaymentsPrimarySkeletonStory: StoryObj = {
	render: () => <CheckoutExpressPaymentsSkeleton />,
	storyName: 'Checkout Express Payments skeleton (primary)',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutExpressPaymentsSkeleton />',
			},
			description: {
				story: 'The primary skeleton pattern for the Express Payments section on the Checkout block.',
			},
		},
	},
};

export const CheckoutExpressPaymentsSecondarySkeletonStory: StoryObj< {
	label: string;
} > = {
	render: () => <CheckoutExpressPaymentsSkeleton showLabels={ true } />,
	storyName: 'Checkout Express Payments Skeleton (secondary)',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutExpressPaymentsSkeleton showLabels={ true } />',
			},
			description: {
				story: 'The secondary skeleton pattern for the Express Payments section on the Checkout block.',
			},
		},
	},
};

export const CheckoutContactSkeletonStory: StoryObj = {
	render: () => <CheckoutContactSkeleton />,
	storyName: 'Checkout Contact skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutContactSkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Contact section on the Checkout block.',
			},
		},
	},
};

export const CheckoutDeliverySkeletonStory: StoryObj = {
	render: () => <CheckoutDeliverySkeleton />,
	storyName: 'Checkout Delivery skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutDeliverySkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Delivery section on the Checkout block.',
			},
		},
	},
};

export const CheckoutShippingSkeletonStory: StoryObj = {
	render: () => <CheckoutShippingSkeleton />,
	storyName: 'Checkout Shipping Skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutShippingSkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Shipping section on the Checkout block.',
			},
		},
	},
};

export const CheckoutPaymentSkeletonStory: StoryObj = {
	render: () => <CheckoutPaymentSkeleton />,
	storyName: 'Checkout Payment skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutPaymentSkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Payment section on the Checkout block.',
			},
		},
	},
};

export const CheckoutOrderSummarySkeletonStory: StoryObj = {
	render: () => <CheckoutOrderSummarySkeleton />,
	storyName: 'Checkout Order Summary skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutOrderSummarySkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Order Summary section on the Checkout block.',
			},
		},
	},
};

export const CheckoutOrderSummaryMobileSkeletonStory: StoryObj = {
	render: () => <CheckoutOrderSummaryMobileSkeleton />,
	storyName: 'Checkout Order Summary Mobile skeleton',
	parameters: {
		docs: {
			source: {
				code: '<CheckoutOrderSummaryMobileSkeleton />',
			},
			description: {
				story: 'The skeleton pattern for the Order Summary section on the Checkout block on mobile.',
			},
		},
	},
};
