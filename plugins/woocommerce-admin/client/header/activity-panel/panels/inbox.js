/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
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
				<Button isDefault href={ action.url }>
					{ action.label }
				</Button>
			) );
		};

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
						Object.keys( notes ).map( key => (
							<ActivityCard
								key={ notes[ key ].id }
								className="woocommerce-inbox-activity-card"
								title={ notes[ key ].title }
								date={ notes[ key ].date_created }
								icon={ <Gridicon icon={ notes[ key ].icon } size={ 48 } /> }
								unread={ 'unread' === notes[ key ].status }
								actions={ getButtonsFromActions( notes[ key ].actions ) }
							>
								<span dangerouslySetInnerHTML={ sanitizeHTML( notes[ key ].content ) } />
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
