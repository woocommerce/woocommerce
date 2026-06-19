/**
 * External dependencies
 */
import { dispatch, useSelect } from '@wordpress/data';
import { useRef, useState } from '@wordpress/element';
import { __, _n } from '@wordpress/i18n';
import { notesStore, QUERY_DEFAULTS } from '@woocommerce/data';
import { Section } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import {
	InboxDismissConfirmationModal,
	InboxNoteCard,
	InboxNotePlaceholder,
} from '@woocommerce/experimental';
import type { InboxNote, InboxNoteAction } from '@woocommerce/experimental';

const INBOX_QUERY = {
	page: 1,
	per_page: QUERY_DEFAULTS.pageSize,
	status: 'unactioned',
	type: QUERY_DEFAULTS.noteTypes,
	orderby: 'date',
	order: 'desc',
	source: 'woocommerce-payments',
	_fields: [
		'id',
		'name',
		'title',
		'content',
		'type',
		'status',
		'actions',
		'date_created',
		'date_created_gmt',
		'layout',
		'image',
		'is_deleted',
		'is_read',
		'source',
	],
} as const;

type WooPaymentsInboxNoteAction = {
	id: number;
	url: string;
	label: string;
	actioned_text?: string;
};

type WooPaymentsInboxNote = {
	id: number;
	status: string;
	title: string;
	name: string;
	content: string;
	date_created: string;
	date_created_gmt: string;
	actions?: WooPaymentsInboxNoteAction[];
	is_deleted: boolean;
	type: string;
	is_read: boolean;
	layout?: string;
	image?: string;
};

type NotesSelector = {
	getNotes: ( query: typeof INBOX_QUERY ) => WooPaymentsInboxNote[];
	getNotesError: ( selector: string, args: unknown[] ) => unknown;
	isResolving?: ( selector: string, args: unknown[] ) => boolean;
	hasFinishedResolution?: ( selector: string, args: unknown[] ) => boolean;
};

const getInboxNote = ( note: WooPaymentsInboxNote ): InboxNote => ( {
	id: note.id,
	status: note.status,
	title: note.title,
	name: note.name,
	content: note.content,
	date_created: note.date_created,
	date_created_gmt: note.date_created_gmt,
	actions: ( note.actions ?? [] ).map(
		( action ): InboxNoteAction => ( {
			id: action.id,
			url: action.url,
			label: action.label,
			primary: false,
			actioned_text: action.actioned_text
				? Boolean( action.actioned_text )
				: undefined,
		} )
	),
	is_deleted: note.is_deleted,
	type: note.type,
	is_read: note.is_read,
	layout: note.layout,
	image: note.image,
} );

const getNotesDispatch = () =>
	dispatch( notesStore ) as {
		removeNote: ( noteId: number ) => Promise< WooPaymentsInboxNote >;
		triggerNoteAction: ( noteId: number, actionId: number ) => void;
		updateNote: (
			noteId: number,
			noteFields: Partial< WooPaymentsInboxNote >
		) => void;
	};

const getNoticesDispatch = () =>
	dispatch( 'core/notices' ) as unknown as {
		createSuccessNotice: ( message: string, options?: unknown ) => void;
		createErrorNotice: ( message: string ) => void;
	};

