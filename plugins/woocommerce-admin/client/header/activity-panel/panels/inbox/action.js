/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { ADMIN_URL as adminUrl } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */

class InboxNoteAction extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			inAction: false,
		};

		this.handleActionClick = this.handleActionClick.bind( this );
	}

	handleActionClick( event ) {
		const {
			action,
			actionCallback,
			noteId,
			triggerNoteAction,
			removeAllNotes,
			removeNote,
		} = this.props;
		const href = event.target.href || '';
		let inAction = true;

		if ( href.length && ! href.startsWith( adminUrl ) ) {
			event.preventDefault();
			inAction = false; // link buttons shouldn't be "busy".
			window.open( href, '_blank' );
		}

		if ( ! action ) {
			if ( noteId ) {
				removeNote( noteId );
			} else {
				removeAllNotes();
			}
			actionCallback( true );
		} else {
			this.setState( { inAction }, () =>
				triggerNoteAction( noteId, action.id )
			);
		}
	}

	render() {
		const { action, dismiss, label } = this.props;
		return (
			<Button
				isDefault
				isPrimary={ dismiss || action.primary }
				isBusy={ this.state.inAction }
				disabled={ this.state.inAction }
				href={ action ? action.url : undefined }
				onClick={ this.handleActionClick }
			>
				{ dismiss ? label : action.label }
			</Button>
		);
	}
}

InboxNoteAction.propTypes = {
	noteId: PropTypes.number,
	label: PropTypes.string,
	dismiss: PropTypes.bool,
	actionCallback: PropTypes.func,
	action: PropTypes.shape( {
		id: PropTypes.number.isRequired,
		url: PropTypes.string,
		label: PropTypes.string.isRequired,
		primary: PropTypes.bool.isRequired,
	} ),
};

export default compose(
	withDispatch( ( dispatch ) => {
		const { removeAllNotes, removeNote, triggerNoteAction } = dispatch(
			'wc-api'
		);

		return {
			removeAllNotes,
			removeNote,
			triggerNoteAction,
		};
	} )
)( InboxNoteAction );
