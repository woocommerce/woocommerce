// Helper types for @wordpress/core-data entity types
// This is a script file (no `export {}`) — the declare module block
// fully replaces the module.

declare module '@wordpress/core-data/build-types/entity-types/helpers' {
	export type Context = 'view' | 'edit' | 'embed';

	export type ContextualField<
		FieldType,
		AvailableInContexts extends Context,
		C extends Context
	> = AvailableInContexts extends C ? FieldType : never;

	export type OmitNevers<
		T,
		Nevers = {
			[ K in keyof T ]: Exclude< T[ K ], undefined > extends never
				? never
				: T[ K ] extends Record< string, unknown >
				? OmitNevers< T[ K ] >
				: T[ K ];
		}
	> = Pick<
		Nevers,
		{
			[ K in keyof Nevers ]: Nevers[ K ] extends never ? never : K;
		}[ keyof Nevers ]
	>;

	export interface AvatarUrls {
		'24': string;
		'48': string;
		'96': string;
	}

	export interface RenderedText< C extends Context > {
		raw: ContextualField< string, 'edit', C >;
		rendered: string;
	}

	export type MediaType = 'image' | 'file';
	export type CommentingStatus = 'open' | 'closed';
	export type PingStatus = 'open' | 'closed';
	export type PostStatus =
		| 'publish'
		| 'future'
		| 'draft'
		| 'pending'
		| 'private';
	export type PostFormat =
		| 'standard'
		| 'aside'
		| 'chat'
		| 'gallery'
		| 'link'
		| 'image'
		| 'quote'
		| 'status'
		| 'video'
		| 'audio';

	export type Updatable<
		T extends import( '@wordpress/core-data/build-types/entity-types' ).EntityRecord< 'edit' >
	> = {
		[ K in keyof T ]: T[ K ] extends RenderedText< any > ? string : T[ K ];
	};
}
