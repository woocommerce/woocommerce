/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext2 } from '@woocommerce/components';
import { Product, ProductCategory } from '@woocommerce/data';
import { Controller, useController } from 'react-hook-form';

/**
 * Internal dependencies
 */
import { CategoryField } from '../../fields/category-field';

export const DetailsCategoriesField = () => {
	const { control } = useFormContext2< Product >();
	const { field } = useController< Product >( {
		name: 'categories',
		control,
	} );

	return (
		<CategoryField
			{ ...field }
			label={ __( 'Categories', 'woocommerce' ) }
			placeholder={ __( 'Search or create category…', 'woocommerce' ) }
		/>
	);
};
