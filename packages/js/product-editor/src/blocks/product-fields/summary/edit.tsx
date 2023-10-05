/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { createElement, createInterpolateElement } from '@wordpress/element';
import { BaseControl } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { useInstanceId } from '@wordpress/compose';
import classNames from 'classnames';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	AlignmentControl,
	BlockControls,
	RichText,
	store as blockEditorStore,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ParagraphRTLControl } from './paragraph-rtl-control';
import { SummaryAttributes } from './types';
import { ALIGNMENT_CONTROLS } from './constants';
import { ProductEditorBlockEditProps } from '../../../types';

export function Edit( {
	attributes,
	setAttributes,
	context,
}: ProductEditorBlockEditProps< SummaryAttributes > ) {
	const { align, allowedFormats, direction, label, helpText } = attributes;
	const blockProps = useWooBlockProps( attributes, {
		style: { direction },
	} );
	const contentId = useInstanceId(
		Edit,
		'wp-block-woocommerce-product-summary-field__content'
	);
	const [ summary, setSummary ] = useEntityProp< string >(
		'postType',
		context.postType || 'product',
		attributes.property
	);
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	const { clearSelectedBlock } = useDispatch( blockEditorStore );

	function handleAlignmentChange( value: SummaryAttributes[ 'align' ] ) {
		setAttributes( { align: value } );
	}

	function handleDirectionChange( value: SummaryAttributes[ 'direction' ] ) {
		setAttributes( { direction: value } );
	}

	function handleBlur( event: React.FocusEvent< 'p', Element > ) {
		const isToolbar = event.relatedTarget?.closest(
			'.block-editor-block-contextual-toolbar'
		);
		if ( ! isToolbar ) {
			clearSelectedBlock();
		}
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
				label={ createInterpolateElement(
					label || __( 'Summary', 'woocommerce' ),
					{
						optional: (
							<span className="woocommerce-product-form__optional-input">
								{ __( '(OPTIONAL)', 'woocommerce' ) }
							</span>
						),
					}
				) }
				help={
					typeof helpText === 'string'
						? helpText
						: __(
								"Summarize this product in 1-2 short sentences. We'll show it at the top of the page.",
								'woocommerce'
						  )
				}
			>
				<div { ...blockProps }>
					<RichText
						id={ contentId.toString() }
						identifier="content"
						tagName="p"
						value={ summary }
						onChange={ setSummary }
						data-empty={ Boolean( summary ) }
						className={ classNames( 'components-summary-control', {
							[ `has-text-align-${ align }` ]: align,
						} ) }
						dir={ direction }
						allowedFormats={ allowedFormats }
						onBlur={ handleBlur }
					/>
				</div>
			</BaseControl>
		</div>
	);
}
