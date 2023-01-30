/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useFormContext } from '@woocommerce/components';
import { Product, ProductCategory } from '@woocommerce/data';
import { Controller } from 'react-hook-form';

/**
 * Internal dependencies
 */
import { CategoryField } from '../../fields/category-field';

export const DetailsCategoriesField = () => {
	const { control } = useFormContext< Product >();

	return (
		<Controller
			name="categories"
			control={ control }
			render={ ( { field } ) => (
				<CategoryField
					{ ...field }
					label={ __( 'Categories', 'woocommerce' ) }
					placeholder={ __(
						'Search or create category…',
						'woocommerce'
					) }
				/>
			) }
		></Controller>
	);
};
