/**
 * External dependencies
 */
import {
	getContext as getContextFn,
	getElement,
	store,
} from '@wordpress/interactivity';

type Notice = {
	notice: string;
	type: 'error' | 'success' | 'notice';
	dismissible: boolean;
};

type NoticeWithId = Notice & {
	id: string;
};

const getContext = getContextFn< {
	notice: NoticeWithId;
} >;

type StoreNoticesState = {
	notices: NoticeWithId[];
	get role(): string;
	get iconPath(): string;
	get isError(): boolean;
	get isSuccess(): boolean;
	get isInfo(): boolean;
};

export type StoreNoticesStore = {
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

const ALERT_ICON_PATH =
	'M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z';

const ICON_PATHS = {
	error: ALERT_ICON_PATH,
	success: 'M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z',
	notice: ALERT_ICON_PATH,
};

const generateNoticeId = () => {
	// semi-random with low collision probability.
	return `${ Date.now() }-${ Math.random()
		.toString( 36 )
		.substring( 2, 15 ) }`;
};

const { state } = store< StoreNoticesStore >(
	'woocommerce/product-collection-notices',
	{
		state: {
			get role() {
				const context = getContext();
				if (
					context.notice.type === 'error' ||
					context.notice.type === 'success'
				) {
					return 'alert';
				}

				return 'status';
			},
			get iconPath() {
				const context = getContext();
				const noticeType = context.notice.type;
				return ICON_PATHS[ noticeType ];
			},
			get isError() {
				const { notice } = getContext();
				return notice.type === 'error';
			},
			get isSuccess() {
				const { notice } = getContext();
				return notice.type === 'success';
			},
			get isInfo() {
				const { notice } = getContext();
				return notice.type === 'notice';
			},
		},
		actions: {
			addNotice: ( notice: Notice ) => {
				const noticeId = generateNoticeId();
				const noticeWithId = {
					...notice,
					id: noticeId,
				};
				state.notices.push( noticeWithId );

				return noticeId;
			},

			removeNotice: ( noticeId: string | PointerEvent ) => {
				noticeId =
					typeof noticeId === 'string'
						? noticeId
						: getContext().notice.id;
				const index = state.notices.findIndex(
					( { id } ) => id === noticeId
				);
				if ( index !== -1 ) {
					state.notices.splice( index, 1 );
				}
			},
		},
		callbacks: {
			renderNoticeContent: () => {
				const context = getContext();
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
