/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import { createElement } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { CatalogVisibilityBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';
import { CatalogVisibility } from '../../../components/catalog-visibility';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< CatalogVisibilityBlockAttributes > ) {
	const { label, visibility } = attributes;

	const blockProps = useWooBlockProps( attributes );

	const [ catalogVisibility, setCatalogVisibility ] = useEntityProp<
		Product[ 'catalog_visibility' ]
	>( 'postType', 'product', 'catalog_visibility' );

	return (
		<div { ...blockProps }>
			<CatalogVisibility
				catalogVisibility={ catalogVisibility }
				label={ label }
				visibility={ visibility }
				onCheckboxChange={ setCatalogVisibility }
			/>
		</div>
	);
}
