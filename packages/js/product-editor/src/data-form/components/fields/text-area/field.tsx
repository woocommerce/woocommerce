/**
 * External dependencies
 */
import { createElement, useRef } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import { BaseControl, TextareaControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Label } from '../../../../components/label/label';
import { ProductDataFormControlProps } from '../types';
import { TextAreaBlockEditAttributes } from '../../../../blocks/generic/text-area/types';
import RichTextEditor from './rich-text';

export function TextAreaBlockEdit( {
	data,
	onChange,
	field,
	attributes,
}: ProductDataFormControlProps<
	TextAreaBlockEditAttributes & { note?: string; tooltip?: string }
> ) {
	const { id, label } = field;
	const {
		placeholder,
		help,
		required,
		note,
		tooltip,
		disabled = false,
		align,
		allowedFormats,
		direction,
		mode = 'rich-text',
	} = attributes || {};
	const value = field.getValue( { item: data } ) ?? '';
	const textAreaRef = useRef< HTMLTextAreaElement >( null );
	const contentId = useInstanceId(
		TextAreaBlockEdit,
		'wp-block-woocommerce-product-content-field__content'
	);
	const labelId = contentId.toString() + '__label';

	function focusTextArea() {
		textAreaRef.current?.focus();
	}

	if ( mode === 'plain-text' ) {
		return (
			<BaseControl
				id={ contentId.toString() }
				label={
					<Label
						label={ label || '' }
						labelId={ labelId }
						required={ required }
						note={ note }
						tooltip={ tooltip }
						onClick={ focusTextArea }
					/>
				}
				help={ help }
			>
				<TextareaControl
					ref={ textAreaRef }
					aria-labelledby={ labelId }
					value={ value || '' }
					onChange={ ( nextValue: string ) => {
						onChange( {
							[ id ]: nextValue,
						} );
					} }
					placeholder={ placeholder }
					required={ required }
					disabled={ disabled }
				/>
			</BaseControl>
		);
	}

	return (
		<div>
			<RichTextEditor
				contentId={ contentId }
				label={ label }
				value={ value }
				onChange={ onChange }
				id={ id }
				allowedFormats={ allowedFormats }
				placeholder={ placeholder }
				required={ required }
				disabled={ disabled }
				defaultAlign={ align }
				defaultDirection={ direction }
			/>
		</div>
	);
}
