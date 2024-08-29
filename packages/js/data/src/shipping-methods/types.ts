// Types to describe shipping method object.
type Feature = {
	icon: string;
	title?: string;
	description: string;
};

type Layout = {
	image: string;
	features: Feature[];
};

type LayoutType = 'row' | 'column';

export type ShippingMethod = {
	name: string;
	slug: string;
	description: string;
	learn_more_link: string;
	is_visible: boolean;
	available_layouts: LayoutType[];
	layout_column?: Layout;
	layout_row?: Layout;
	dependencies?: string[];
};
