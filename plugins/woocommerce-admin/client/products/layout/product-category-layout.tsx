/**
 * Internal dependencies
 */
import './product-category-layout.scss';

type ProductCategoryLayoutProps = {
	title: string;
	description: string;
};

export const ProductCategoryLayout: React.FC< ProductCategoryLayoutProps > = ( {
	title,
	description,
	children,
} ) => {
	return (
		<div className="product-form-layout__category product-category-layout">
			<div className="product-category-layout__header">
				<h3 className="product-category-layout__title">{ title }</h3>
				<div>
					<p>{ description }</p>
				</div>
			</div>
			<div className="product-category-layout__fields">{ children }</div>
		</div>
	);
};
