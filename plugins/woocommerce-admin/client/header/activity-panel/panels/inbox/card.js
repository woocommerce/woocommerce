/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import Gridicon from 'gridicons';
import VisibilitySensor from 'react-visibility-sensor';

/**
 * Internal dependencies
 */
import { ActivityCard } from '../../activity-card';
import NoteAction from './action';
import sanitizeHTML from 'lib/sanitize-html';
import classnames from 'classnames';
import { recordEvent } from 'lib/tracks';

class InboxNoteCard extends Component {
	constructor( props ) {
		super( props );
		this.onVisible = this.onVisible.bind( this );
		this.hasBeenSeen = false;
	}

	// Trigger a view Tracks event when the note is seen.
	onVisible( isVisible ) {
		if ( isVisible && ! this.hasBeenSeen ) {
			const { note } = this.props;
			const {
				content: note_content,
				name: note_name,
				title: note_title,
				type: note_type,
				icon: note_icon,
			} = note;

			recordEvent( 'inbox_note_view', {
				note_content,
				note_name,
				note_title,
				note_type,
				note_icon,
			} );

			this.hasBeenSeen = true;
		}
	}

	render() {
		const { lastRead, note } = this.props;

		const getButtonsFromActions = () => {
			if ( ! note.actions ) {
				return [];
			}
			return note.actions.map( action => <NoteAction noteId={ note.id } action={ action } /> );
		};

		return (
			<VisibilitySensor onChange={ this.onVisible }>
				<ActivityCard
					className={ classnames( 'woocommerce-inbox-activity-card', {
						actioned: 'unactioned' !== note.status,
					} ) }
					title={ note.title }
					date={ note.date_created }
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
			</VisibilitySensor>
		);
	}
}

InboxNoteCard.propTypes = {
	note: PropTypes.shape( {
		id: PropTypes.number,
		status: PropTypes.string,
		title: PropTypes.string,
		icon: PropTypes.string,
		content: PropTypes.string,
		date_created: PropTypes.string,
		date_created_gmt: PropTypes.string,
		actions: PropTypes.arrayOf(
			PropTypes.shape( {
				id: PropTypes.number.isRequired,
				url: PropTypes.string,
				label: PropTypes.string.isRequired,
				primary: PropTypes.bool.isRequired,
			} )
		),
	} ),
	lastRead: PropTypes.number,
};

export default InboxNoteCard;
