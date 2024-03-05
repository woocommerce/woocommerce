/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { CustomFieldsBlockAttributes } from './types';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< CustomFieldsBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	return <div { ...blockProps }></div>;
}
