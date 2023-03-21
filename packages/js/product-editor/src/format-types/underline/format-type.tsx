/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { formatUnderline } from '@wordpress/icons';
import {
	FormatProps,
	NamedFormatConfiguration,
	toggleFormat,
} from '@wordpress/rich-text';

const name = 'woocommerce/underline';
const title = __( 'Underline', 'woocommerce' );

function ToolbarButton( { isActive, value, onChange }: FormatProps ) {
	function handleClick() {
		onChange(
			toggleFormat( value, {
				type: name,
				attributes: {
					style: 'text-decoration: underline;',
				},
			} )
		);
	}

	return (
		<RichTextToolbarButton
			title={ title }
			icon={ formatUnderline }
			isActive={ isActive }
			onClick={ handleClick }
		/>
	);
}

export const formatType: NamedFormatConfiguration = {
	name,
	title,
	tagName: 'span',
	className: 'has-text-decoration-underline',
	attributes: {
		style: 'style',
	},
	edit: ToolbarButton,
};
