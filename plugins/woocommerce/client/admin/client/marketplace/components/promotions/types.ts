export type Page = {
	page?: string;
	path?: string;
	tab?: string;
	pathname?: string;
};

export type Promotion = {
	date_from_gmt: string;
	date_to_gmt: string;
	format: string;
	style?: string;
	pages: Page[];
	content: { [ locale: string ]: string };
	icon?: string;
	title?: { [ locale: string ]: string };
	is_dismissible?: boolean;
	menu_item_id?: string;
	cta_label?: {
		[ locale: string ]: string;
	};
	cta_link?: string;
};
