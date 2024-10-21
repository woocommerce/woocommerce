/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { useEffect, useState, useMemo } from '@wordpress/element';
import {
	EmptyContent,
	Section,
	Badge,
	EllipsisMenu,
} from '@woocommerce/components';
import { Card, CardHeader, Button, CardFooter } from '@wordpress/components';
import { NOTES_STORE_NAME, QUERY_DEFAULTS } from '@woocommerce/data';
import { useSelect, useDispatch } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import {
	InboxNoteCard,
	InboxNotePlaceholder,
	Text,
} from '@woocommerce/experimental';
import moment from 'moment';

/**
 * Internal dependencies
 */
import { ActivityCard } from '~/activity-panel/activity-card';
import { hasValidNotes, truncateRenderableHTML } from './utils';
import { getScreenName } from '../utils';
import DismissAllModal from './dismiss-all-modal';
import './index.scss';

const ADD_NOTES_AMOUNT = 10;
const DEFAULT_INBOX_QUERY = {
	page: 1,
	per_page: 5,
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
		'is_read',
		'locale',
	],
};

const supportedLocales = [ 'en_US', 'en_AU', 'en_CA', 'en_GB', 'en_ZA' ];

const WC_VERSION_61_RELEASE_DATE = moment(
	'2022-01-11',
	'YYYY-MM-DD'
).valueOf();

const renderEmptyCard = () => (
	<ActivityCard
		className="woocommerce-empty-activity-card"
		title={ __( 'Your inbox is empty', 'woocommerce' ) }
		icon={ false }
	>
		{ __(
			'As things begin to happen in your store your inbox will start to fill up. ' +
				'You’ll see things like achievements, new feature announcements, extension recommendations and more!',
			'woocommerce'
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

let hasFiredPanelViewTrack = false;

const renderNotes = ( {
	hasNotes,
	isBatchUpdating,
	notes,
	onDismiss,
	onNoteActionClick,
	onNoteVisible,
	setShowDismissAllModal: onDismissAll,
	showHeader = true,
	loadMoreNotes,
	allNotesFetched,
	notesHaveResolved,
	unreadNotesCount,
} ) => {
	if ( isBatchUpdating ) {
		return;
	}

	if ( ! hasNotes ) {
		return renderEmptyCard();
	}

	if ( ! hasFiredPanelViewTrack ) {
		recordEvent( 'inbox_panel_view', {
			total: notes.length,
		} );
		hasFiredPanelViewTrack = true;
	}

	const notesArray = Object.keys( notes ).map( ( key ) => notes[ key ] );

	return (
		<Card size="large">
			{ showHeader && (
				<CardHeader size="medium">
					<div className="woocommerce-inbox-card__header">
						<Text size="20" lineHeight="28px" variant="title.small">
							{ __( 'Inbox', 'woocommerce' ) }
						</Text>
						<Badge count={ unreadNotesCount } />
					</div>
					<EllipsisMenu
						label={ __( 'Inbox Notes Options', 'woocommerce' ) }
						renderContent={ ( { onToggle } ) => (
							<div className="woocommerce-inbox-card__section-controls">
								<Button
									onClick={ () => {
										onDismissAll( true );
										onToggle();
									} }
								>
									{ __( 'Dismiss all', 'woocommerce' ) }
								</Button>
							</div>
						) }
					/>
				</CardHeader>
			) }
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
								onDismiss={ onDismiss }
								onNoteActionClick={ onNoteActionClick }
								onBodyLinkClick={ onBodyLinkClick }
								onNoteVisible={ onNoteVisible }
							/>
						</CSSTransition>
					);
				} ) }
			</TransitionGroup>
			{ allNotesFetched
				? null
				: ( () => {
						if ( ! notesHaveResolved ) {
							return (
								<InboxNotePlaceholder className="banner message-is-unread" />
							);
						}

						return (
							<CardFooter
								className="woocommerce-inbox-card__footer"
								size="medium"
							>
								<Button
									isPrimary={ true }
									onClick={ () => {
										loadMoreNotes();
									} }
								>
									{ notesArray.length >
									DEFAULT_INBOX_QUERY.per_page
										? __( 'Show more', 'woocommerce' )
										: __( 'Show older', 'woocommerce' ) }
								</Button>
							</CardFooter>
						);
				  } )() }
		</Card>
	);
};