export const InboxNotifications = () => {
	const [ noteToDismiss, setNoteToDismiss ] = useState< InboxNote | null >(
		null
	);
	const [ shouldPreserveEmptyState, setShouldPreserveEmptyState ] =
		useState( false );
	const headingRef = useRef< HTMLHeadingElement >( null );
	const noteToDismissElementRef = useRef< HTMLElement | null >( null );
	const { isError, isLoading, notes } = useSelect( ( select ) => {
		const store = select( notesStore ) as unknown as NotesSelector;
		const args = [ INBOX_QUERY ];

		return {
			notes: store.getNotes( INBOX_QUERY ),
			isError: Boolean( store.getNotesError( 'getNotes', args ) ),
			isLoading: store.isResolving
				? store.isResolving( 'getNotes', args )
				: ! store.hasFinishedResolution?.( 'getNotes', args ),
		};
	}, [] );
	const visibleNotes = notes.filter( ( note ) => ! note.is_deleted );
	const shouldRestoreFocusAfterDismissal = () => {
		const ownerDocument = headingRef.current?.ownerDocument;
		const activeElement = ownerDocument?.activeElement;

		if ( ! ownerDocument || ! activeElement ) {
			return false;
		}

		return (
			activeElement === ownerDocument.body ||
			!! activeElement.closest( '[role="dialog"]' ) ||
			!! noteToDismissElementRef.current?.contains( activeElement )
		);
	};
	const focusInboxHeading = () => {
		const requestAnimationFrame =
			headingRef.current?.ownerDocument.defaultView
				?.requestAnimationFrame ?? window.requestAnimationFrame;

		requestAnimationFrame( () => headingRef.current?.focus() );
	};
	const openDismissConfirmation = ( note: InboxNote ) => {
		const activeElement = headingRef.current?.ownerDocument.activeElement;
		const noteElement = activeElement?.closest(
			'.woocommerce-inbox-message'
		) as HTMLElement | null | undefined;

		noteToDismissElementRef.current = noteElement ?? null;
		setNoteToDismiss( note );
	};
	const dismissNote = async () => {
		if ( ! noteToDismiss ) {
			return;
		}

		const shouldPreserveEmptyStateAfterDismissal =
			visibleNotes.length === 1;

		if ( shouldPreserveEmptyStateAfterDismissal ) {
			setShouldPreserveEmptyState( true );
		}

		try {
			const removedNote = await getNotesDispatch().removeNote(
				noteToDismiss.id
			);
			const shouldRestoreFocus = shouldRestoreFocusAfterDismissal();
			setNoteToDismiss( null );
			noteToDismissElementRef.current = null;

			if ( shouldRestoreFocus ) {
				focusInboxHeading();
			}

			getNoticesDispatch().createSuccessNotice(
				__( 'Message dismissed', 'woocommerce' ),
				{
					actions: [
						{
							label: __( 'Undo', 'woocommerce' ),
							onClick: () =>
								getNotesDispatch().updateNote( removedNote.id, {
									is_deleted: false,
								} ),
						},
					],
				}
			);
		} catch ( error ) {
			setNoteToDismiss( null );
			noteToDismissElementRef.current = null;
			if ( shouldPreserveEmptyStateAfterDismissal ) {
				setShouldPreserveEmptyState( false );
			}
			getNoticesDispatch().createErrorNotice(
				_n(
					'Message could not be dismissed',
					'Messages could not be dismissed',
					1,
					'woocommerce'
				)
			);
		}
	};

	if ( isLoading ) {
		return (
			<section
				className="woocommerce-woopayments-overview-card woocommerce-woopayments-inbox-notifications"
				aria-busy
				aria-labelledby="woocommerce-woopayments-inbox-heading"
			>
				<h2 id="woocommerce-woopayments-inbox-heading">
					{ __( 'Inbox', 'woocommerce' ) }
				</h2>
				<p className="screen-reader-text" role="status">
					{ __( 'Loading inbox notifications…', 'woocommerce' ) }
				</p>
				<InboxNotePlaceholder className="banner message-is-unread" />
			</section>
		);
	}

	if ( isError ) {
		return (
			<section
				className="woocommerce-woopayments-overview-card woocommerce-woopayments-inbox-notifications"
				aria-labelledby="woocommerce-woopayments-inbox-heading"
			>
				<h2 id="woocommerce-woopayments-inbox-heading">
					{ __( 'Inbox', 'woocommerce' ) }
				</h2>
				<p role="status">
					{ __(
						'There was an error getting your inbox. Please try again.',
						'woocommerce'
					) }
				</p>
			</section>
		);
	}

	if ( visibleNotes.length === 0 && ! shouldPreserveEmptyState ) {
		return null;
	}

	return (
		<>
			<section
				className="woocommerce-woopayments-overview-card woocommerce-woopayments-inbox-notifications"
				aria-labelledby="woocommerce-woopayments-inbox-heading"
			>
				<h2
					id="woocommerce-woopayments-inbox-heading"
					ref={ headingRef }
					tabIndex={ -1 }
				>
					{ __( 'Inbox', 'woocommerce' ) }
				</h2>
				{ visibleNotes.length === 0 ? (
					<p role="status">
						{ __( 'No inbox notifications.', 'woocommerce' ) }
					</p>
				) : (
					<Section component={ false }>
						<div className="woocommerce-woopayments-inbox-notifications__list">
							{ visibleNotes.map( ( note ) => {
								const inboxNote = getInboxNote( note );

								return (
									<InboxNoteCard
										key={ inboxNote.id }
										note={ inboxNote }
										onDismiss={ openDismissConfirmation }
										onNoteActionClick={ (
											selectedNote,
											action
										) =>
											getNotesDispatch().triggerNoteAction(
												selectedNote.id,
												action.id
											)
										}
										onBodyLinkClick={ (
											selectedNote,
											innerLink
										) =>
											recordEvent(
												'wcpay_inbox_action_click',
												{
													note_name:
														selectedNote.name,
													note_title:
														selectedNote.title,
													note_content_inner_link:
														innerLink,
												}
											)
										}
										onNoteVisible={ ( selectedNote ) =>
											recordEvent(
												'wcpay_inbox_note_view',
												{
													note_content:
														selectedNote.content,
													note_name:
														selectedNote.name,
													note_title:
														selectedNote.title,
													note_type:
														selectedNote.type,
												}
											)
										}
									/>
								);
							} ) }
						</div>
					</Section>
				) }
			</section>
			{ noteToDismiss && (
				<InboxDismissConfirmationModal
					onClose={ () => setNoteToDismiss( null ) }
					onDismiss={ dismissNote }
				/>
			) }
		</>
	);
};
