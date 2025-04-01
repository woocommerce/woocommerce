/**
 * External dependencies
 */
import { LegacyRef } from 'react';
import { __ } from '@wordpress/i18n';
import { createElement, useRef, useState, useEffect } from '@wordpress/element';
import { BaseControl, SlotFillProvider } from '@wordpress/components';
import { DataFormControlProps } from '@wordpress/dataviews';
import { useDispatch } from '@wordpress/data';
import classNames from 'classnames';
import { Product } from '@woocommerce/data';
import { registerCoreBlocks } from '@wordpress/block-library';
import { useInstanceId } from '@wordpress/compose';
import { __experimentalRichTextEditor as GutenbergRichTextEditor } from '@woocommerce/components';
import {
	BlockControls,
	BlockEditorProvider,
	ObserveTyping,
	RichText,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockTools,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockContextProvider,
	store as blockEditorStore,
	useBlockProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { RTLToolbarButton } from '../../../../blocks/generic/text-area/toolbar/toolbar-button-rtl';
import { useClearSelectedBlockOnBlur } from '../../../../hooks/use-clear-selected-block-on-blur';
import AlignmentToolbarButton from '../../../../blocks/generic/text-area/toolbar/toolbar-button-alignment';
import { Label } from '../../../../components/label/label';

registerCoreBlocks();
function RichTextEditor( {
	contentId,
	label,
	value,
	onChange,
	id,
}: {
	contentId: string;
	label: string;
	value: string;
	onChange: ( value: Record< string, any > ) => void;
	id: string;
} ) {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	const blockProps = useBlockProps( { clientId: contentId } );
	const { selectBlock } = useDispatch( blockEditorStore );
	const [ align, setAlignment ] = useState< 'left' | 'right' >( 'left' );
	const [ direction, changeDirection ] = useState<
		'ltr' | 'rtl' | undefined
	>( undefined );
	const blockControlsBlockProps = { group: 'block' };
	const richTextRef = useRef< HTMLParagraphElement >( null );
	// This is a workaround to hide the toolbar when the block is blurred.
	// This is a temporary solution until using Gutenberg 18 with the
	// fix from https://github.com/WordPress/gutenberg/pull/59800
	const { handleBlur: hideToolbar } = useClearSelectedBlockOnBlur();
	const labelId = contentId.toString() + '__label';

	const showToolbar = () => {
		selectBlock( contentId );
		console.log( 'showToolbar' );
	};

	function focusRichText() {
		richTextRef.current?.focus();
	}

	return (
		<div { ...blockProps }>
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
							allowedFormats={ [
								'core/bold',
								'core/code',
								'core/italic',
								'core/link',
								'core/strikethrough',
								'core/underline',
								'core/text-color',
								'core/subscript',
								'core/superscript',
								'core/unknown',
							] }
							placeholder={ __(
								'Enter description here',
								'woocommerce'
							) }
							required={ false }
							aria-required={ false }
							readOnly={ false }
							// onBlur={ hideToolbar }
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
}: DataFormControlProps< Product > ) {
	const { id, label } = field;
	const value = field.getValue( { item: data } ) ?? '';
	const contentId = useInstanceId(
		TextAreaBlockEdit,
		'wp-block-woocommerce-product-content-field__content'
	);
	useEffect( () => {
		registerCoreBlocks();
	}, [] );

	return (
		<div>
			<SlotFillProvider>
				<BlockContextProvider>
					<BlockEditorProvider
						useSubRegistry={ true }
						value={ [
							{
								clientId: contentId,
								attributes: {
									content: value,
								},
								innerBlocks: [],
							},
						] }
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
						/>
					</BlockEditorProvider>
				</BlockContextProvider>
			</SlotFillProvider>
		</div>
	);
}
