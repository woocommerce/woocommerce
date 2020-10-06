/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import PropTypes from 'prop-types';
import { ADMIN_URL as adminUrl } from '@woocommerce/wc-admin-settings';
import { NOTES_STORE_NAME } from '@woocommerce/data';

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
			batchUpdateNotes,
			createNotice,
			noteId,
			triggerNoteAction,
			removeAllNotes,
			removeNote,
			onClick,
			updateNote,
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
				removeNote( noteId )
					.then( () => {
						createNotice(
							'success',
							__( 'Message dismissed.', 'woocommerce-admin' ),
							{
								actions: [
									{
										label: __(
											'Undo',
											'woocommerce-admin'
										),
										onClick: () => {
											updateNote( noteId, {
												is_deleted: 0,
											} );
										},
									},
								],
							}
						);
					} )
					.catch( () => {
						createNotice(
							'error',
							__(
								'Message could not be dismissed.',
								'woocommerce-admin'
							)
						);
					} );
			} else {
				removeAllNotes()
					.then( ( notes ) => {
						createNotice(
							'success',
							__(
								'All messages dismissed.',
								'woocommerce-admin'
							),
							{
								actions: [
									{
										label: __(
											'Undo',
											'woocommerce-admin'
										),
										onClick: () => {
											batchUpdateNotes(
												notes.map(
													( note ) => note.id
												),
												{
													is_deleted: 0,
												}
											);
										},
									},
								],
							}
						);
					} )
					.catch( () => {
						createNotice(
							'error',
							__(
								'Message could not be dismissed.',
								'woocommerce-admin'
							)
						);
					} );
			}

			actionCallback( true );
		} else {
			this.setState( { inAction }, () => {
				triggerNoteAction( noteId, action.id );

				if ( !! onClick ) {
					onClick();
				}
			} );
		}
	}

	render() {
		const { action, dismiss, label } = this.props;

		return (
			<Button
				isSecondary
				isBusy={ this.state.inAction }
				disabled={ this.state.inAction }
				href={
					action && action.url && action.url.length
						? action.url
						: undefined
				}
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
	onClick: PropTypes.func,
};

export default compose(
	withDispatch( ( dispatch ) => {
		const { createNotice } = dispatch( 'core/notices' );
		const {
			batchUpdateNotes,
			removeAllNotes,
			removeNote,
			updateNote,
			triggerNoteAction,
		} = dispatch( NOTES_STORE_NAME );

		return {
			batchUpdateNotes,
			createNotice,
			removeAllNotes,
			removeNote,
			triggerNoteAction,
			updateNote,
		};
	} )
)( InboxNoteAction );
