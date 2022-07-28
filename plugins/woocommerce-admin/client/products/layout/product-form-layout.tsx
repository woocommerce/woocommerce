/**
 * Internal dependencies
 */
import { ProductCategoryLayout } from './product-category-layout';
import { ProductFieldLayout } from './product-field-layout';

import './product-form-layout.scss';

type ProductFormLayoutProps = {
	categories: {
		id: string;
		title: string;
		description: string;
		fields: JSX.Element[];
	}[];
};
export const ProductFormLayout: React.FC< ProductFormLayoutProps > = ( {
	categories,
} ) => {
	return (
		<div className="product-form-layout">
			{ categories.map( ( { id, title, description, fields } ) => (
				<div key={ id } className="product-form-layout__category">
					<ProductCategoryLayout
						title={ title }
						description={ description }
					/>
					<ProductFieldLayout>{ fields }</ProductFieldLayout>
				</div>
			) ) }
		</div>
	);
};
