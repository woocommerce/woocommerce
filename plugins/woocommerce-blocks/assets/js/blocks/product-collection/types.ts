export interface ProductCollectionAttributes {
	query: ProductCollectionQuery;
	queryId: number;
	queryContext: [
		{
			page: number;
		}
	];
	templateSlug: string;
	displayLayout: ProductCollectionDisplayLayout;
}

export interface ProductCollectionDisplayLayout {
	type: string;
	columns: number;
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
	woocommerceOnSale: boolean;
}

export type TProductCollectionOrder = 'asc' | 'desc';
export type TProductCollectionOrderBy =
	| 'date'
	| 'title'
	| 'popularity'
	| 'rating';
