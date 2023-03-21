/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { BlockEditProps } from '@wordpress/blocks';
import { BaseControl, ToolbarButton } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import uniqueId from 'lodash/uniqueId';
import classNames from 'classnames';
import { unregisterFormatType } from '@wordpress/rich-text';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	AlignmentControl,
	BlockControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ParagraphRTLControl } from './paragraph-rtl-control';
import { SummaryAttributes } from './types';
import { ALIGNMENT_CONTROLS } from './constants';
import { registerUppercaseFormatType } from '../../format-types';
import { alignNone } from '@wordpress/icons';
import { saveEntityRecord } from '@wordpress/core-data/actions';

export function Edit( {
	attributes,
	setAttributes,
}: BlockEditProps< SummaryAttributes > ) {
	const { align, direction, label } = attributes;
	const blockProps = useBlockProps( {
		style: { direction },
	} );
	const id = uniqueId();
	const [ summary, setSummary ] = useEntityProp< string >(
		'postType',
		'product',
		'short_description'
	);

	function handleAlignmentChange( value: SummaryAttributes[ 'align' ] ) {
		setAttributes( { align: value } );
	}

	function handleDirectionChange( value: SummaryAttributes[ 'direction' ] ) {
		setAttributes( { direction: value } );
	}

	return (
		<div { ...blockProps }>
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

				<ToolbarButton
					name="RichText.ToolbarControls"
					icon={ alignNone }
					title="SAMPLE"
				/>
			</BlockControls>

			<BaseControl
				id={ id }
				label={ label || __( 'Summary', 'woocommerce' ) }
			>
				<RichText
					id={ id }
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
					allowedFormats={ [
						'core/underline',
						'core/strikethrough',
					] }
				/>
			</BaseControl>
		</div>
	);
}

unregisterFormatType( 'core/image' );
unregisterFormatType( 'core/keyboard' );

registerUppercaseFormatType();

const [product, set] = useState(null);

useEffect(() => {
	const promise = productId ? getEntityRecord(productId) : saveEntityRecord({ ...});
	promise.then(set).catch(() => {});
}, [productId]);

