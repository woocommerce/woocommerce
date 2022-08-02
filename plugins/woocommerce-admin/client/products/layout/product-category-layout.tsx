/**
 * Internal dependencies
 */
import { ProductFieldLayout } from './product-field-layout';

type ProductCategoryLayoutProps = {
	fields: {
		label: string;
		key: string;
		autoComplete: string;
		value: string;
		onChange: () => void;
	}[];
	title: string;
	description: string;
};

export const ProductCategoryLayout: React.FC< ProductCategoryLayoutProps > = ( {
	title,
	description,
	fields,
} ) => {
	return (
		<div className="product-form-layout__category">
			<div className="product-category-layout">
				<h3 className="product-category-layout__title">{ title }</h3>
				<div>
					<p>{ description }</p>
				</div>
			</div>
			<div className="product-field-layout">
				{ fields.map(
					( { label, key, autoComplete, value, onChange } ) => (
						<ProductFieldLayout
							label={ label }
							key={ key }
							autoComplete={ autoComplete }
							value={ value }
							onChange={ onChange }
						/>
					)
				) }
			</div>
		</div>
	);
};
