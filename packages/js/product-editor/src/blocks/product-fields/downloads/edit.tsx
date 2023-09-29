/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { UploadsBlockAttributes } from './types';

export function Edit( {
	attributes,
}: BlockEditProps< UploadsBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	return <div { ...blockProps }></div>;
}
