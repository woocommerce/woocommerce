/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { BlockAttributes } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { ProductCategory } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { CategoryField } from '../../components/details-categories-field';

export function Edit( {
	attributes,
	context,
}: {
	attributes: BlockAttributes;
	context?: { postType?: string };
} ) {
	const blockProps = useBlockProps();
	const { name, label, placeholder } = attributes;
	const [ categories, setCategories ] = useEntityProp<
		Pick< ProductCategory, 'name' | 'id' | 'parent' >[]
	>( 'postType', context?.postType || 'product', name || 'categories' );

	return (
		<div { ...blockProps }>
			<CategoryField
				label={ label || __( 'Categories', 'woocommerce' ) }
				placeholder={
					placeholder ||
					__( 'Search or create category…', 'woocommerce' )
				}
				onChange={ setCategories }
				value={ categories || [] }
			/>
		</div>
	);
}
