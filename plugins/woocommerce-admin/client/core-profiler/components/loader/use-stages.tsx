/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import LightBulb from './images/lightbulb';
import { Stages } from './types';

export const useStages = ( key: string ): Stages => {
	switch ( key ) {
		case 'default':
		default:
			return [
				{
					title: __( 'Turning on the lights', 'woocommerce' ),
					image: <LightBulb />,
					paragraphs: [
						{
							label: __( '#FunWooFact: ', 'woocommerce' ),
							text: __(
								'The Woo team is made up of over 350 talented individuals, distributed across 30+ countries.',
								'woocommerce'
							),
						},
					],
				},
			];
	}
};
