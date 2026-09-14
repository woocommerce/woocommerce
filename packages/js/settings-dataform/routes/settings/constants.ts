export const SETTINGS_ENTITY = {
	kind: 'woo_settings',
	name: 'product',
	baseURL: '/wc/v4/settings/general',
	key: 'id',
};
export const SETTINGS_QUERY = { per_page: -1 };
export const SETTINGS_ARGS = [
	SETTINGS_ENTITY.kind,
	SETTINGS_ENTITY.name,
	SETTINGS_QUERY,
] as const;

export const VIEW_CONFIG_FIELDS = [ 'form' ];
export const VIEW_CONFIG_ARGS = [
	SETTINGS_ENTITY.kind,
	SETTINGS_ENTITY.name,
	{ fields: VIEW_CONFIG_FIELDS.join( ',' ) },
] as const;
