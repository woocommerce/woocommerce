/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { LinkedProductListBlockAttributes } from './types';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< LinkedProductListBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	return <div { ...blockProps }></div>;
}
