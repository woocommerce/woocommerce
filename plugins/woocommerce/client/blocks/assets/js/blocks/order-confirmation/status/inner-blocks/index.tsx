/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration, TemplateArray } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import orderReceivedMetadata from './order-confirmation-status-order-received/block.json';
import cancelledMetadata from './order-confirmation-status-cancelled/block.json';
import refundedMetadata from './order-confirmation-status-refunded/block.json';
import completedMetadata from './order-confirmation-status-completed/block.json';
import failedMetadata from './order-confirmation-status-failed/block.json';
import { Edit, Save } from './edit';

export const ORDER_RECEIVED_STATUS_BLOCK =
	'woocommerce/order-confirmation-status-order-received';
export const CANCELLED_STATUS_BLOCK =
	'woocommerce/order-confirmation-status-cancelled';
export const REFUNDED_STATUS_BLOCK =
	'woocommerce/order-confirmation-status-refunded';
export const COMPLETED_STATUS_BLOCK =
	'woocommerce/order-confirmation-status-completed';
export const FAILED_STATUS_BLOCK =
	'woocommerce/order-confirmation-status-failed';

export const ORDER_STATUS_BLOCKS = [
	ORDER_RECEIVED_STATUS_BLOCK,
	CANCELLED_STATUS_BLOCK,
	REFUNDED_STATUS_BLOCK,
	COMPLETED_STATUS_BLOCK,
	FAILED_STATUS_BLOCK,
];

const orderReceivedTemplate: TemplateArray = [
	[
		'core/heading',
		{ content: __( 'Order received', 'woocommerce' ), level: 1 },
	],
	[
		'core/paragraph',
		{
			content: __(
				'Thank you. Your order has been received',
				'woocommerce'
			),
		},
	],
];

const cancelledTemplate: TemplateArray = [
	[
		'core/heading',
		{ content: __( 'Order cancelled', 'woocommerce' ), level: 1 },
	],
	[
		'core/paragraph',
		{
			content: __( 'Your order has been cancelled.', 'woocommerce' ),
		},
	],
];

const refundedTemplate: TemplateArray = [
	[
		'core/heading',
		{ content: __( 'Order refunded', 'woocommerce' ), level: 1 },
	],
	[
		'core/paragraph',
		{ content: __( 'Your order was refunded', 'woocommerce' ) },
	],
];

const completedTemplate: TemplateArray = [
	[
		'core/heading',
		{ content: __( 'Order completed', 'woocommerce' ), level: 1 },
	],
	[
		'core/paragraph',
		{
			content: __(
				'Thank you. Your order has been fulfilled.',
				'woocommerce'
			),
		},
	],
];

const failedTemplate: TemplateArray = [
	[
		'core/heading',
		{ content: __( 'Order failed', 'woocommerce' ), level: 1 },
	],
	[
		'core/paragraph',
		{
			content: __(
				'Your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again',
				'woocommerce'
			),
		},
	],
];

export const ORDER_STATUS_TEMPLATE = [
	[ ORDER_RECEIVED_STATUS_BLOCK, {}, orderReceivedTemplate ],
	[ CANCELLED_STATUS_BLOCK, {}, cancelledTemplate ],
	[ REFUNDED_STATUS_BLOCK, {}, refundedTemplate ],
	[ COMPLETED_STATUS_BLOCK, {}, completedTemplate ],
	[ FAILED_STATUS_BLOCK, {}, failedTemplate ],
] as TemplateArray;

export const registerOrderStatusBlocks = (): void => {
	registerBlockType( orderReceivedMetadata.name, {
		...orderReceivedMetadata,
		edit: () => (
			<Edit
				view={ ORDER_RECEIVED_STATUS_BLOCK }
				template={ orderReceivedTemplate }
			/>
		),
		save: Save,
	} as unknown as BlockConfiguration );
	registerBlockType( cancelledMetadata.name, {
		...cancelledMetadata,
		edit: () => (
			<Edit
				view={ CANCELLED_STATUS_BLOCK }
				template={ cancelledTemplate }
			/>
		),
		save: Save,
	} as unknown as BlockConfiguration );
	registerBlockType( refundedMetadata.name, {
		...refundedMetadata,
		edit: () => (
			<Edit
				view={ REFUNDED_STATUS_BLOCK }
				template={ refundedTemplate }
			/>
		),
		save: Save,
	} as unknown as BlockConfiguration );
	registerBlockType( completedMetadata.name, {
		...completedMetadata,
		edit: () => (
			<Edit
				view={ COMPLETED_STATUS_BLOCK }
				template={ completedTemplate }
			/>
		),
		save: Save,
	} as unknown as BlockConfiguration );
	registerBlockType( failedMetadata.name, {
		...failedMetadata,
		edit: () => (
			<Edit view={ FAILED_STATUS_BLOCK } template={ failedTemplate } />
		),
		save: Save,
	} as unknown as BlockConfiguration );
};
