export type BlockData< T = unknown > = {
	name: string;
	slug: string;
	mainClass: string;
	selectors: Record< 'editor' | 'frontend', Record< string, unknown > >;
} & ( T extends undefined ? Record< string, never > : T );
