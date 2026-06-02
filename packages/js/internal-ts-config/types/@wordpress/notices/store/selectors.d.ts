/// <reference path="./actions.d.ts" />

declare module '@wordpress/notices/build-types/store/selectors' {
	/**
	 * @typedef {Object} WPNotice Notice object.
	 *
	 * @property {string}           id             Unique identifier of notice.
	 * @property {string}           status         Status of notice, one of `success`,
	 *                                             `info`, `error`, or `warning`. Defaults
	 *                                             to `info`.
	 * @property {string}           content        Notice message.
	 * @property {string}           spokenMessage  Audibly announced message text used by
	 *                                             assistive technologies.
	 * @property {string}           __unstableHTML Notice message as raw HTML. Intended to
	 *                                             serve primarily for compatibility of
	 *                                             server-rendered notices, and SHOULD NOT
	 *                                             be used for notices. It is subject to
	 *                                             removal without notice.
	 * @property {boolean}          isDismissible  Whether the notice can be dismissed by
	 *                                             user. Defaults to `true`.
	 * @property {string}           type           Type of notice, one of `default`,
	 *                                             or `snackbar`. Defaults to `default`.
	 * @property {boolean}          speak          Whether the notice content should be
	 *                                             announced to screen readers. Defaults to
	 *                                             `true`.
	 * @property {WPNoticeAction[]} actions        User actions to present with notice.
	 */
	/**
	 * Returns all notices as an array, optionally for a given context. Defaults to
	 * the global context.
	 *
	 * @param {Object}  state   Notices state.
	 * @param {?string} context Optional grouping context.
	 *
	 * @example
	 *
	 *```js
	 * import { useSelect } from '@wordpress/data';
	 * import { store as noticesStore } from '@wordpress/notices';
	 *
	 * const ExampleComponent = () => {
	 *     const notices = useSelect( ( select ) => select( noticesStore ).getNotices() );
	 *     return (
	 *         <ul>
	 *         { notices.map( ( notice ) => (
	 *             <li key={ notice.ID }>{ notice.content }</li>
	 *         ) ) }
	 *        </ul>
	 *    )
	 * };
	 *```
	 *
	 * @return {WPNotice[]} Array of notices.
	 */
	export function getNotices(state: Object, context?: string | null): WPNotice[];
	/**
	 * Notice object.
	 */
	export type WPNotice = {
	    /**
	     * Unique identifier of notice.
	     */
	    id: string;
	    /**
	     * Status of notice, one of `success`,
	     * `info`, `error`, or `warning`. Defaults
	     * to `info`.
	     */
	    status: string;
	    /**
	     * Notice message.
	     */
	    content: string;
	    /**
	     * Audibly announced message text used by
	     * assistive technologies.
	     */
	    spokenMessage: string;
	    /**
	     * Notice message as raw HTML. Intended to
	     * serve primarily for compatibility of
	     * server-rendered notices, and SHOULD NOT
	     * be used for notices. It is subject to
	     * removal without notice.
	     */
	    __unstableHTML: string;
	    /**
	     * Whether the notice can be dismissed by
	     * user. Defaults to `true`.
	     */
	    isDismissible: boolean;
	    /**
	     * Type of notice, one of `default`,
	     * or `snackbar`. Defaults to `default`.
	     */
	    type: string;
	    /**
	     * Whether the notice content should be
	     * announced to screen readers. Defaults to
	     * `true`.
	     */
	    speak: boolean;
	    /**
	     * User actions to present with notice.
	     */
	    actions: WPNoticeAction[];
	};
	export type WPNoticeAction = import("@wordpress/notices/build-types/store/actions").WPNoticeAction;
}
