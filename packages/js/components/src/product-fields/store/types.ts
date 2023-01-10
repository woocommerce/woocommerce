export type ProductFieldDefinition = {
	name: string;
	type?: string;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	edit?: ( props: Record< string, any > ) => JSX.Element;
};

export type ProductFieldState = {
	fields: Record< string, ProductFieldDefinition >;
};
