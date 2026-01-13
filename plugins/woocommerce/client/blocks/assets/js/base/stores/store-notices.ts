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
	get isErrorOrInfo(): boolean;
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
	};
};

const generateNoticeId = () => {
	// semi-random with low collision probability.
	return `${ Date.now() }-${ Math.random()
		.toString( 36 )
		.substring( 2, 15 ) }`;
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
			get isErrorOrInfo(): boolean {
				return state.isError || state.isInfo;
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
		},
	},
	{ lock: true }
);
