/**
 * External dependencies
 */
import {
	getContext as getContextFn,
	getElement,
	store,
} from '@wordpress/interactivity';

export type Notice = {
	notice: string;
	type: 'error' | 'success' | 'notice';
	dismissible: boolean;
};

type NoticeWithId = Notice & {
	id: string;
};

const getStoreNoticeContext = getContextFn< {
	notices: NoticeWithId[];
	notice: NoticeWithId;
} >;

// Todo: Go back to the Store Notices block context once more than one context
// can be added to an element (https://github.com/WordPress/gutenberg/discussions/62720).
const getProductCollectionContext = () =>
	getContextFn< {
		notices: NoticeWithId[];
	} >( 'woocommerce/product-collection' );

type StoreNoticesState = {
	get role(): string;
	get isError(): boolean;
	get isSuccess(): boolean;
	get isInfo(): boolean;
	get notices(): NoticeWithId[];
};

export type Store = {
	state: StoreNoticesState;
	actions: {
		addNotice: ( notice: Notice ) => string;
		removeNotice: ( noticeId: string | PointerEvent ) => void;
	};
	callbacks: {
		renderNoticeContent: () => void;
		scrollIntoView: () => void;
		injectIcon: () => void;
	};
};

const generateNoticeId = () => {
	// semi-random with low collision probability.
	return `${ Date.now() }-${ Math.random()
		.toString( 36 )
		.substring( 2, 15 ) }`;
};

const ICON_PATHS = {
	errorOrInfo:
		'M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z',
	success: 'M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z',
};

// Todo: export this store once the store is public.
const { state } = store< Store >(
	'woocommerce/store-notices',
	{
		state: {
			get role() {
				const context = getStoreNoticeContext();
				if (
					context.notice.type === 'error' ||
					context.notice.type === 'success'
				) {
					return 'alert';
				}

				return 'status';
			},
			get isError() {
				const { notice } = getStoreNoticeContext();
				return notice.type === 'error';
			},
			get isSuccess() {
				const { notice } = getStoreNoticeContext();
				return notice.type === 'success';
			},
			get isInfo() {
				const { notice } = getStoreNoticeContext();
				return notice.type === 'notice';
			},
			get notices() {
				const productCollectionContext = getProductCollectionContext();
				if ( productCollectionContext ) {
					return productCollectionContext?.notices;
				}

				const context = getStoreNoticeContext();

				if ( context && context.notices ) {
					return context.notices;
				}

				return [];
			},
		},
		actions: {
			addNotice: ( notice: Notice ): string => {
				const { notices } = state;

				// Prevent adding an extra notice with the same message.
				const existingNotice = notices.find(
					( n ) => n.notice === notice.notice
				);
				const noticeId = existingNotice
					? existingNotice.id
					: generateNoticeId();

				if ( ! existingNotice ) {
					notices.push( {
						...notice,
						id: noticeId,
					} );
				}

				return noticeId;
			},

			removeNotice: ( noticeId: string | PointerEvent ) => {
				const { notices } = state;

				noticeId =
					typeof noticeId === 'string'
						? noticeId
						: getStoreNoticeContext().notice.id;
				const index = notices.findIndex(
					( { id } ) => id === noticeId
				);
				if ( index !== -1 ) {
					notices.splice( index, 1 );
				}
			},
		},
		callbacks: {
			renderNoticeContent: () => {
				const context = getStoreNoticeContext();
				const { ref } = getElement();

				if ( ref ) {
					ref.innerHTML = context.notice.notice;
				}
			},

			scrollIntoView: () => {
				const { ref } = getElement();

				if ( ref ) {
					ref.scrollIntoView( { behavior: 'smooth' } );
				}
			},

			injectIcon: () => {
				const { ref } = getElement();
				if ( ! ref ) {
					return;
				}

				// Remove existing icon SVG if present (watch may run multiple times).
				const existingSvg = ref.querySelector( ':scope > svg' );
				if ( existingSvg ) {
					existingSvg.remove();
				}

				const svg = document.createElementNS(
					'http://www.w3.org/2000/svg',
					'svg'
				);
				svg.setAttribute( 'xmlns', 'http://www.w3.org/2000/svg' );
				svg.setAttribute( 'viewBox', '0 0 24 24' );
				svg.setAttribute( 'width', '24' );
				svg.setAttribute( 'height', '24' );
				svg.setAttribute( 'aria-hidden', 'true' );
				svg.setAttribute( 'focusable', 'false' );

				const path = document.createElementNS(
					'http://www.w3.org/2000/svg',
					'path'
				);
				path.setAttribute(
					'd',
					state.isError || state.isInfo
						? ICON_PATHS.errorOrInfo
						: ICON_PATHS.success
				);
				svg.appendChild( path );

				// Insert as first child.
				ref.prepend( svg );
			},
		},
	},
	{ lock: true }
);
