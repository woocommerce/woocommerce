type ProductFieldLayoutProps = {
	fieldName: string;
	categoryName: string;
};

// TODO: Do we need this component for anything? Only used with attributes, and now only adds this wrapper.

export const ProductFieldLayout: React.FC< ProductFieldLayoutProps > = ( {
	fieldName,
	categoryName,
	children,
} ) => {
	return <div className="product-field-layout">{ children }</div>;
};
