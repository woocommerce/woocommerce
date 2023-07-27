export type BlockData< T = unknown > = {
	name: string;
	mainClass: string;
	selectors: Record< 'editor' | 'frontend', Record< string, string > >;
} & ( T extends undefined ? Record< string, never > : T );
