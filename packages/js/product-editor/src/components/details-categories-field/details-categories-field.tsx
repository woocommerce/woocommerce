/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { Product } from '@woocommerce/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CategoryField } from './category-field';
import { ProductCategoryNode } from './use-category-search';

export const DetailsCategoriesField = () => {
	const { getInputProps } = useFormContext< Product >();

	return (
		<CategoryField
			label={ __( 'Categories', 'woocommerce' ) }
			placeholder={ __( 'Search or create category…', 'woocommerce' ) }
			{ ...getInputProps< ProductCategoryNode[] >( 'categories' ) }
		/>
	);
};
