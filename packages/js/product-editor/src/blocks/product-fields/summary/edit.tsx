/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import {
	createElement,
	createInterpolateElement,
	useState,
} from '@wordpress/element';
import { __experimentalRichTextEditor as RichTextEditor } from '@woocommerce/components';
import { BaseControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useInstanceId } from '@wordpress/compose';
import { BlockInstance, serialize, parse } from '@wordpress/blocks';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	AlignmentControl,
	BlockControls,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ParagraphRTLControl } from './paragraph-rtl-control';
import { SummaryAttributes } from './types';
import { ALIGNMENT_CONTROLS } from './constants';
import { ProductEditorBlockEditProps } from '../../../types';
import { useClearSelectedBlockOnBlur } from '../../../hooks/use-clear-selected-block-on-blur';

export function SummaryBlockEdit( {
	attributes,
	setAttributes,
	context,
}: ProductEditorBlockEditProps< SummaryAttributes > ) {
	const { align, direction, label, helpText } = attributes;
	const blockProps = useWooBlockProps( attributes, {
		style: { direction },
	} );
	const contentId = useInstanceId(
		SummaryBlockEdit,
		'wp-block-woocommerce-product-summary-field__content'
	);
	const [ summary, setSummary ] = useEntityProp< string >(
		'postType',
		context.postType || 'product',
		attributes.property
	);
	const [ summaryBlocks, setSummaryBlocks ] = useState< BlockInstance[] >(
		parse( summary )
	);

	// This is a workaround to hide the toolbar when the block is blurred.
	// This is a temporary solution until using Gutenberg 18 with the
	// fix from https://github.com/WordPress/gutenberg/pull/59800
	const { handleBlur: hideToolbar } = useClearSelectedBlockOnBlur();

	function handleAlignmentChange( value: SummaryAttributes[ 'align' ] ) {
		setAttributes( { align: value } );
	}

	function handleDirectionChange( value: SummaryAttributes[ 'direction' ] ) {
		setAttributes( { direction: value } );
	}

	return (
		<div
			className={
				'wp-block wp-block-woocommerce-product-summary-field-wrapper'
			}
		>
			{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
			{ /* @ts-ignore No types for this exist yet. */ }
			<BlockControls group="block">
				<AlignmentControl
					alignmentControls={ ALIGNMENT_CONTROLS }
					value={ align }
					onChange={ handleAlignmentChange }
				/>

				<ParagraphRTLControl
					direction={ direction }
					onChange={ handleDirectionChange }
				/>
			</BlockControls>

			<BaseControl
				id={ contentId.toString() }
				label={
					typeof label === 'undefined'
						? createInterpolateElement(
								__( 'Summary', 'woocommerce' ),
								{
									optional: (
										<span className="woocommerce-product-form__optional-input">
											{ __(
												'(OPTIONAL)',
												'woocommerce'
											) }
										</span>
									),
								}
						  )
						: label
				}
				help={
					typeof helpText === 'undefined'
						? __(
								"Summarize this product in 1-2 short sentences. We'll show it at the top of the page.",
								'woocommerce'
						  )
						: helpText
				}
			>
				<div { ...blockProps }>
					<RichTextEditor
						label={ __( 'Summary', 'woocommerce' ) }
						blocks={ summaryBlocks }
						onChange={ ( blocks ) => {
							setSummaryBlocks( blocks );
							if ( ! summaryBlocks.length ) {
								return;
							}
							setSummary( serialize( blocks ) );
						} }
						placeholder={ __(
							'Describe this product. What makes it unique? What are its most important features?',
							'woocommerce'
						) }
					/>
				</div>
			</BaseControl>
		</div>
	);
}
