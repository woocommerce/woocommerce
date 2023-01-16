type BaseComponent = {
	id: string;
	plugin_id: string;
	order: number;
};

type FieldProperties = {
	name: string;
	label: string;
};

export type Field = BaseComponent & {
	type: string;
	section: string;
	properties: FieldProperties;
};

export type Section = BaseComponent & {
	title: string;
	description: string;
};

export type Subsection = BaseComponent;

export type ProductForm = {
	fields: Field[];
	sections: Section[];
	subsections: Subsection[];
};

export type ProductFormState = ProductForm & {
	errors: {
		[ key: string ]: unknown;
	};
};
