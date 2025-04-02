/**
 * External dependencies
 */
import { LegacyRef } from 'react';
import { __ } from '@wordpress/i18n';
import { createElement, useRef, useState } from '@wordpress/element';
import {
	BaseControl,
	SlotFillProvider,
	TextareaControl,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import classNames from 'classnames';
import { useInstanceId } from '@wordpress/compose';
import {
	BlockControls,
	BlockEditorProvider,
	ObserveTyping,
	RichText,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockTools,
	store as blockEditorStore,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { RTLToolbarButton } from '../../../../blocks/generic/text-area/toolbar/toolbar-button-rtl';
import { useClearSelectedBlockOnBlur } from '../../../../hooks/use-clear-selected-block-on-blur';
import AlignmentToolbarButton from '../../../../blocks/generic/text-area/toolbar/toolbar-button-alignment';
import { Label } from '../../../../components/label/label';
import { ProductDataFormControlProps } from '../types';
import { TextAreaBlockEditAttributes } from '../../../../blocks/generic/text-area/types';

function RichTextEditor( {
	contentId,
	label,
	value,
	onChange,
	id,
	allowedFormats,
	placeholder,
	required,
	disabled,
	defaultAlign,
	defaultDirection,
}: {
	contentId: string;
	label: string;
	value: string;
	onChange: ( value: Record< string, any > ) => void;
	id: string;
	allowedFormats?: string[];
	placeholder?: string;
	required?: boolean;
	disabled?: boolean;
	defaultAlign?: TextAreaBlockEditAttributes[ 'align' ];
	defaultDirection?: 'ltr' | 'rtl';
} ) {
	const { selectBlock } = useDispatch( blockEditorStore );
	const [ align, setAlignment ] = useState( defaultAlign ?? 'left' );
	const [ direction, changeDirection ] = useState( defaultDirection );
	const blockControlsBlockProps = { group: 'block' };
	const richTextRef = useRef< HTMLParagraphElement >( null );
	// This is a workaround to hide the toolbar when the block is blurred.
	// This is a temporary solution until using Gutenberg 18 with the
	// fix from https://github.com/WordPress/gutenberg/pull/59800
	const { handleBlur: hideToolbar } = useClearSelectedBlockOnBlur();
	const labelId = contentId.toString() + '__label';

	const showToolbar = () => {
		selectBlock( contentId );
	};

	function focusRichText() {
		richTextRef.current?.focus();
	}

	return (
		<div>
			<BlockTools>
				<ObserveTyping>
					<BlockControls { ...blockControlsBlockProps }>
						<AlignmentToolbarButton
							align={ align }
							setAlignment={ setAlignment }
						/>

						<RTLToolbarButton
							direction={ direction }
							onChange={ changeDirection }
						/>
					</BlockControls>
					<BaseControl
						id={ contentId.toString() }
						label={
							<Label
								label={ label || '' }
								labelId={ labelId }
								required={ false }
								note={ '' }
								tooltip={ '' }
								onClick={ focusRichText }
							/>
						}
						help={ '' }
					>
						<RichText
							ref={ richTextRef as unknown as LegacyRef< 'p' > }
							id={ contentId.toString() }
							aria-labelledby={ labelId }
							identifier="content"
							tagName="p"
							value={ value || '' }
							onChange={ ( nextValue ) => {
								onChange( {
									[ id ]: nextValue,
								} );
							} }
							data-empty={ Boolean( value ) }
							className={ classNames(
								'components-summary-control',
								{
									[ `has-text-align-${ align }` ]: align,
								}
							) }
							allowedFormats={ allowedFormats }
							placeholder={ placeholder }
							required={ required }
							aria-required={ required }
							readOnly={ disabled }
							onBlur={ hideToolbar }
							onFocus={ showToolbar }
							inlineToolbar={ true }
						/>
					</BaseControl>
				</ObserveTyping>
			</BlockTools>
		</div>
	);
}

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
			<SlotFillProvider>
				<BlockEditorProvider
					useSubRegistry={ true }
					settings={ {
						bodyPlaceholder: '',
						hasFixedToolbar: false,
						// eslint-disable-next-line @typescript-eslint/ban-ts-comment
						// @ts-ignore This property was recently added in the block editor data store.
						__experimentalClearBlockSelection: false,
					} }
				>
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
				</BlockEditorProvider>
			</SlotFillProvider>
		</div>
	);
}
