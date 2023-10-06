/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useEntityProp } from '@wordpress/core-data';
import { ToggleControl } from '@wordpress/components';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { ToggleBlockAttributes } from './types';
import { sanitizeHTML } from '../../../utils/sanitize-html';
import { ProductEditorBlockEditProps } from '../../../types';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< ToggleBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
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
