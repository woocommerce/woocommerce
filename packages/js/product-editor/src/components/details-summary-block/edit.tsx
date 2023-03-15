/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { RichText, useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { BaseControl } from '@wordpress/components';
import uniqueId from 'lodash/uniqueId';
import { BlockAttributes } from '@wordpress/blocks';

export function Edit( { attributes }: { attributes: BlockAttributes } ) {
	const { label } = attributes;
	const blockProps = useBlockProps();
	const id = uniqueId();
	const [ summary, setSummary ] = useEntityProp< string >(
		'postType',
		'product',
		'short_description'
	);

	return (
		<div { ...blockProps }>
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
					className="components-summary-control"
				/>
			</BaseControl>
		</div>
	);
}
