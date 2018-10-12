/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
import { sortBy } from 'lodash';
import { withSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../activity-card';
import ActivityHeader from '../activity-header';
import sanitizeHTML from 'lib/sanitize-html';
import { Section } from '@woocommerce/components';

class InboxPanel extends Component {
	render() {
		const { loading, notes } = this.props;

		const getButtonsFromActions = actions => {
			if ( ! actions ) {
				return [];
			}
			return actions.map( action => (
				<Button disabled isDefault href={ action.url }>
					{ action.label }
				</Button>
			) );
		};

		const notesArray = Object.keys( notes ).map( key => notes[ key ] );
		const sortedNotesArray = sortBy( notesArray, id => -id );

		return (
			<Fragment>
				<ActivityHeader title={ __( 'Inbox', 'wc-admin' ) } />
				<Section>
					{ loading ? (
						<ActivityCardPlaceholder
							className="woocommerce-inbox-activity-card"
							hasAction
							hasDate
							lines={ 2 }
						/>
					) : (
						sortedNotesArray.map( note => (
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
		const { getNotes } = select( 'wc-admin' );
		const notes = getNotes();
		const loading = select( 'core/data' ).isResolving( 'wc-admin', 'getNotes' );
		return { loading, notes };
	} )
)( InboxPanel );
