export interface ProductCollectionAttributes {
	query: ProductCollectionQuery;
	queryId: number;
	queryContext: [
		{
			page: number;
		}
	];
	templateSlug: string;
	displayLayout: {
		type: string;
		columns: number;
	};
}

export interface ProductCollectionQuery {
	author: string;
	exclude: string[];
	inherit: boolean;
	offset: number;
	order: TProductCollectionOrder;
	orderBy: TProductCollectionOrderBy;
	pages: number;
	parents: number[];
	perPage: number;
	postType: string;
	search: string;
	sticky: string;
	taxQuery: string;
}

export type TProductCollectionOrder = 'asc' | 'desc';
export type TProductCollectionOrderBy =
	| 'date'
	| 'title'
	| 'popularity'
	| 'rating';
