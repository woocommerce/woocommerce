/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { BlockEditProps } from '@wordpress/blocks';
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
	useBlockProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ParagraphRTLControl } from './paragraph-rtl-control';
import { SummaryAttributes } from './types';
import { ALIGNMENT_CONTROLS } from './constants';

export function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< SummaryAttributes > ) {
	const { align, allowedFormats, direction, label } = attributes;
	const blockProps = useBlockProps( {
		style: { direction },
	} );
	const contentId = useInstanceId(
		Edit,
		'wp-block-woocommerce-product-summary-field__content'
	);
	const [ summary, setSummary ] = useEntityProp< string >(
		'postType',
		'product',
		'short_description'
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
				label={ label || __( 'Summary', 'woocommerce' ) }
			>
				<div { ...blockProps }>
					<RichText
						id={ contentId.toString() }
						identifier="content"
						tagName="p"
						value={ summary }
						onChange={ setSummary }
						placeholder={ __(
							"Summarize this product in 1-2 short sentences. We'll show it at the top of the page.",
							'woocommerce'
						) }
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
