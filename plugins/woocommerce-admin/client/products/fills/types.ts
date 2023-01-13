export type Section = {
	id: string;
	order?: number;
	plugin_id: string;
	location: string;
	title: string;
	description: string;
};

export type Field = {
	id: string;
	order?: number;
	plugin_id: string;
	type: string;
	section: string;
	properties: {
		name: string;
		label: string;
	};
};

export type ProductForm = {
	fields: Field[];
	sections: Section[];
};
