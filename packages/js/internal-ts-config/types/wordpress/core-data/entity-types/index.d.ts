// Entity type definitions for @wordpress/core-data
// This is a script file (no `export {}`) — the declare module block
// fully replaces the module.

declare module '@wordpress/core-data/build-types/entity-types' {
	import type {
		Context,
		ContextualField,
		OmitNevers,
		AvatarUrls,
		RenderedText,
		CommentingStatus,
		PingStatus,
		PostStatus,
	} from '@wordpress/core-data/build-types/entity-types/helpers';

	// --- User (fully defined) ---
	interface BaseUser< C extends Context > {
		id: number;
		username: ContextualField< string, 'edit', C >;
		name: string;
		first_name: ContextualField< string, 'edit', C >;
		last_name: ContextualField< string, 'edit', C >;
		email: ContextualField< string, 'edit', C >;
		url: string;
		description: string;
		link: string;
		locale: ContextualField< string, 'edit', C >;
		nickname: ContextualField< string, 'edit', C >;
		slug: string;
		registered_date: ContextualField< string, 'edit', C >;
		roles: ContextualField< string[], 'edit', C >;
		password?: string;
		capabilities: ContextualField< Record< string, string >, 'edit', C >;
		extra_capabilities: ContextualField<
			Record< string, string >,
			'edit',
			C
		>;
		avatar_urls: AvatarUrls;
		meta: ContextualField<
			Record< string, string >,
			'view' | 'edit',
			C
		>;
	}
	export type User< C extends Context = 'edit' > = OmitNevers< BaseUser< C > >;

	// --- Post (fully defined) ---
	interface BasePost< C extends Context > {
		id: number;
		date: string | null;
		date_gmt: ContextualField< string | null, 'view' | 'edit', C >;
		guid: ContextualField< RenderedText< C >, 'view' | 'edit', C >;
		link: string;
		modified: ContextualField< string, 'view' | 'edit', C >;
		modified_gmt: ContextualField< string, 'view' | 'edit', C >;
		slug: string;
		status: ContextualField< PostStatus, 'view' | 'edit', C >;
		type: string;
		password: ContextualField< string, 'edit', C >;
		permalink_template: ContextualField< string, 'edit', C >;
		generated_slug: ContextualField< string, 'edit', C >;
		title: RenderedText< C >;
		content: ContextualField<
			RenderedText< C > & {
				is_protected: boolean;
				block_version: ContextualField< string, 'edit', C >;
			},
			'view' | 'edit',
			C
		>;
		author: number;
		featured_media: number;
		excerpt: RenderedText< C > & { protected: boolean };
		comment_status: ContextualField< CommentingStatus, 'view' | 'edit', C >;
		ping_status: ContextualField< PingStatus, 'view' | 'edit', C >;
		format: ContextualField< string, 'view' | 'edit', C >;
		meta: ContextualField<
			Record< string, string >,
			'view' | 'edit',
			C
		>;
		sticky: ContextualField< boolean, 'view' | 'edit', C >;
		template: ContextualField< string, 'view' | 'edit', C >;
		categories: ContextualField< number[], 'view' | 'edit', C >;
		tags: ContextualField< number[], 'view' | 'edit', C >;
	}
	export type Post< C extends Context = 'edit' > = OmitNevers< BasePost< C > >;

	// --- Settings (fully defined) ---
	export interface Settings {
		title: string;
		description: string;
		url: string;
		email: string;
		timezone: string;
		date_format: string;
		time_format: string;
		start_of_week: number;
		language: string;
		use_smilies: boolean;
		default_category: number;
		default_post_format: string;
		posts_per_page: number;
		default_ping_status: string;
		default_comment_status: string;
		site_logo: number;
		site_icon: number;
	}

