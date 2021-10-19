import { Model } from './model';
/**
 * A constructor for a model.
 *
 * @typedef ModelConstructor
 * @alias Function.<T>
 * @template T
 */
export declare type ModelConstructor<T extends Model> = new (properties: Partial<T>) => T;
/**
 * A post's status.
 *
 * @typedef PostStatus
 * @alias 'draft'|'pending'|'private'|'publish'|string
 */
export declare type PostStatus = 'draft' | 'pending' | 'private' | 'publish' | string;
/**
 * A metadata object.
 */
export declare class MetaData extends Model {
    /**
     * The key of the metadata.
     *
     * @type {string}
     */
    readonly key: string;
    /**
     * The value of the metadata.
     *
     * @type {*}
     */
    readonly value: any;
    /**
     * The key of the metadata.
     *
     * @type {string}
     */
    readonly displayKey?: string;
    /**
     * The value of the metadata.
     *
     * @type {*}
     */
    readonly displayValue?: string;
    /**
     * Creates a new metadata.
     *
     * @param {Partial.<MetaData>} properties The properties to set.
     */
    constructor(properties?: Partial<MetaData>);
}
/**
 * An object link item.
 */
declare class LinkItem {
    /**
     * The href of the link.
     *
     * @type {ReadonlyArray.<string>}
     */
    readonly href: string;
    /**
     * Creates a new product link item.
     *
     * @param {Partial.<LinkItem>} properties The properties to set.
     */
    constructor(properties?: Partial<LinkItem>);
}
/**
 * An object's links.
 */
export declare class ObjectLinks {
    /**
     * The collection containing the object.
     *
     * @type {ReadonlyArray.<LinkItem>}
     */
    readonly collection: readonly LinkItem[];
    /**
     * Self referential link to the object.
     *
     * @type {ReadonlyArray.<LinkItem>}
     */
    readonly self: readonly LinkItem[];
    /**
     * The link to the parent object.
     *
     * @type {ReadonlyArray.<LinkItem>}
     */
    readonly up?: readonly LinkItem[];
    /**
     * Creates a new product link list.
     *
     * @param {Partial.<ObjectLinks>} properties The properties to set.
     */
    constructor(properties?: Partial<ObjectLinks>);
}
export {};
//# sourceMappingURL=shared-types.d.ts.map