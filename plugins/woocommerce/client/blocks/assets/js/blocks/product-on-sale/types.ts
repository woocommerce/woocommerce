export interface Attributes {
	columns: number;
	rows: number;
	alignButtons: boolean;
	contentVisibility: {
		image: boolean;
		title: boolean;
		price: boolean;
		rating: boolean;
		button: boolean;
	};
	categories: Array< number >;
	catOperator: string;
	isPreview: boolean;
	stockStatus: Array< string >;
	editMode: boolean;
	orderby:
		| 'date'
		| 'popularity'
		| 'price_asc'
		| 'price_desc'
		| 'rating'
		| 'title'
		| 'menu_order';
}
