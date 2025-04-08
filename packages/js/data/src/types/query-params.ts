export type BaseQueryParams< Fields = string > = {
	context?: string;
	page?: number;
	per_page?: number;
	search?: string;
	after?: string;
	before?: string;
	exclude?: string | number[];
	include?: string | number[];
	offset?: number;
	order?: 'asc' | 'desc';
	orderby?: 'date' | 'id' | 'include' | 'title' | 'slug' | 'menu_order';
	parent?: number[];
	parent_exclude?: number[];
	_fields?: Fields[];
};
