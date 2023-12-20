export interface ProductCategoriesBlockProps {
	attributes: {
		hasCount: boolean;
		hasImage: boolean;
		hasEmpty: boolean;
		isDropdown: boolean;
		isHierarchical: boolean;
		showChildrenOnly: boolean;
	};
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
