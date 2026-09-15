export const SETTINGS_ENTITY = {
	kind: 'woo_settings',
	name: 'product',
	baseURL: '/wc/v4/settings/products',
	key: false,
};
export const SETTINGS_ARGS = [
	SETTINGS_ENTITY.kind,
	SETTINGS_ENTITY.name,
	undefined,
] as const;

export const VIEW_CONFIG_FIELDS = [ 'form' ];
export const VIEW_CONFIG_ARGS = [
	SETTINGS_ENTITY.kind,
	SETTINGS_ENTITY.name,
	{ fields: VIEW_CONFIG_FIELDS.join( ',' ) },
] as const;
