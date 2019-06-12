/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
import { withDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../activity-card';
import ActivityHeader from '../activity-header';
import { EmptyContent, Section } from '@woocommerce/components';
import sanitizeHTML from 'lib/sanitize-html';
import { QUERY_DEFAULTS } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';
import classnames from 'classnames';

class InboxPanel extends Component {
	constructor( props ) {
		super( props );
		this.mountTime = Date.now();
	}

	componentWillUnmount() {
		const userDataFields = {
			[ 'activity_panel_inbox_last_read' ]: this.mountTime,
		};
		this.props.updateCurrentUserData( userDataFields );
	}

	renderEmptyCard() {
		return (
			<ActivityCard
				className="woocommerce-empty-activity-card"
				title={ __( 'Your inbox is empty', 'woocommerce-admin' ) }
				icon={ <Gridicon icon="checkmark" size={ 48 } /> }
			>
				{ __(
					'As things begin to happen in your store your inbox will start to fill up. ' +
						"You'll see things like achievements, new feature announcements, extension recommendations and more!",
					'woocommerce-admin'
				) }
			</ActivityCard>
		);
	}

	renderNotes() {
		const { lastRead, notes, triggerNoteAction } = this.props;

		if ( 0 === Object.keys( notes ).length ) {
			return this.renderEmptyCard();
		}

		const getButtonsFromActions = note => {
			if ( ! note.actions ) {
				return [];
			}
			return note.actions.map( action => (
				<Button
					isDefault
					isPrimary={ action.primary }
					href={ action.url || undefined }
					onClick={ () => triggerNoteAction( note.id, action.id ) }
				>
					{ action.label }
				</Button>
			) );
		};

		const notesArray = Object.keys( notes ).map( key => notes[ key ] );

		return notesArray.map( note => (
			<ActivityCard
				key={ note.id }
				className={ classnames( 'woocommerce-inbox-activity-card', {
					actioned: 'unactioned' !== note.status,
				} ) }
				title={ note.title }
				date={ note.date_created_gmt }
				icon={ <Gridicon icon={ note.icon } size={ 48 } /> }
				unread={
					! lastRead ||
					! note.date_created_gmt ||
					new Date( note.date_created_gmt + 'Z' ).getTime() > lastRead
				}
				actions={ getButtonsFromActions( note ) }
			>
				<span dangerouslySetInnerHTML={ sanitizeHTML( note.content ) } />
			</ActivityCard>
		) );
	}

	render() {
		const { isError, isRequesting } = this.props;

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

		return (
			<Fragment>
				<ActivityHeader title={ __( 'Inbox', 'woocommerce-admin' ) } />
				<Section>
					{ isRequesting ? (
						<ActivityCardPlaceholder
							className="woocommerce-inbox-activity-card"
							hasAction
							hasDate
							lines={ 2 }
						/>
					) : (
						this.renderNotes()
					) }
				</Section>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getCurrentUserData, getNotes, getNotesError, isGetNotesRequesting } = select(
			'wc-api'
		);
		const userData = getCurrentUserData();
		const inboxQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			type: 'info,warning',
			orderby: 'date',
			order: 'desc',
			status: 'unactioned',
		};

		const notes = getNotes( inboxQuery );
		const isError = Boolean( getNotesError( inboxQuery ) );
		const isRequesting = isGetNotesRequesting( inboxQuery );

		return { notes, isError, isRequesting, lastRead: userData.activity_panel_inbox_last_read };
	} ),
	withDispatch( dispatch => {
		const { updateCurrentUserData, triggerNoteAction } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
			triggerNoteAction,
		};
	} )
)( InboxPanel );
