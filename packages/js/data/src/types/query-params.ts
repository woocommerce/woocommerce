export type BaseQueryParams< Fields = string > = {
	context: string;
	page: number;
	per_page: number;
	search: string;
	after: string;
	before: string;
	exclude: string;
	include: string;
	offset: number;
	order: 'asc' | 'desc';
	orderby: 'date' | 'id' | 'include' | 'title' | 'slug';
	parent: number[];
	parent_exclude: number[];
	_fields: Fields[];
};
