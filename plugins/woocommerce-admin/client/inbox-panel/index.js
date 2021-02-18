/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { EmptyContent, Section } from '@woocommerce/components';
import {
	NOTES_STORE_NAME,
	useUserPreferences,
	QUERY_DEFAULTS,
} from '@woocommerce/data';
import { withSelect } from '@wordpress/data';
import { CSSTransition, TransitionGroup } from 'react-transition-group';

/**
 * Internal dependencies
 */
import { ActivityCard } from '../header/activity-panel/activity-card';
import InboxNotePlaceholder from './placeholder';
import InboxNoteCard from './card';
import { hasValidNotes } from './utils';

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

const renderNotes = ( { hasNotes, isBatchUpdating, lastRead, notes } ) => {
	if ( isBatchUpdating ) {
		return;
	}

	if ( ! hasNotes ) {
		return renderEmptyCard();
	}

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
						/>
					</CSSTransition>
				);
			} ) }
		</TransitionGroup>
	);
};

const InboxPanel = ( props ) => {
	const { isError, isResolving, isBatchUpdating, notes } = props;
	const { updateUserPreferences, ...userPrefs } = useUserPreferences();
	const [ lastRead ] = useState( userPrefs.activity_panel_inbox_last_read );

	useEffect( () => {
		const mountTime = Date.now();

		const userDataFields = {
			activity_panel_inbox_last_read: mountTime,
		};
		updateUserPreferences( userDataFields );
	}, [] );

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
				{ ( isResolving || isBatchUpdating ) && (
					<Section>
						<InboxNotePlaceholder className="banner message-is-unread" />
					</Section>
				) }
				<Section>
					{ ! isResolving &&
						! isBatchUpdating &&
						renderNotes( {
							hasNotes,
							isBatchUpdating,
							lastRead,
							notes,
						} ) }
				</Section>
			</div>
		</>
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

export default compose(
	withSelect( ( select ) => {
		const {
			getNotes,
			getNotesError,
			isResolving,
			isNotesRequesting,
		} = select( NOTES_STORE_NAME );

		return {
			notes: getNotes( INBOX_QUERY ),
			isError: Boolean( getNotesError( 'getNotes', [ INBOX_QUERY ] ) ),
			isResolving: isResolving( 'getNotes', [ INBOX_QUERY ] ),
			isBatchUpdating: isNotesRequesting( 'batchUpdateNotes' ),
		};
	} )
)( InboxPanel );
