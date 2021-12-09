/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	EmptyContent,
	Section,
	Badge,
	EllipsisMenu,
} from '@woocommerce/components';
import { Card, CardHeader, Button } from '@wordpress/components';
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
import { ActivityCard } from '../header/activity-panel/activity-card';
import { hasValidNotes, truncateRenderableHTML } from './utils';
import { getScreenName } from '../utils';
import DismissAllModal from './dissmiss-all-modal';
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
	notes,
	onDismiss,
	onNoteActionClick,
	setShowDismissAllModal: onDismissAll,
	showHeader = true,
} ) => {
	if ( isBatchUpdating ) {
		return;
	}

	if ( ! hasNotes ) {
		return renderEmptyCard();
	}

	recordEvent( 'inbox_panel_view', {
		total: notes.length,
	} );

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
		<Card size="large">
			{ showHeader && (
				<CardHeader size="medium">
					<div className="wooocommerce-inbox-card__header">
						<Text size="20" lineHeight="28px" variant="title.small">
							{ __( 'Inbox', 'woocommerce-admin' ) }
						</Text>
						<Badge count={ notesArray.length } />
					</div>
					<EllipsisMenu
						label={ __(
							'Inbox Notes Options',
							'woocommerce-admin'
						) }
						renderContent={ ( { onToggle } ) => (
							<div className="woocommerce-inbox-card__section-controls">
								<Button
									onClick={ () => {
										onDismissAll( true );
										onToggle();
									} }
								>
									{ __( 'Dismiss all', 'woocommerce-admin' ) }
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
		</Card>
	);
};

const INBOX_QUERY = {
	page: 1,
	per_page: QUERY_DEFAULTS.pageSize,
	status: 'unactioned,actioned',
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

const InboxPanel = ( { showHeader = true } ) => {
	const { createNotice } = useDispatch( 'core/notices' );
	const { removeNote, updateNote, triggerNoteAction } = useDispatch(
		NOTES_STORE_NAME
	);

	const { isError, isResolvingNotes, isBatchUpdating, notes } = useSelect(
		( select ) => {
			const {
				getNotes,
				getNotesError,
				isResolving,
				isNotesRequesting,
			} = select( NOTES_STORE_NAME );
			const WC_VERSION_61_RELEASE_DATE = moment(
				'2022-01-11',
				'YYYY-MM-DD'
			).valueOf();

			const supportedLocales = [
				'en_US',
				'en_AU',
				'en_CA',
				'en_GB',
				'en_ZA',
			];

			return {
				notes: getNotes( INBOX_QUERY ).map( ( note ) => {
					const noteDate = moment(
						note.date_created_gmt,
						'YYYY-MM-DD'
					).valueOf();

					if (
						supportedLocales.includes( note.locale ) &&
						noteDate >= WC_VERSION_61_RELEASE_DATE
					) {
						note.content = truncateRenderableHTML(
							note.content,
							320
						);
					}
					return note;
				} ),
				isError: Boolean(
					getNotesError( 'getNotes', [ INBOX_QUERY ] )
				),
				isResolvingNotes: isResolving( 'getNotes', [ INBOX_QUERY ] ),
				isBatchUpdating: isNotesRequesting( 'batchUpdateNotes' ),
			};
		}
	);

	const [ showDismissAllModal, setShowDismissAllModal ] = useState( false );

	const onDismiss = ( note ) => {
		const screen = getScreenName();

		recordEvent( 'inbox_action_dismiss', {
			note_name: note.name,
			note_title: note.title,
			note_name_dismiss_all: false,
			note_name_dismiss_confirmation: true,
			screen,
		} );

		const noteId = note.id;
		try {
			removeNote( noteId );
			createNotice(
				'success',
				__( 'Message dismissed', 'woocommerce-admin' ),
				{
					actions: [
						{
							label: __( 'Undo', 'woocommerce-admin' ),
							onClick: () => {
								updateNote( noteId, {
									is_deleted: 0,
								} );
							},
						},
					],
				}
			);
		} catch ( e ) {
			createNotice(
				'error',
				_n(
					'Message could not be dismissed',
					'Messages could not be dismissed',
					1,
					'woocommerce-admin'
				)
			);
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
			{ showDismissAllModal && (
				<DismissAllModal
					onClose={ () => {
						setShowDismissAllModal( false );
					} }
				/>
			) }
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
							notes,
							onDismiss,
							onNoteActionClick,
							setShowDismissAllModal,
							showHeader,
						} ) }
				</Section>
			</div>
		</>
	);
};

export default InboxPanel;
