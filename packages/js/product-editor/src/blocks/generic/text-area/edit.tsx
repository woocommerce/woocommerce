/**
 * External dependencies
 */
import { LegacyRef } from 'react';
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { createElement, useRef } from '@wordpress/element';
import { BaseControl, TextareaControl } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { BlockControls, RichText } from '@wordpress/block-editor';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { RTLToolbarButton } from './toolbar/toolbar-button-rtl';
import type {
	TextAreaBlockEditAttributes,
	TextAreaBlockEditProps,
} from './types';
import AligmentToolbarButton from './toolbar/toolbar-button-alignment';
import { useClearSelectedBlockOnBlur } from '../../../hooks/use-clear-selected-block-on-blur';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';
import { Label } from '../../../components/label/label';

export function TextAreaBlockEdit( {
	attributes,
	setAttributes,
	context: { postType },
}: TextAreaBlockEditProps ) {
	const {
		property,
		label,
		placeholder,
		help,
		required,
		note,
		tooltip,
		disabled,
		align,
		allowedFormats,
		direction,
		mode = 'rich-text',
	} = attributes;
	const blockProps = useWooBlockProps( attributes, {
		className: 'wp-block-woocommerce-product-text-area-field',
		style: { direction },
	} );

	const contentId = useInstanceId(
		TextAreaBlockEdit,
		'wp-block-woocommerce-product-content-field__content'
	);

	const labelWrapperId = contentId.toString() + '__label-wrapper';
	const labelId = labelWrapperId + '__label';

	// `property` attribute is required.
	if ( ! property ) {
		throw new Error(
			__( 'Property attribute is required.', 'woocommerce' )
		);
	}

	const [ content, setContent ] = useProductEntityProp< string >( property, {
		postType,
	} );

	// This is a workaround to hide the toolbar when the block is blurred.
	// This is a temporary solution until using Gutenberg 18 with the
	// fix from https://github.com/WordPress/gutenberg/pull/59800
	const { handleBlur: hideToolbar } = useClearSelectedBlockOnBlur();

	function setAlignment( value: TextAreaBlockEditAttributes[ 'align' ] ) {
		setAttributes( { align: value } );
	}

	function changeDirection(
		value: TextAreaBlockEditAttributes[ 'direction' ]
	) {
		setAttributes( { direction: value } );
	}

	const richTextRef = useRef< HTMLParagraphElement >( null );

	function focusRichText() {
		richTextRef.current?.focus();
	}

	const blockControlsBlockProps = { group: 'block' };

	const isRichTextMode = mode === 'rich-text';
	const isPlainTextMode = mode === 'plain-text';

	return (
		<div { ...blockProps }>
			{ isRichTextMode && (
				<BlockControls { ...blockControlsBlockProps }>
					<AligmentToolbarButton
						align={ align }
						setAlignment={ setAlignment }
					/>

					<RTLToolbarButton
						direction={ direction }
						onChange={ changeDirection }
					/>
				</BlockControls>
			) }

			<BaseControl
				id={ contentId.toString() }
				label={
					<Label
						id={ labelWrapperId }
						label={ label || '' }
						required={ required }
						note={ note }
						tooltip={ tooltip }
						onClick={ isRichTextMode ? focusRichText : undefined }
					/>
				}
				help={ help }
			>
				{ isRichTextMode && (
					<RichText
						ref={ richTextRef as unknown as LegacyRef< 'p' > }
						id={ contentId.toString() }
						aria-labelledby={ labelId }
						identifier="content"
						tagName="p"
						value={ content || '' }
						onChange={ setContent }
						data-empty={ Boolean( content ) }
						className={ classNames( 'components-summary-control', {
							[ `has-text-align-${ align }` ]: align,
						} ) }
						dir={ direction }
						allowedFormats={ allowedFormats }
						placeholder={ placeholder }
						required={ required }
						disabled={ disabled }
						onBlur={ hideToolbar }
					/>
				) }

				{ isPlainTextMode && (
					<TextareaControl
						value={ content || '' }
						onChange={ setContent }
						placeholder={ placeholder }
						required={ required }
						disabled={ disabled }
						onBlur={ hideToolbar }
					/>
				) }
			</BaseControl>
		</div>
	);
}
