/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { EmptyContent, Section } from '@woocommerce/components';
import {
	NOTES_STORE_NAME,
	useUserPreferences,
	QUERY_DEFAULTS,
} from '@woocommerce/data';
import { useSelect, useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import {
	InboxNoteCard,
	InboxDismissConfirmationModal,
	InboxNotePlaceholder,
} from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import { ActivityCard } from '../header/activity-panel/activity-card';
import { hasValidNotes } from './utils';
import { getScreenName } from '../utils';
import './index.scss';

const renderEmptyCard = () => (
	<ActivityCard
		className="woocommerce-empty-activity-card"
		title={ __( 'Your inbox is empty', 'woocommerce-admin' ) }
		icon={ false }
	>
		{ __(
			'As things begin to happen in your store your inbox will start to fill up. ' +
				"You'll see things like achievements, new feature announcements, extension recommendations and more!",
			'woocommerce-admin'
		) }
	</ActivityCard>
);

const onBodyLinkClick = ( note, innerLink ) => {
	recordEvent( 'inbox_action_click', {
		note_name: note.name,
		note_title: note.title,
		note_content_inner_link: innerLink,
	} );
};

const renderNotes = ( {
	hasNotes,
	isBatchUpdating,
	lastRead,
	notes,
	onDismiss,
	onNoteActionClick,
} ) => {
	if ( isBatchUpdating ) {
		return;
	}

	if ( ! hasNotes ) {
		return renderEmptyCard();
	}

	const screen = getScreenName();
	const onNoteVisible = ( note ) => {
		recordEvent( 'inbox_note_view', {
			note_content: note.content,
			note_name: note.name,
			note_title: note.title,
			note_type: note.type,
			screen,
		} );
	};

	const notesArray = Object.keys( notes ).map( ( key ) => notes[ key ] );

	return (
		<TransitionGroup role="menu">
			{ notesArray.map( ( note ) => {
				const { id: noteId, is_deleted: isDeleted } = note;
				if ( isDeleted ) {
					return null;
				}
				return (
					<CSSTransition
						key={ noteId }
						timeout={ 500 }
						classNames="woocommerce-inbox-message"
					>
						<InboxNoteCard
							key={ noteId }
							note={ note }
							lastRead={ lastRead }
							onDismiss={ onDismiss }
							onNoteActionClick={ onNoteActionClick }
							onBodyLinkClick={ onBodyLinkClick }
							onNoteVisible={ onNoteVisible }
						/>
					</CSSTransition>
				);
			} ) }
		</TransitionGroup>
	);
};

const INBOX_QUERY = {
	page: 1,
	per_page: QUERY_DEFAULTS.pageSize,
	status: 'unactioned',
	type: QUERY_DEFAULTS.noteTypes,
	orderby: 'date',
	order: 'desc',
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
	],
};

const InboxPanel = () => {
	const { createNotice } = useDispatch( 'core/notices' );
	const {
		batchUpdateNotes,
		removeAllNotes,
		removeNote,
		updateNote,
		triggerNoteAction,
	} = useDispatch( NOTES_STORE_NAME );
	const { isError, isResolvingNotes, isBatchUpdating, notes } = useSelect(
		( select ) => {
			const {
				getNotes,
				getNotesError,
				isResolving,
				isNotesRequesting,
			} = select( NOTES_STORE_NAME );

			return {
				notes: getNotes( INBOX_QUERY ),
				isError: Boolean(
					getNotesError( 'getNotes', [ INBOX_QUERY ] )
				),
				isResolvingNotes: isResolving( 'getNotes', [ INBOX_QUERY ] ),
				isBatchUpdating: isNotesRequesting( 'batchUpdateNotes' ),
			};
		}
	);
	const { updateUserPreferences, ...userPrefs } = useUserPreferences();
	const [ lastRead ] = useState( userPrefs.activity_panel_inbox_last_read );
	const [ dismiss, setDismiss ] = useState();

	useEffect( () => {
		const mountTime = Date.now();

		const userDataFields = {
			activity_panel_inbox_last_read: mountTime,
		};
		updateUserPreferences( userDataFields );
	}, [] );

	const onDismiss = ( note, type ) => {
		setDismiss( { note, type } );
	};

	const closeDismissModal = async ( confirmed = false ) => {
		const noteNameDismissAll = dismiss.type === 'all' ? true : false;
		const screen = getScreenName();

		recordEvent( 'inbox_action_dismiss', {
			note_name: dismiss.note.name,
			note_title: dismiss.note.title,
			note_name_dismiss_all: noteNameDismissAll,
			note_name_dismiss_confirmation: confirmed,
			screen,
		} );

		if ( confirmed ) {
			const noteId = dismiss.note.id;
			const removeAll = ! noteId || noteNameDismissAll;
			try {
				let notesRemoved = [];
				if ( removeAll ) {
					notesRemoved = await removeAllNotes( {
						status: INBOX_QUERY.status,
					} );
				} else {
					const noteRemoved = await removeNote( noteId );
					notesRemoved = [ noteRemoved ];
				}
				setDismiss( undefined );
				createNotice(
					'success',
					notesRemoved.length > 1
						? __( 'All messages dismissed', 'woocommerce-admin' )
						: __( 'Message dismissed', 'woocommerce-admin' ),
					{
						actions: [
							{
								label: __( 'Undo', 'woocommerce-admin' ),
								onClick: () => {
									if ( notesRemoved.length > 1 ) {
										batchUpdateNotes(
											notesRemoved.map(
												( note ) => note.id
											),
											{
												is_deleted: 0,
											}
										);
									} else {
										updateNote( noteId, {
											is_deleted: 0,
										} );
									}
								},
							},
						],
					}
				);
			} catch ( e ) {
				const numberOfNotes = removeAll ? notes.length : 1;
				createNotice(
					'error',
					_n(
						'Message could not be dismissed',
						'Messages could not be dismissed',
						numberOfNotes,
						'woocommerce-admin'
					)
				);
				setDismiss( undefined );
			}
		} else {
			setDismiss( undefined );
		}
	};

	const onNoteActionClick = ( note, action ) => {
		triggerNoteAction( note.id, action.id );
	};

	if ( isError ) {
		const title = __(
			'There was an error getting your inbox. Please try again.',
			'woocommerce-admin'
		);
		const actionLabel = __( 'Reload', 'woocommerce-admin' );
		const actionCallback = () => {
			// @todo Add tracking for how often an error is displayed, and the reload action is clicked.
			window.location.reload();
		};

		return (
			<EmptyContent
				title={ title }
				actionLabel={ actionLabel }
				actionURL={ null }
				actionCallback={ actionCallback }
			/>
		);
	}

	const hasNotes = hasValidNotes( notes );

	// @todo After having a pagination implemented we should call the method "getNotes" with a different query since
	// the current one is only getting 25 notes and the count of unread notes only will refer to this 25 and not all the existing ones.
	return (
		<>
			<div className="woocommerce-homepage-notes-wrapper">
				{ ( isResolvingNotes || isBatchUpdating ) && (
					<Section>
						<InboxNotePlaceholder className="banner message-is-unread" />
					</Section>
				) }
				<Section>
					{ ! isResolvingNotes &&
						! isBatchUpdating &&
						renderNotes( {
							hasNotes,
							isBatchUpdating,
							lastRead,
							notes,
							onDismiss,
							onNoteActionClick,
						} ) }
				</Section>
				{ dismiss && (
					<InboxDismissConfirmationModal
						onClose={ closeDismissModal }
						onDismiss={ () => closeDismissModal( true ) }
					/>
				) }
			</div>
		</>
	);
};

export default InboxPanel;
