/**
 * Internal dependencies
 */
import { ProductCategoryLayout } from './product-category-layout';
import './product-form-layout.scss';

type ProductFormLayoutProps = {
	categories: {
		id: string;
		title: string;
		description: string;
		fields: {
			label: string;
			key: string;
			autoComplete: string;
			value: string;
			onChange: () => void;
		}[];
	}[];
};

export const ProductFormLayout: React.FC< ProductFormLayoutProps > = ( {
	categories,
} ) => {
	return (
		<div className="product-form-layout">
			{ categories.map( ( { id, title, description, fields } ) => (
				<ProductCategoryLayout
					key={ id }
					title={ title }
					description={ description }
					fields={ fields }
				></ProductCategoryLayout>
			) ) }
		</div>
	);
};
