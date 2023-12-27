/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import React from 'react';

/**
 * Internal dependencies
 */
import { AdviceCard, AdviceCardProps } from '..';
import { ShoppingBags } from '../../../images/shopping-bags';

export default {
	title: 'Product Editor/components/AdviceCard',
	component: AdviceCard,
	args: {
		tip: __(
			'Tip: Upsells are typically products that are extra profitable or better quality or more expensive. Experiment with combinations to boost sales.',
			'woocommerce'
		),
		image: <ShoppingBags />,
		isDismissible: true,
	},
};

export const Default = ( args: AdviceCardProps ) => <AdviceCard { ...args } />;

Default.args = {
	// @todo: use an addon
	onDismiss: console.log, // eslint-disable-line no-console
};
