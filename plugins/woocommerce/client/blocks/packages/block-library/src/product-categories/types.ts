export interface ProductCategoriesAttributes {
	hasCount?: boolean;
	hasImage?: boolean;
	hasEmpty?: boolean;
	isDropdown?: boolean;
	isHierarchical?: boolean;
	showChildrenOnly?: boolean;
}

export interface ProductCategoriesBlockProps {
	attributes: ProductCategoriesAttributes;
	name: string;
	setAttributes: ( attributes: {
		hasCount?: boolean;
		hasImage?: boolean;
		hasEmpty?: boolean;
		isDropdown?: boolean;
		isHierarchical?: boolean;
		showChildrenOnly?: boolean;
	} ) => void;
}

export interface ProductCategoriesIndexProps {
	idBase?: string;
	instance: {
		raw: {
			dropdown: boolean;
			count: boolean;
			hide_empty: boolean;
			hierarchical: boolean;
		};
	};
	hasCount: boolean;
	hasEmpty: boolean;
	isDropdown: boolean;
	isHierarchical: boolean;
}
