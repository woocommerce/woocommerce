export type BlockData< T = undefined > = {
	name: string;
	mainClass: string;
	selectors: Record< 'editor' | 'frontend', Record< string, unknown > >;
} & ( T extends undefined ? Record< string, never > : T );
