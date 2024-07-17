export interface ProductCategoryResponseImageItem {
	id: number;
	src: string;
	thumbnail: string;
	srcset: string;
	sizes: string;
	name: string;
	alt: string;
}

export interface ProductCategoryResponseItem {
	id: number;
	name: string;
	slug: string;
	description: string;
	parent: number;
	count: number;
	image: ProductCategoryResponseImageItem | null;
	review_count: number;
	permalink: string;
}
