/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ActivityCard } from '../../activity-card';
import InboxNotePlaceholder from './placeholder';
import ActivityHeader from '../../activity-header';
import InboxNoteCard from './card';
import { EmptyContent, Section } from '@woocommerce/components';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';
import { getUnreadNotesCount, hasValidNotes } from './utils';

class InboxPanel extends Component {
	constructor( props ) {
		super( props );
		this.mountTime = Date.now();
	}

	componentWillUnmount() {
		const userDataFields = {
			activity_panel_inbox_last_read: this.mountTime,
		};
		this.props.updateCurrentUserData( userDataFields );
	}

	renderEmptyCard() {
		return (
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
	}

	renderNotes( hasNotes ) {
		const {
			isDismissUndoRequesting,
			isDismissAllUndoRequesting,
			lastRead,
			notes,
		} = this.props;

		if ( isDismissAllUndoRequesting ) {
			return;
		}

		if ( ! hasNotes ) {
			return this.renderEmptyCard();
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
	}

	render() {
		const {
			isError,
			isPanelEmpty,
			isRequesting,
			isUndoRequesting,
			isDismissAllUndoRequesting,
			lastRead,
			notes,
		} = this.props;

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

		return (
			<Fragment>
				{ isActivityHeaderVisible && (
					<ActivityHeader
						title={ __( 'Inbox', 'woocommerce-admin' ) }
						subtitle={ __(
							'Insights and growth tips for your business',
							'woocommerce-admin'
						) }
						unreadMessages={ getUnreadNotesCount(
							notes,
							lastRead
						) }
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
							this.renderNotes( hasNotes ) }
					</Section>
				</div>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( ( select ) => {
		const {
			getCurrentUserData,
			getNotes,
			getNotesError,
			isGetNotesRequesting,
			getUndoDismissRequesting,
		} = select( 'wc-api' );
		const userData = getCurrentUserData();
		const inboxQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			type: QUERY_DEFAULTS.noteTypes,
			orderby: 'date',
			order: 'desc',
			status: 'unactioned',
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
			lastRead: userData.activity_panel_inbox_last_read,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( InboxPanel );