	// --- Page (fully defined) ---
	interface BasePage< C extends Context > {
		id: number;
		date: string | null;
		date_gmt: ContextualField< string | null, 'view' | 'edit', C >;
		guid: ContextualField< RenderedText< C >, 'view' | 'edit', C >;
		link: string;
		modified: ContextualField< string, 'view' | 'edit', C >;
		modified_gmt: ContextualField< string, 'view' | 'edit', C >;
		slug: string;
		status: ContextualField< PostStatus, 'view' | 'edit', C >;
		type: string;
		password: ContextualField< string, 'edit', C >;
		permalink_template: ContextualField< string, 'edit', C >;
		generated_slug: ContextualField< string, 'edit', C >;
		parent: ContextualField< number, 'view' | 'edit', C >;
		title: RenderedText< C >;
		content: ContextualField<
			RenderedText< C > & {
				is_protected: boolean;
				block_version: ContextualField< string, 'edit', C >;
			},
			'view' | 'edit',
			C
		>;
		author: number;
		featured_media: number;
		excerpt: RenderedText< C > & { protected: boolean };
		comment_status: ContextualField< CommentingStatus, 'view' | 'edit', C >;
		ping_status: ContextualField< PingStatus, 'view' | 'edit', C >;
		menu_order: ContextualField< number, 'view' | 'edit', C >;
		meta: ContextualField<
			Record< string, string >,
			'view' | 'edit',
			C
		>;
		template: ContextualField< string, 'view' | 'edit', C >;
	}
	export type Page< C extends Context = 'edit' > = OmitNevers< BasePage< C > >;

	// --- WpTemplate (fully defined) ---
	interface BaseWpTemplate< C extends Context > {
		id: string;
		slug: string;
		theme: string;
		type: string;
		source: string;
		origin: string;
		content: ContextualField<
			RenderedText< C > & {
				block_version: ContextualField< number, 'edit', C >;
			},
			'view' | 'edit',
			C
		>;
		title: RenderedText< 'edit' >;
		description: string;
		status: PostStatus;
		wp_id: number;
		plugin?: string;
		has_theme_file: Record< string, string >;
		author: number;
		is_custom: Record< string, string >;
		modified: ContextualField< string, 'view' | 'edit', C >;
	}
	export type WpTemplate< C extends Context = 'edit' > = OmitNevers<
		BaseWpTemplate< C >
	>;

	// --- Stub types for unused entity types ---
	// These are included so that EntityRecord<C> (the union of all entity types)
	// remains structurally compatible with the real package.
	export type Attachment< C extends Context = 'edit' > = Record<
		string,
		any
	>;
	export type Comment< C extends Context = 'edit' > = Record< string, any >;
	export type GlobalStylesRevision< C extends Context = 'edit' > = Record<
		string,
		any
	>;
	export type MenuLocation< C extends Context = 'edit' > = Record<
		string,
		any
	>;
	export type NavMenu< C extends Context = 'edit' > = Record< string, any >;
	export type NavMenuItem< C extends Context = 'edit' > = Record<
		string,
		any
	>;
	export type Plugin< C extends Context = 'edit' > = Record< string, any >;
	export type PostRevision< C extends Context = 'edit' > = Record<
		string,
		any
	>;
	export type PostStatusObject< C extends Context = 'edit' > = Record<
		string,
		any
	>;
	export type Sidebar< C extends Context = 'edit' > = Record< string, any >;
	export type Taxonomy< C extends Context = 'edit' > = Record< string, any >;
	export type Term< C extends Context = 'edit' > = Record< string, any >;
	export type Theme< C extends Context = 'edit' > = Record< string, any >;
	export type Type< C extends Context = 'edit' > = Record< string, any >;
	export type Widget< C extends Context = 'edit' > = Record< string, any >;
	export type WidgetType< C extends Context = 'edit' > = Record<
		string,
		any
	>;
	export type WpTemplatePart< C extends Context = 'edit' > = Record<
		string,
		any
	>;
	export type Base< C extends Context = 'edit' > = Record< string, any >;

	// --- EntityRecord union ---
	export interface PerPackageEntityRecords< C extends Context > {
		core:
			| User< C >
			| Post< C >
			| Settings
			| Page< C >
			| WpTemplate< C >
			| Attachment< C >
			| Comment< C >
			| GlobalStylesRevision< C >
			| MenuLocation< C >
			| NavMenu< C >
			| NavMenuItem< C >
			| Plugin< C >
			| PostRevision< C >
			| PostStatusObject< C >
			| Sidebar< C >
			| Taxonomy< C >
			| Term< C >
			| Theme< C >
			| Type< C >
			| Widget< C >
			| WidgetType< C >
			| WpTemplatePart< C >
			| Base< C >;
	}

	export type EntityRecord< C extends Context = 'edit' > =
		PerPackageEntityRecords< C >[ keyof PerPackageEntityRecords< C > ];

	export { Context };
}
