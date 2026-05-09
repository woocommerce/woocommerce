/**
 * Shape of a single field definition from the legacy-fields REST endpoint.
 */
export type LegacyFieldDefinition = {
	id: string;
	type: string;
	input_type: string;
	label: string;
	meta_key: string;
	placeholder: string;
	description: string;
	default_value: string;
	wrapper_class: string;
	custom_attributes: Record< string, string >;
	options: Record< string, string >;
	hidden: boolean;
};

/**
 * REST response shape from GET /wc/v4/products/legacy-fields.
 */
export type LegacyFieldsResponse = {
	fields: Record< string, LegacyFieldDefinition[] >;
};

/**
 * Mapping from hook name to insertion point in the native field list.
 */
export type LegacyHookMapping = Record<
	string,
	{ insertAfter: string } | { insertAt: 'end' }
>;
