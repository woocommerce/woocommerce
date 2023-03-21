/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { formatUppercase } from '@wordpress/icons';
import {
	FormatProps,
	NamedFormatConfiguration,
	toggleFormat,
} from '@wordpress/rich-text';

const name = 'woocommerce/uppercase';
const title = __( 'Uppercase', 'woocommerce' );

function ToolbarButton( { isActive, value, onChange }: FormatProps ) {
	function handleClick() {
		onChange(
			toggleFormat( value, {
				type: name,
				attributes: {
					style: 'text-transform: uppercase;',
				},
			} )
		);
	}

	return (
		<RichTextToolbarButton
			title={ title }
			icon={ formatUppercase }
			isActive={ isActive }
			onClick={ handleClick }
		/>
	);
}

export const formatType: NamedFormatConfiguration = {
	name,
	title,
	tagName: 'span',
	className: 'has-text-transform-uppercase',
	attributes: {
		style: 'style',
	},
	edit: ToolbarButton,
};
