/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment, useEffect } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { EmptyContent, Section } from '@woocommerce/components';
import { useUserPreferences } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ActivityCard } from '../../activity-card';
import InboxNotePlaceholder from './placeholder';
import ActivityHeader from '../../activity-header';
import InboxNoteCard from './card';
import { QUERY_DEFAULTS } from '../../../../wc-api/constants';
import withSelect from '../../../../wc-api/with-select';
import { getUnreadNotesCount, hasValidNotes } from './utils';

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

const renderNotes = ( {
	hasNotes,
	isDismissUndoRequesting,
	isDismissAllUndoRequesting,
	lastRead,
	notes,
} ) => {
	if ( isDismissAllUndoRequesting ) {
		return;
	}

	if ( ! hasNotes ) {
		return renderEmptyCard();
	}

	const notesArray = Object.keys( notes ).map( ( key ) => notes[ key ] );

	return notesArray.map( ( note ) => {
		if ( isDismissUndoRequesting === note.id ) {
			return (
				<InboxNotePlaceholder
					className="banner message-is-unread"
					key={ note.id }
				/>
			);
		}
		return (
			<InboxNoteCard
				key={ note.id }
				note={ note }
				lastRead={ lastRead }
			/>
		);
	} );
};

const InboxPanel = ( props ) => {
	const {
		isError,
		isPanelEmpty,
		isRequesting,
		isUndoRequesting,
		isDismissAllUndoRequesting,
		isDismissUndoRequesting,
		notes,
	} = props;
	const { updateUserPreferences, ...userPrefs } = useUserPreferences();
	const lastRead = userPrefs.activity_panel_inbox_last_read;

	useEffect( () => {
		const mountTime = Date.now();

		return () => {
			const userDataFields = {
				activity_panel_inbox_last_read: mountTime,
			};
			updateUserPreferences( userDataFields );
		};
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
			<Fragment>
				<EmptyContent
					title={ title }
					actionLabel={ actionLabel }
					actionURL={ null }
					actionCallback={ actionCallback }
				/>
			</Fragment>
		);
	}

	const hasNotes = hasValidNotes( notes );

	const isActivityHeaderVisible =
		hasNotes || isRequesting || isUndoRequesting;

	if ( isPanelEmpty ) {
		isPanelEmpty( ! hasNotes && ! isActivityHeaderVisible );
	}

	// @todo After having a pagination implemented we should call the method "getNotes" with a different query since
	// the current one is only getting 25 notes and the count of unread notes only will refer to this 25 and not all the existing ones.
	return (
		<Fragment>
			{ isActivityHeaderVisible && (
				<ActivityHeader
					title={ __( 'Inbox', 'woocommerce-admin' ) }
					subtitle={ __(
						'Insights and growth tips for your business',
						'woocommerce-admin'
					) }
					unreadMessages={ getUnreadNotesCount( notes, lastRead ) }
				/>
			) }
			<div className="woocommerce-homepage-notes-wrapper">
				{ ( isRequesting || isDismissAllUndoRequesting ) && (
					<Section>
						<InboxNotePlaceholder className="banner message-is-unread" />
					</Section>
				) }
				<Section>
					{ ! isRequesting &&
						! isDismissAllUndoRequesting &&
						renderNotes( {
							hasNotes,
							isDismissUndoRequesting,
							isDismissAllUndoRequesting,
							lastRead,
							notes,
						} ) }
				</Section>
			</div>
		</Fragment>
	);
};

export default compose(
	withSelect( ( select ) => {
		const {
			getNotes,
			getNotesError,
			isGetNotesRequesting,
			getUndoDismissRequesting,
		} = select( 'wc-api' );
		const inboxQuery = {
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

		const notes = getNotes( inboxQuery );
		const isError = Boolean( getNotesError( inboxQuery ) );
		const isRequesting = isGetNotesRequesting( inboxQuery );
		const {
			isUndoRequesting,
			isDismissUndoRequesting,
			isDismissAllUndoRequesting,
		} = getUndoDismissRequesting();

		return {
			notes,
			isError,
			isRequesting,
			isUndoRequesting,
			isDismissUndoRequesting,
			isDismissAllUndoRequesting,
		};
	} )
)( InboxPanel );
