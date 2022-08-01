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
		<div className="product-form-layout__category">
			<div className="product-category-layout">
				<h3 className="product-category-layout__title">{ title }</h3>
				<div>
					<p>{ description }</p>
				</div>
				{ children }
			</div>
		</div>
	);
};
