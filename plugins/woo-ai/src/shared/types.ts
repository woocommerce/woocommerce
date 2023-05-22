export type TinyContent = {
	getContent: () => string;
	setContent: ( str: string ) => void;
};

export type Attribute = {
	name: string;
	value: string;
};

export type ProductData = {
	name: string;
	description: string;
	categories: string[];
	tags: string[];
	attributes: Attribute[];
	product_type: string;
	is_downloadable: string;
	is_virtual: string;
};

export type AttributeSuggestion = {
	reason: string;
	content: string;
};

export type AttributeSuggestionRequest = {
	requested_attribute: string;
	name: string;
	description: string;
	categories: string[];
	tags: string[];
	attributes: Attribute[];
};
