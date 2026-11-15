/**
 * Generic taxonomy term image response from the Store API.
 * Used for categories, brands, and other hierarchical taxonomies.
 */
export interface TaxonomyResponseImageItem {
	id: number;
	src: string;
	thumbnail: string;
	srcset: string;
	sizes: string;
	name: string;
	alt: string;
}

/**
 * Generic taxonomy term response from the Store API.
 * Used for categories, brands, and other hierarchical taxonomies.
 */
export interface TaxonomyResponseItem {
	id: number;
	name: string;
	slug: string;
	description: string;
	parent: number;
	count: number;
	image: TaxonomyResponseImageItem | null;
	review_count: number;
	permalink: string;
}

// Aliases for backward compatibility and semantic clarity
export type ProductCategoryResponseImageItem = TaxonomyResponseImageItem;
export type ProductCategoryResponseItem = TaxonomyResponseItem;
export type ProductBrandResponseImageItem = TaxonomyResponseImageItem;
export type ProductBrandResponseItem = TaxonomyResponseItem;
