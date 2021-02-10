/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, createRef } from '@wordpress/element';
import { Button, Dropdown, Modal } from '@wordpress/components';
import PropTypes from 'prop-types';
import VisibilitySensor from 'react-visibility-sensor';
import moment from 'moment';
import classnames from 'classnames';
import { H, Section } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import NoteAction from './action';
import sanitizeHTML from '../lib/sanitize-html';
import './style.scss';
import { getScreenName } from '../utils';

class InboxNoteCard extends Component {
	constructor( props ) {
		super( props );
		this.onVisible = this.onVisible.bind( this );
		this.hasBeenSeen = false;
		this.state = {
			isDismissModalOpen: false,
			dismissType: null,
			clickedActionText: null,
		};
		this.openDismissModal = this.openDismissModal.bind( this );
		this.closeDismissModal = this.closeDismissModal.bind( this );
		this.bodyNotificationRef = createRef();
		this.toggleButtonRef = createRef();
		this.screen = getScreenName();
	}

	componentDidMount() {
		if ( this.bodyNotificationRef.current ) {
			this.bodyNotificationRef.current.addEventListener(
				'click',
				( event ) => this.handleBodyClick( event, this.props )
			);
		}
	}

	componentWillUnmount() {
		if ( this.bodyNotificationRef.current ) {
			this.bodyNotificationRef.current.removeEventListener(
				'click',
				( event ) => this.handleBodyClick( event, this.props )
			);
		}
	}

	handleBodyClick( event, props ) {
		const innerLink = event.target.href;
		if ( innerLink ) {
			const { note } = props;

			recordEvent( 'wcadmin_inbox_action_click', {
				note_name: note.name,
				note_title: note.title,
				note_content_inner_link: innerLink,
			} );
		}
	}

	// Trigger a view Tracks event when the note is seen.
	onVisible( isVisible ) {
		if ( isVisible && ! this.hasBeenSeen ) {
			const { note } = this.props;

			recordEvent( 'inbox_note_view', {
				note_content: note.content,
				note_name: note.name,
				note_title: note.title,
				note_type: note.type,
				screen: this.screen,
			} );

			this.hasBeenSeen = true;
		}
	}

	openDismissModal( type, onToggle ) {
		this.setState( {
			isDismissModalOpen: true,
			dismissType: type,
		} );
		onToggle();
	}

	closeDismissModal( noteNameDismissConfirmation ) {
		const { dismissType } = this.state;
		const { note } = this.props;
		const noteNameDismissAll = dismissType === 'all' ? true : false;

		recordEvent( 'inbox_action_dismiss', {
			note_name: note.name,
			note_title: note.title,
			note_name_dismiss_all: noteNameDismissAll,
			note_name_dismiss_confirmation:
				noteNameDismissConfirmation || false,
			screen: this.screen,
		} );

		this.setState( {
			isDismissModalOpen: false,
		} );
	}

	handleBlur( event, onClose ) {
		const dropdownClasses = [
			'woocommerce-admin-dismiss-notification',
			'components-popover__content',
		];
		// This line is for IE compatibility.
		let relatedTarget;
		if ( event.relatedTarget ) {
			relatedTarget = event.relatedTarget;
		} else if ( this.toggleButtonRef.current ) {
			const ownerDoc = this.toggleButtonRef.current.ownerDocument;
			relatedTarget = ownerDoc ? ownerDoc.activeElement : null;
		}
		const isClickOutsideDropdown = relatedTarget
			? dropdownClasses.some( ( className ) =>
					relatedTarget.className.includes( className )
			  )
			: false;
		if ( isClickOutsideDropdown ) {
			event.preventDefault();
		} else {
			onClose();
		}
	}

	renderDismissButton() {
		const { clickedActionText } = this.state;

		if ( clickedActionText ) {
			return null;
		}

		return (
			<Dropdown
				contentClassName="woocommerce-admin-dismiss-dropdown"
				position="bottom right"
				renderToggle={ ( { onClose, onToggle } ) => (
					<Button
						isTertiary
						onClick={ onToggle }
						ref={ this.toggleButtonRef }
						onBlur={ ( event ) =>
							this.handleBlur( event, onClose )
						}
					>
						{ __( 'Dismiss', 'woocommerce-admin' ) }
					</Button>
				) }
				focusOnMount={ false }
				popoverProps={ { noArrow: true } }
				renderContent={ ( { onToggle } ) => (
					<ul>
						<li>
							<Button
								className="woocommerce-admin-dismiss-notification"
								onClick={ () =>
									this.openDismissModal( 'this', onToggle )
								}
							>
								{ __(
									'Dismiss this message',
									'woocommerce-admin'
								) }
							</Button>
						</li>
						<li>
							<Button
								className="woocommerce-admin-dismiss-notification"
								onClick={ () =>
									this.openDismissModal( 'all', onToggle )
								}
							>
								{ __(
									'Dismiss all messages',
									'woocommerce-admin'
								) }
							</Button>
						</li>
					</ul>
				) }
			/>
		);
	}

