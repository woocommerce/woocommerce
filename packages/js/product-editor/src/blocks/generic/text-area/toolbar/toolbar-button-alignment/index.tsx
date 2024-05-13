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
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	AlignmentControl,
} from '@wordpress/block-editor';

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

export default function AligmentToolbarButton( {
	align,
	setAlignment,
}: AlignmentControl ) {
	return (
		<AlignmentControl
			alignmentControls={ ALIGNMENT_CONTROLS }
			value={ align }
			onChange={ setAlignment }
		/>
	);
}
