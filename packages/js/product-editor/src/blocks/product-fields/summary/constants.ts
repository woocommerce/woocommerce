/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	alignCenter,
	alignJustify,
	alignLeft,
	alignRight,
} from '@wordpress/icons';

export const ALIGNMENT_CONTROLS = [
	{
		icon: alignLeft,
		title: __( 'Align text left', 'woocommerce' ),
		align: 'left',
	},
	{
		icon: alignCenter,
		title: __( 'Align text center', 'woocommerce' ),
		align: 'center',
	},
	{
		icon: alignRight,
		title: __( 'Align text right', 'woocommerce' ),
		align: 'right',
	},
	{
		icon: alignJustify,
		title: __( 'Align text justify', 'woocommerce' ),
		align: 'justify',
	},
];
