/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import LightBulbImage from '../assets/images/loader-lightbulb.svg';
import DevelopingImage from '../assets/images/loader-developing.svg';
import LayoutImage from '../assets/images/loader-layout.svg';

import { Stages } from '../pages/Loader';

const LightbulbStage = {
	title: __( 'Turning on the lights', 'woocommerce' ),
	image: <img src={ LightBulbImage } alt="loader-lightbulb" />,
	paragraphs: [
		{
			label: __( '#FunWooFact: ', 'woocommerce' ),
			text: __(
				'The Woo team is made up of over 350 talented individuals, distributed across 30+ countries.',
				'woocommerce'
			),
		},
	],
};
const LayoutStage = {
	title: __( "Extending your store's capabilities", 'woocommerce' ),
	image: <img src={ LayoutImage } alt="loader-lightbulb" />,
	paragraphs: [
		{
			label: __( '#FunWooFact: ', 'woocommerce' ),
			text: __(
				'#FunWooFact: Did you know that Woo powers almost 4 million stores worldwide? You’re in good company.',
				'woocommerce'
			),
		},
	],
};

const DevelopingStage = {
	title: __( "Woo! Let's get your features ready", 'woocommerce' ),
	image: <img src={ DevelopingImage } alt="loader-developng" />,
	paragraphs: [
		{
			label: __( '#FunWooFact: ', 'woocommerce' ),
			text: __(
				'Did you know that Woo was founded by two South Africans and a Norwegian? Here are three alternative ways to say “store” in those countries – Winkel, ivenkile, and butikk.',
				'woocommerce'
			),
		},
	],
};

export const getLoaderStageMeta = ( key: string ): Stages => {
	switch ( key ) {
		case 'plugins':
			return [ DevelopingStage, LayoutStage, LightbulbStage ];
		case 'default':
		default:
			return [ LightbulbStage ];
	}
};
