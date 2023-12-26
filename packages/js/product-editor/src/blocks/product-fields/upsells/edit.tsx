/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import type { UpsellsBlockEditComponent } from './types';

export function UpsellsBlockEdit( { attributes }: UpsellsBlockEditComponent ) {
	const blockProps = useWooBlockProps( attributes );

	return <div { ...blockProps }></div>;
}
