/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	AlignmentControl,
	BlockControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { BaseControl } from '@wordpress/components';
import uniqueId from 'lodash/uniqueId';
import { BlockAttributes } from '@wordpress/blocks';
import classNames from 'classnames';

export function Edit( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes( attributes: BlockAttributes ): void;
} ) {
	const { align, label } = attributes;
	const blockProps = useBlockProps();
	const id = uniqueId();
	const [ summary, setSummary ] = useEntityProp< string >(
		'postType',
		'product',
		'short_description'
	);

	function handleAlignmentChange( value: string ) {
		setAttributes( {
			align: value,
		} );
	}

	return (
		<div { ...blockProps }>
			{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
			{ /* @ts-ignore No types for this exist yet. */ }
			<BlockControls group="block">
				<AlignmentControl
					value={ align }
					onChange={ handleAlignmentChange }
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
				/>
			</BaseControl>
		</div>
	);
}