const InboxPanel = ( { showHeader = true } ) => {
	const [ noteDisplayQty, setNoteDisplayQty ] = useState(
		DEFAULT_INBOX_QUERY.per_page
	);
	const [ allNotesFetched, setAllNotesFetched ] = useState( false );
	const [ allNotes, setAllNotes ] = useState( [] );
	const [ viewedNotes, setViewedNotes ] = useState( {} );
	const { createNotice } = useDispatch( 'core/notices' );
	const {
		removeNote,
		updateNote,
		triggerNoteAction,
		invalidateResolutionForStoreSelector,
	} = useDispatch( NOTES_STORE_NAME );
	const screen = getScreenName();

	const inboxQuery = useMemo( () => {
		return {
			...DEFAULT_INBOX_QUERY,
			per_page: noteDisplayQty,
		};
	}, [ noteDisplayQty ] );

	const {
		isError,
		notes,
		notesHaveResolved,
		isBatchUpdating,
		unreadNotesCount,
	} = useSelect( ( select ) => {
		const {
			getNotes,
			getNotesError,
			isNotesRequesting,
			hasFinishedResolution,
		} = select( NOTES_STORE_NAME );

		return {
			notes: getNotes( inboxQuery ),
			unreadNotesCount: getNotes( {
				...DEFAULT_INBOX_QUERY,
				is_read: false,
				per_page: -1,
			} ).length,
			isError: Boolean( getNotesError( 'getNotes', [ inboxQuery ] ) ),
			isBatchUpdating: isNotesRequesting( 'batchUpdateNotes' ),
			notesHaveResolved:
				! isNotesRequesting( 'batchUpdateNotes' ) &&
				hasFinishedResolution( 'getNotes', [ inboxQuery ] ),
		};
	} );

	useEffect( () => {
		if ( notesHaveResolved && notes.length < noteDisplayQty ) {
			setAllNotesFetched( true );
		}

		if ( notesHaveResolved && notes.length ) {
			setAllNotes(
				notes.map( ( note ) => {
					const noteDate = moment(
						note.date_created_gmt,
						'YYYY-MM-DD'
					).valueOf();

					if (
						supportedLocales.includes( note.locale ) &&
						noteDate >= WC_VERSION_61_RELEASE_DATE
					) {
						return {
							...note,
							content: truncateRenderableHTML(
								note.content,
								320
							),
						};
					}
					return note;
				} )
			);
		}
	}, [ notes, notesHaveResolved ] );

	const [ showDismissAllModal, setShowDismissAllModal ] = useState( false );

	const onNoteVisible = ( note ) => {
		if ( ! viewedNotes[ note.id ] && ! note.is_read ) {
			setViewedNotes( { ...viewedNotes, [ note.id ]: true } );
			setTimeout( () => {
				updateNote( note.id, {
					is_read: true,
				} );
			}, 3000 );
		}
		recordEvent( 'inbox_note_view', {
			note_content: note.content,
			note_name: note.name,
			note_title: note.title,
			note_type: note.type,
			screen,
		} );
	};

	const onDismiss = async ( note ) => {
		recordEvent( 'inbox_action_dismiss', {
			note_name: note.name,
			note_title: note.title,
			note_name_dismiss_all: false,
			note_name_dismiss_confirmation: true,
			screen,
		} );

		const noteId = note.id;
		try {
			await removeNote( noteId );
			invalidateResolutionForStoreSelector( 'getNotes' );
			createNotice( 'success', __( 'Message dismissed', 'woocommerce' ), {
				actions: [
					{
						label: __( 'Undo', 'woocommerce' ),
						onClick: async () => {
							await updateNote( noteId, {
								is_deleted: 0,
							} );
							invalidateResolutionForStoreSelector( 'getNotes' );
						},
					},
				],
			} );
		} catch ( e ) {
			createNotice(
				'error',
				_n(
					'Message could not be dismissed',
					'Messages could not be dismissed',
					1,
					'woocommerce'
				)
			);
		}
	};

	if ( isError ) {
		const title = __(
			'There was an error getting your inbox. Please try again.',
			'woocommerce'
		);
		const actionLabel = __( 'Reload', 'woocommerce' );
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

	if ( notesHaveResolved && ! allNotes.length ) {
		return null;
	}

	return (
		<>
			{ showDismissAllModal && (
				<DismissAllModal
					onClose={ () => {
						setShowDismissAllModal( false );
					} }
				/>
			) }
			<div className="woocommerce-homepage-notes-wrapper">
				{ ! notesHaveResolved && ! allNotes.length && (
					<Section>
						<InboxNotePlaceholder className="banner message-is-unread" />
					</Section>
				) }
				<Section>
					{ Boolean( allNotes.length ) &&
						renderNotes( {
							loadMoreNotes: () => {
								recordEvent( 'inbox_action_load_more', {
									quantity_shown: allNotes.length,
								} );
								setNoteDisplayQty(
									noteDisplayQty + ADD_NOTES_AMOUNT
								);
							},
							hasNotes: hasValidNotes( allNotes ),
							isBatchUpdating,
							notes: allNotes,
							onDismiss,
							onNoteActionClick: ( note, action ) => {
								triggerNoteAction( note.id, action.id );
							},
							onNoteVisible,
							setShowDismissAllModal,
							showHeader,
							allNotesFetched,
							notesHaveResolved,
							unreadNotesCount,
						} ) }
				</Section>
			</div>
		</>
	);
};

export default InboxPanel;
