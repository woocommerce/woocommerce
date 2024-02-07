/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Flex } from '@wordpress/components';
import React from 'react';

/**
 * Internal dependencies
 */
import { AdviceCard, AdviceCardProps } from '..';
import { ShoppingBags } from '../../../images/shopping-bags';
import { Shirt } from '../../../images/shirt';
import { Pants } from '../../../images/pants';
import { Glasses } from '../../../images/glasses';

export default {
	title: 'Product Editor/components/AdviceCard',
	component: AdviceCard,
};

export const Default = ( args: AdviceCardProps ) => <AdviceCard { ...args } />;

Default.args = {
	// @todo: use an addon
	onDismiss: console.log, // eslint-disable-line no-console

	tip: __(
		'Tip: Upsells are typically products that are extra profitable or better quality or more expensive. Experiment with combinations to boost sales.',
		'woocommerce'
	),
	children: <ShoppingBags />,
	isDismissible: true,
};

export const MultipleChildren = ( args: AdviceCardProps ) => (
	<AdviceCard { ...args } />
);

MultipleChildren.args = {
	tip: __(
		'Tip: Group together items that have a clear relationship or compliment each other well, e.g., garment bundles, camera kits, or skincare product sets.',
		'woocommerce'
	),

	children: (
		<Flex justify="center" style={ { gap: '16px ' } }>
			<Shirt />
			<Pants />
			<Glasses />
		</Flex>
	),
	isDismissible: false,
	onDismiss: console.log, // eslint-disable-line no-console
};
