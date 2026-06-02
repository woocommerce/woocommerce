/// <reference path="./attachment.d.ts" />
/// <reference path="./base-entity-records.d.ts" />
/// <reference path="./base.d.ts" />
/// <reference path="./comment.d.ts" />
/// <reference path="./global-styles-revision.d.ts" />
/// <reference path="./helpers.d.ts" />
/// <reference path="./menu-location.d.ts" />
/// <reference path="./nav-menu-item.d.ts" />
/// <reference path="./nav-menu.d.ts" />
/// <reference path="./page.d.ts" />
/// <reference path="./plugin.d.ts" />
/// <reference path="./post-revision.d.ts" />
/// <reference path="./post-status.d.ts" />
/// <reference path="./post.d.ts" />
/// <reference path="./settings.d.ts" />
/// <reference path="./sidebar.d.ts" />
/// <reference path="./taxonomy.d.ts" />
/// <reference path="./term.d.ts" />
/// <reference path="./theme.d.ts" />
/// <reference path="./type.d.ts" />
/// <reference path="./user.d.ts" />
/// <reference path="./widget-type.d.ts" />
/// <reference path="./widget.d.ts" />
/// <reference path="./wp-template-part.d.ts" />
/// <reference path="./wp-template.d.ts" />

declare module '@wordpress/core-data/build-types/entity-types' {
	/**
	 * Internal dependencies
	 */
	import type { Context, Updatable } from '@wordpress/core-data/build-types/entity-types/helpers';
	import type { Attachment } from '@wordpress/core-data/build-types/entity-types/attachment';
	import type { Base, TemplatePartArea, TemplateType } from '@wordpress/core-data/build-types/entity-types/base';
	import type { Comment } from '@wordpress/core-data/build-types/entity-types/comment';
	import type { GlobalStylesRevision } from '@wordpress/core-data/build-types/entity-types/global-styles-revision';
	import type { MenuLocation } from '@wordpress/core-data/build-types/entity-types/menu-location';
	import type { NavMenu } from '@wordpress/core-data/build-types/entity-types/nav-menu';
	import type { NavMenuItem } from '@wordpress/core-data/build-types/entity-types/nav-menu-item';
	import type { Page } from '@wordpress/core-data/build-types/entity-types/page';
	import type { Plugin } from '@wordpress/core-data/build-types/entity-types/plugin';
	import type { Post } from '@wordpress/core-data/build-types/entity-types/post';
	import type { PostStatusObject } from '@wordpress/core-data/build-types/entity-types/post-status';
	import type { PostRevision } from '@wordpress/core-data/build-types/entity-types/post-revision';
	import type { Settings } from '@wordpress/core-data/build-types/entity-types/settings';
	import type { Sidebar } from '@wordpress/core-data/build-types/entity-types/sidebar';
	import type { Taxonomy } from '@wordpress/core-data/build-types/entity-types/taxonomy';
	import type { Term } from '@wordpress/core-data/build-types/entity-types/term';
	import type { Theme } from '@wordpress/core-data/build-types/entity-types/theme';
	import type { User } from '@wordpress/core-data/build-types/entity-types/user';
	import type { Type } from '@wordpress/core-data/build-types/entity-types/type';
	import type { Widget } from '@wordpress/core-data/build-types/entity-types/widget';
	import type { WidgetType } from '@wordpress/core-data/build-types/entity-types/widget-type';
	import type { WpTemplate } from '@wordpress/core-data/build-types/entity-types/wp-template';
	import type { WpTemplatePart } from '@wordpress/core-data/build-types/entity-types/wp-template-part';
	export type { BaseEntityRecords } from '@wordpress/core-data/build-types/entity-types/base-entity-records';
	export type { Attachment, Base as UnstableBase, Comment, Context, GlobalStylesRevision, MenuLocation, NavMenu, NavMenuItem, Page, Plugin, Post, PostRevision, PostStatusObject, Settings, Sidebar, Taxonomy, TemplatePartArea, TemplateType, Term, Theme, Type, Updatable, User, Widget, WidgetType, WpTemplate, WpTemplatePart, };
	/**
	 * An interface that may be extended to add types for new entities. Each entry
	 * must be a union of entity definitions adhering to the EntityInterface type.
	 *
	 * Example:
	 *
	 * ```ts
	 * import type { Context } from '@wordpress/core-data';
	 * // ...
	 *
	 * interface Client {
	 *   id: number;
	 *   name: string;
	 *   // ...
	 * }
	 *
	 * interface Order< C extends Context > {
	 *   id: number;
	 *   name: string;
	 *   // ...
	 * }
	 *
	 * declare module '@wordpress/core-data' {
	 *     export interface PerPackageEntityRecords< C extends Context > {
	 *         myPlugin: Client | Order<C>>
	 *     }
	 * }
	 *
	 * const c = getEntityRecord<Order>( 'myPlugin', 'order', 15 );
	 * // c is of the type Order
	 * ```
	 */
	export interface PerPackageEntityRecords<C extends Context> {
	    core: Base<C> | Attachment<C> | Comment<C> | GlobalStylesRevision<C> | MenuLocation<C> | NavMenu<C> | NavMenuItem<C> | Page<C> | Plugin<C> | Post<C> | PostStatusObject<C> | PostRevision<C> | Settings<C> | Sidebar<C> | Taxonomy<C> | Term<C> | Theme<C> | User<C> | Type<C> | Widget<C> | WidgetType<C> | WpTemplate<C> | WpTemplatePart<C>;
	}
	/**
	 * A union of all known record types.
	 */
	export type EntityRecord<C extends Context = 'edit'> = PerPackageEntityRecords<C>[keyof PerPackageEntityRecords<C>];
}
