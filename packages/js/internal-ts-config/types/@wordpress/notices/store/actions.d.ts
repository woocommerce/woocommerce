declare module '@wordpress/notices/build-types/store/actions' {
	/**
	 * Returns an action object used in signalling that a notice is to be created.
	 *
	 * @param {string|undefined}      status                       Notice status ("info" if undefined is passed).
	 * @param {string}                content                      Notice message.
	 * @param {Object}                [options]                    Notice options.
	 * @param {string}                [options.context='global']   Context under which to
	 *                                                             group notice.
	 * @param {string}                [options.id]                 Identifier for notice.
	 *                                                             Automatically assigned
	 *                                                             if not specified.
	 * @param {boolean}               [options.isDismissible=true] Whether the notice can
	 *                                                             be dismissed by user.
	 * @param {string}                [options.type='default']     Type of notice, one of
	 *                                                             `default`, or `snackbar`.
	 * @param {boolean}               [options.speak=true]         Whether the notice
	 *                                                             content should be
	 *                                                             announced to screen
	 *                                                             readers.
	 * @param {Array<WPNoticeAction>} [options.actions]            User actions to be
	 *                                                             presented with notice.
	 * @param {string}                [options.icon]               An icon displayed with the notice.
	 *                                                             Only used when type is set to `snackbar`.
	 * @param {boolean}               [options.explicitDismiss]    Whether the notice includes
	 *                                                             an explicit dismiss button and
	 *                                                             can't be dismissed by clicking
	 *                                                             the body of the notice. Only applies
	 *                                                             when type is set to `snackbar`.
	 * @param {Function}              [options.onDismiss]          Called when the notice is dismissed.
	 *
	 * @example
	 * ```js
	 * import { __ } from '@wordpress/i18n';
	 * import { useDispatch } from '@wordpress/data';
	 * import { store as noticesStore } from '@wordpress/notices';
	 * import { Button } from '@wordpress/components';
	 *
	 * const ExampleComponent = () => {
	 *     const { createNotice } = useDispatch( noticesStore );
	 *     return (
	 *         <Button
	 *             onClick={ () => createNotice( 'success', __( 'Notice message' ) ) }
	 *         >
	 *             { __( 'Generate a success notice!' ) }
	 *         </Button>
	 *     );
	 * };
	 * ```
	 *
	 * @return {Object} Action object.
	 */
	export function createNotice(status: string | undefined, content: string, options?: {
	    context?: string | undefined;
	    id?: string | undefined;
	    isDismissible?: boolean | undefined;
	    type?: string | undefined;
	    speak?: boolean | undefined;
	    actions?: WPNoticeAction[] | undefined;
	    icon?: string | undefined;
	    explicitDismiss?: boolean | undefined;
	    onDismiss?: Function | undefined;
	}): Object;
	/**
	 * Returns an action object used in signalling that a success notice is to be
	 * created. Refer to `createNotice` for options documentation.
	 *
	 * @see createNotice
	 *
	 * @param {string} content   Notice message.
	 * @param {Object} [options] Optional notice options.
	 *
	 * @example
	 * ```js
	 * import { __ } from '@wordpress/i18n';
	 * import { useDispatch } from '@wordpress/data';
	 * import { store as noticesStore } from '@wordpress/notices';
	 * import { Button } from '@wordpress/components';
	 *
	 * const ExampleComponent = () => {
	 *     const { createSuccessNotice } = useDispatch( noticesStore );
	 *     return (
	 *         <Button
	 *             onClick={ () =>
	 *                 createSuccessNotice( __( 'Success!' ), {
	 *                     type: 'snackbar',
	 *                     icon: '🔥',
	 *                 } )
	 *             }
	 *         >
	 *             { __( 'Generate a snackbar success notice!' ) }
	 *        </Button>
	 *     );
	 * };
	 * ```
	 *
	 * @return {Object} Action object.
	 */
	export function createSuccessNotice(content: string, options?: Object): Object;
	/**
	 * Returns an action object used in signalling that an info notice is to be
	 * created. Refer to `createNotice` for options documentation.
	 *
	 * @see createNotice
	 *
	 * @param {string} content   Notice message.
	 * @param {Object} [options] Optional notice options.
	 *
	 * @example
	 * ```js
	 * import { __ } from '@wordpress/i18n';
	 * import { useDispatch } from '@wordpress/data';
	 * import { store as noticesStore } from '@wordpress/notices';
	 * import { Button } from '@wordpress/components';
	 *
	 * const ExampleComponent = () => {
	 *     const { createInfoNotice } = useDispatch( noticesStore );
	 *     return (
	 *         <Button
	 *             onClick={ () =>
	 *                createInfoNotice( __( 'Something happened!' ), {
	 *                   isDismissible: false,
	 *                } )
	 *             }
	 *         >
	 *         { __( 'Generate a notice that cannot be dismissed.' ) }
	 *       </Button>
	 *       );
	 * };
	 *```
	 *
	 * @return {Object} Action object.
	 */
	export function createInfoNotice(content: string, options?: Object): Object;
	/**
	 * Returns an action object used in signalling that an error notice is to be
	 * created. Refer to `createNotice` for options documentation.
	 *
	 * @see createNotice
	 *
	 * @param {string} content   Notice message.
	 * @param {Object} [options] Optional notice options.
	 *
	 * @example
	 * ```js
	 * import { __ } from '@wordpress/i18n';
	 * import { useDispatch } from '@wordpress/data';
	 * import { store as noticesStore } from '@wordpress/notices';
	 * import { Button } from '@wordpress/components';
	 *
	 * const ExampleComponent = () => {
	 *     const { createErrorNotice } = useDispatch( noticesStore );
	 *     return (
	 *         <Button
	 *             onClick={ () =>
	 *                 createErrorNotice( __( 'An error occurred!' ), {
	 *                     type: 'snackbar',
	 *                     explicitDismiss: true,
	 *                 } )
	 *             }
	 *         >
	 *             { __(
	 *                 'Generate a snackbar error notice with explicit dismiss button.'
	 *             ) }
	 *         </Button>
	 *     );
	 * };
	 * ```
	 *
	 * @return {Object} Action object.
	 */
	export function createErrorNotice(content: string, options?: Object): Object;
	/**
	 * Returns an action object used in signalling that a warning notice is to be
	 * created. Refer to `createNotice` for options documentation.
	 *
	 * @see createNotice
	 *
	 * @param {string} content   Notice message.
	 * @param {Object} [options] Optional notice options.
	 *
	 * @example
	 * ```js
	 * import { __ } from '@wordpress/i18n';
	 * import { useDispatch } from '@wordpress/data';
	 * import { store as noticesStore } from '@wordpress/notices';
	 * import { Button } from '@wordpress/components';
	 *
	 * const ExampleComponent = () => {
	 *     const { createWarningNotice, createInfoNotice } = useDispatch( noticesStore );
	 *     return (
	 *         <Button
	 *             onClick={ () =>
	 *                 createWarningNotice( __( 'Warning!' ), {
	 *                     onDismiss: () => {
	 *                         createInfoNotice(
	 *                             __( 'The warning has been dismissed!' )
	 *                         );
	 *                     },
	 *                 } )
	 *             }
	 *         >
	 *             { __( 'Generates a warning notice with onDismiss callback' ) }
	 *         </Button>
	 *     );
	 * };
	 * ```
	 *
	 * @return {Object} Action object.
	 */
	export function createWarningNotice(content: string, options?: Object): Object;
	/**
	 * Returns an action object used in signalling that a notice is to be removed.
	 *
	 * @param {string} id                 Notice unique identifier.
	 * @param {string} [context='global'] Optional context (grouping) in which the notice is
	 *                                    intended to appear. Defaults to default context.
	 *
	 * @example
	 * ```js
	 * import { __ } from '@wordpress/i18n';
	 * import { useDispatch } from '@wordpress/data';
	 * import { store as noticesStore } from '@wordpress/notices';
	 * import { Button } from '@wordpress/components';
	 *
	 * const ExampleComponent = () => {
	 *    const notices = useSelect( ( select ) => select( noticesStore ).getNotices() );
	 *    const { createWarningNotice, removeNotice } = useDispatch( noticesStore );
	 *
	 *    return (
	 *         <>
	 *             <Button
	 *                 onClick={ () =>
	 *                     createWarningNotice( __( 'Warning!' ), {
	 *                         isDismissible: false,
	 *                     } )
	 *                 }
	 *             >
	 *                 { __( 'Generate a notice' ) }
	 *             </Button>
	 *             { notices.length > 0 && (
	 *                 <Button onClick={ () => removeNotice( notices[ 0 ].id ) }>
	 *                     { __( 'Remove the notice' ) }
	 *                 </Button>
	 *             ) }
	 *         </>
	 *     );
	 *};
	 * ```
	 *
	 * @return {Object} Action object.
	 */
	export function removeNotice(id: string, context?: string): Object;
	/**
	 * Removes all notices from a given context. Defaults to the default context.
	 *
	 * @param {string} noticeType The context to remove all notices from.
	 * @param {string} context    The context to remove all notices from.
	 *
	 * @example
	 * ```js
	 * import { __ } from '@wordpress/i18n';
	 * import { useDispatch, useSelect } from '@wordpress/data';
	 * import { store as noticesStore } from '@wordpress/notices';
	 * import { Button } from '@wordpress/components';
	 *
	 * export const ExampleComponent = () => {
	 * 	const notices = useSelect( ( select ) =>
	 * 		select( noticesStore ).getNotices()
	 * 	);
	 * 	const { removeAllNotices } = useDispatch( noticesStore );
	 * 	return (
	 * 		<>
	 * 			<ul>
	 * 				{ notices.map( ( notice ) => (
	 * 					<li key={ notice.id }>{ notice.content }</li>
	 * 				) ) }
	 * 			</ul>
	 * 			<Button
	 * 				onClick={ () =>
	 * 					removeAllNotices()
	 * 				}
	 * 			>
	 * 				{ __( 'Clear all notices', 'woo-gutenberg-products-block' ) }
	 * 			</Button>
	 * 			<Button
	 * 				onClick={ () =>
	 * 					removeAllNotices( 'snackbar' )
	 * 				}
	 * 			>
	 * 				{ __( 'Clear all snackbar notices', 'woo-gutenberg-products-block' ) }
	 * 			</Button>
	 * 		</>
	 * 	);
	 * };
	 * ```
	 *
	 * @return {Object} 	   Action object.
	 */
	export function removeAllNotices(noticeType?: string, context?: string): Object;
	/**
	 * Returns an action object used in signalling that several notices are to be removed.
	 *
	 * @param {string[]} ids                List of unique notice identifiers.
	 * @param {string}   [context='global'] Optional context (grouping) in which the notices are
	 *                                      intended to appear. Defaults to default context.
	 * @example
	 * ```js
	 * import { __ } from '@wordpress/i18n';
	 * import { useDispatch, useSelect } from '@wordpress/data';
	 * import { store as noticesStore } from '@wordpress/notices';
	 * import { Button } from '@wordpress/components';
	 *
	 * const ExampleComponent = () => {
	 * 	const notices = useSelect( ( select ) =>
	 * 		select( noticesStore ).getNotices()
	 * 	);
	 * 	const { removeNotices } = useDispatch( noticesStore );
	 * 	return (
	 * 		<>
	 * 			<ul>
	 * 				{ notices.map( ( notice ) => (
	 * 					<li key={ notice.id }>{ notice.content }</li>
	 * 				) ) }
	 * 			</ul>
	 * 			<Button
	 * 				onClick={ () =>
	 * 					removeNotices( notices.map( ( { id } ) => id ) )
	 * 				}
	 * 			>
	 * 				{ __( 'Clear all notices' ) }
	 * 			</Button>
	 * 		</>
	 * 	);
	 * };
	 * ```
	 * @return {Object} Action object.
	 */
	export function removeNotices(ids: string[], context?: string): Object;
	/**
	 * Object describing a user action option associated with a notice.
	 */
	export type WPNoticeAction = {
	    /**
	     * Message to use as action label.
	     */
	    label: string;
	    /**
	     * Optional URL of resource if action incurs
	     * browser navigation.
	     */
	    url: string | null;
	    /**
	     * Optional function to invoke when action is
	     * triggered by user.
	     */
	    onClick: Function | null;
	};
}
