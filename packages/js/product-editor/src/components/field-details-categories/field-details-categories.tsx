/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { Product, ProductCategory } from '@woocommerce/data';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CategoryField } from './category-field';

export const DetailsCategoriesField = () => {
	const { getInputProps } = useFormContext< Product >();

	return (
		<CategoryField
			label={ __( 'Categories', 'woocommerce' ) }
			placeholder={ __( 'Search or create category…', 'woocommerce' ) }
			{ ...getInputProps< Pick< ProductCategory, 'id' | 'name' >[] >(
				'categories'
			) }
		/>
	);
};
