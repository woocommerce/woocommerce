/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { BlockEditProps } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ToggleBlockAttributes } from './types';
import { sanitizeHTML } from '../../utils/sanitize-html';

export function Edit( {
	attributes,
}: BlockEditProps< ToggleBlockAttributes > ) {
	const blockProps = useBlockProps();
	const { label, property, disabled, disabledCopy } = attributes;
	const [ value, setValue ] = useEntityProp< boolean >(
		'postType',
		'product',
		property
	);

	return (
		<div { ...blockProps }>
			<ToggleControl
				label={ label }
				checked={ value }
				disabled={ disabled }
				onChange={ setValue }
			/>
			{ disabled && (
				<p
					className="wp-block-woocommerce-product-toggle__disable-copy"
					dangerouslySetInnerHTML={ sanitizeHTML( disabledCopy ) }
				/>
			) }
		</div>
	);
}
