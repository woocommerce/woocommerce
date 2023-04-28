/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Stages } from '../types';

export default [
	{
		title: __( 'Turning on the lights', 'woocommerce' ),
		image: 'lightbulb',
		progress: 80,
		paragraphs: [
			{
				label: __( '#FunWooFact: ', 'woocommerce' ),
				text: __(
					'The Woo team is made up of over 350 talented individuals, distributed across 30+ countries.',
					'woocommerce'
				),
			},
			{
				label: __( '#FunWooFact: ', 'woocommerce' ),
				text: __(
					'#FunWooFact: The Woo team is made up..',
					'woocommerce'
				),
			},
		],
	},
] as Stages;
