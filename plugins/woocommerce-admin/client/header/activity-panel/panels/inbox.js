/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
import withSelect from 'wc-api/with-select';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../activity-card';
import ActivityHeader from '../activity-header';
import { EmptyContent, Section } from '@woocommerce/components';
import sanitizeHTML from 'lib/sanitize-html';
import { QUERY_DEFAULTS } from 'store/constants';

class InboxPanel extends Component {
	render() {
		const { isError, isRequesting, notes } = this.props;

		if ( isError ) {
			const title = __( 'There was an error getting your inbox. Please try again.', 'wc-admin' );
			const actionLabel = __( 'Reload', 'wc-admin' );
			const actionCallback = () => {
				// TODO Add tracking for how often an error is displayed, and the reload action is clicked.
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

		const getButtonsFromActions = actions => {
			if ( ! actions ) {
				return [];
			}
			return actions.map( action => (
				<Button isDefault href={ action.url }>
					{ action.label }
				</Button>
			) );
		};

		const notesArray = Object.keys( notes ).map( key => notes[ key ] );

		return (
			<Fragment>
				<ActivityHeader title={ __( 'Inbox', 'wc-admin' ) } />
				<Section>
					{ isRequesting ? (
						<ActivityCardPlaceholder
							className="woocommerce-inbox-activity-card"
							hasAction
							hasDate
							lines={ 2 }
						/>
					) : (
						notesArray.map( note => (
							<ActivityCard
								key={ note.id }
								className="woocommerce-inbox-activity-card"
								title={ note.title }
								date={ note.date_created }
								icon={ <Gridicon icon={ note.icon } size={ 48 } /> }
								unread={ 'unread' === note.status }
								actions={ getButtonsFromActions( note.actions ) }
							>
								<span dangerouslySetInnerHTML={ sanitizeHTML( note.content ) } />
							</ActivityCard>
						) )
					) }
				</Section>
			</Fragment>
		);
	}
}

export default compose(
	withSelect( select => {
		const { getNotes, isGetNotesError, isGetNotesRequesting } = select( 'wc-api' );
		const inboxQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
			type: 'info,warning',
		};

		const notes = getNotes( inboxQuery );
		const isError = isGetNotesError( inboxQuery );
		const isRequesting = isGetNotesRequesting( inboxQuery );

		return { notes, isError, isRequesting };
	} )
)( InboxPanel );
