/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { useProductEntity } from '../../hooks/use-product-entity';

export function Edit( { context }: { context?: { area?: string } } ) {
	const blockProps = useBlockProps();
	const { id } = useProductEntity();

	if ( context?.area === 'product-editor' ) {
		return <div { ...blockProps }>Product editor: { id }</div>;
	}

	if ( context?.area === 'site-editor' ) {
		return <div { ...blockProps }>Site editor: { id }</div>;
	}

	return <div { ...blockProps }>Other: { id }</div>;
}
