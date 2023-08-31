export interface Taxonomy {
	id: number;
	name: string;
	parent: number;
}

export interface TaxonomyMetadata {
	hierarchical: boolean;
}
