/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import LightBulb from './assets/images/loader-lightbulb.svg';
import { Stages } from './pages/Loader';

export const getLoaderStageMeta = ( key: string ): Stages => {
	switch ( key ) {
		case 'default':
		default:
			return [
				{
					title: __( 'Turning on the lights', 'woocommerce' ),
					image: <img src={ LightBulb } />,
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
