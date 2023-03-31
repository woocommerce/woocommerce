/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Fragment } from '@wordpress/element';
import type { BlockAttributes } from '@wordpress/blocks';
import { RadioControl } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { sanitizeHTML } from '../../utils/sanitize-html';

export function Edit( { attributes }: { attributes: BlockAttributes } ) {
	const blockProps = useBlockProps();
	const { description, options, property, title } = attributes;
	const [ value, setValue ] = useEntityProp< string >(
		'postType',
		'product',
		property
	);

	return (
		<div { ...blockProps }>
			<RadioControl
				label={
					<>
						<span className="wp-block-woocommerce-product-radio__title">
							{ title }
						</span>
						<span
							className="wp-block-woocommerce-product-radio__description"
							dangerouslySetInnerHTML={ sanitizeHTML(
								description
							) }
						/>
					</>
				}
				selected={ value }
				options={ options }
				onChange={ ( selected ) => setValue( selected || '' ) }
			/>
		</div>
	);
}