	getDismissConfirmationButton() {
		const { note } = this.props;
		const { dismissType } = this.state;
		return (
			<NoteAction
				key={ note.id }
				noteId={ dismissType === 'all' ? null : note.id }
				label={ __( "Yes, I'm sure", 'woocommerce-admin' ) }
				actionCallback={ this.closeDismissModal }
				dismiss={ true }
				screen={ this.screen }
			/>
		);
	}

	renderDismissConfirmationModal() {
		return (
			<Modal
				title={ <>{ __( 'Are you sure?', 'woocommerce-admin' ) }</> }
				onRequestClose={ () => this.closeDismissModal() }
				className="woocommerce-inbox-dismiss-confirmation_modal"
			>
				<div className="woocommerce-inbox-dismiss-confirmation_wrapper">
					<p>
						{ __(
							'Dismissed messages cannot be viewed again',
							'woocommerce-admin'
						) }
					</p>
					<div className="woocommerce-inbox-dismiss-confirmation_buttons">
						<Button
							isSecondary
							onClick={ () => this.closeDismissModal() }
						>
							{ __( 'Cancel', 'woocommerce-admin' ) }
						</Button>
						{ this.getDismissConfirmationButton() }
					</div>
				</div>
			</Modal>
		);
	}

	renderActions( note ) {
		const { actions: noteActions, id: noteId } = note;
		const { clickedActionText } = this.state;

		if ( !! clickedActionText ) {
			return clickedActionText;
		}

		if ( ! noteActions ) {
			return;
		}

		return (
			<>
				{ noteActions.map( ( action, index ) => (
					<NoteAction
						key={ index }
						noteId={ noteId }
						action={ action }
						onClick={ () => this.onActionClicked( action ) }
					/>
				) ) }
			</>
		);
	}

	onActionClicked = ( action ) => {
		if ( ! action.actioned_text ) {
			return;
		}

		this.setState( {
			clickedActionText: action.actioned_text,
		} );
	};

	render() {
		const { lastRead, note } = this.props;
		const { isDismissModalOpen } = this.state;
		const {
			content,
			date_created: dateCreated,
			date_created_gmt: dateCreatedGmt,
			image,
			is_deleted: isDeleted,
			layout,
			status,
			title,
		} = note;

		if ( isDeleted ) {
			return null;
		}

		const unread =
			! lastRead ||
			! dateCreatedGmt ||
			new Date( dateCreatedGmt + 'Z' ).getTime() > lastRead;
		const date = dateCreated;
		const hasImage = layout !== 'plain' && layout !== '';
		const cardClassName = classnames( 'woocommerce-inbox-message', layout, {
			'message-is-unread': unread && status === 'unactioned',
		} );

		return (
			<VisibilitySensor onChange={ this.onVisible }>
				<section className={ cardClassName }>
					{ hasImage && (
						<div className="woocommerce-inbox-message__image">
							<img src={ image } alt="" />
						</div>
					) }
					<div className="woocommerce-inbox-message__wrapper">
						<div className="woocommerce-inbox-message__content">
							{ unread && (
								<div className="woocommerce-inbox-message__unread-indicator" />
							) }
							{ date && (
								<span className="woocommerce-inbox-message__date">
									{ moment.utc( date ).fromNow() }
								</span>
							) }
							<H className="woocommerce-inbox-message__title">
								{ title }
							</H>
							<Section className="woocommerce-inbox-message__text">
								<span
									dangerouslySetInnerHTML={ sanitizeHTML(
										content
									) }
									ref={ this.bodyNotificationRef }
								/>
							</Section>
						</div>
						<div className="woocommerce-inbox-message__actions">
							{ this.renderActions( note ) }
							{ this.renderDismissButton() }
						</div>
					</div>
					{ isDismissModalOpen &&
						this.renderDismissConfirmationModal() }
				</section>
			</VisibilitySensor>
		);
	}
}

InboxNoteCard.propTypes = {
	note: PropTypes.shape( {
		id: PropTypes.number,
		status: PropTypes.string,
		title: PropTypes.string,
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
		layout: PropTypes.string,
		image: PropTypes.string,
		is_deleted: PropTypes.bool,
	} ),
	lastRead: PropTypes.number,
};

export default InboxNoteCard;
