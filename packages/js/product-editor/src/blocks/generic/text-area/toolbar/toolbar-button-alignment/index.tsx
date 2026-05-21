/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	alignCenter,
	alignJustify,
	alignLeft,
	alignRight,
} from '@wordpress/icons';
import { AlignmentControl } from '@wordpress/block-editor';

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

export default function AlignmentToolbarButton( {
	align,
	setAlignment,
}: {
	align?: string;
	setAlignment: (
		alignment: 'left' | 'center' | 'right' | 'justify' | undefined
	) => void;
} ) {
	return (
		<AlignmentControl
			alignmentControls={ ALIGNMENT_CONTROLS }
			value={ align }
			onChange={ setAlignment }
		/>
	);
}
