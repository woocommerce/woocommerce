/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Fragment, useState, useRef } from '@wordpress/element';
import { Button, Dropdown, Popover } from '@wordpress/components';
import VisibilitySensor from 'react-visibility-sensor';
import moment from 'moment';
import classnames from 'classnames';
import { H, Section } from '@woocommerce/components';
import { sanitize } from 'dompurify';

/**
 * Internal dependencies
 */
import { InboxNoteActionButton } from './action';
import { useCallbackOnLinkClick } from './use-callback-on-link-click';

const ALLOWED_TAGS = [ 'a', 'b', 'em', 'i', 'strong', 'p', 'br' ];
const ALLOWED_ATTR = [ 'target', 'href', 'rel', 'name', 'download' ];

const sanitizeHTML = ( html: string ) => {
	return {
		__html: sanitize( html, { ALLOWED_TAGS, ALLOWED_ATTR } ),
	};
};

type InboxNoteAction = {
	id: number;
	url: string;
	label: string;
	primary: boolean;
	actioned_text?: boolean;
};

type InboxNote = {
	id: number;
	status: string;
	title: string;
	name: string;
	content: string;
	date_created: string;
	date_created_gmt: string;
	actions: InboxNoteAction[];
	layout: string;
	image: string;
	is_deleted: boolean;
	type: string;
};

type InboxNoteProps = {
	note: InboxNote;
	lastRead: number;
	onDismiss?: ( note: InboxNote, type: 'all' | 'note' ) => void;
	onNoteActionClick?: ( note: InboxNote, action: InboxNoteAction ) => void;
	onBodyLinkClick?: ( note: InboxNote, link: string ) => void;
	onNoteVisible?: ( note: InboxNote ) => void;
	className?: string;
};

const DropdownWithPopoverProps = Dropdown as React.ComponentType<
	Dropdown.Props & { popoverProps: Omit< Popover.Props, 'children' > }
>;

const InboxNoteCard: React.FC< InboxNoteProps > = ( {
	note,
	lastRead,
	onDismiss,
	onNoteActionClick,
	onBodyLinkClick,
	onNoteVisible,
	className,
} ) => {
	const [ clickedActionText, setClickedActionText ] = useState( false );
	const hasBeenSeen = useRef( false );
	const toggleButtonRef = useRef< HTMLButtonElement >( null );
	const linkCallbackRef = useCallbackOnLinkClick( ( innerLink ) => {
		if ( onBodyLinkClick ) {
			onBodyLinkClick( note, innerLink );
		}
	} );

	// Trigger a view Tracks event when the note is seen.
	const onVisible = ( isVisible: boolean ) => {
		if ( isVisible && ! hasBeenSeen.current ) {
			if ( onNoteVisible ) {
				onNoteVisible( note );
			}

			hasBeenSeen.current = true;
		}
	};

	const handleBlur = ( event: React.FocusEvent, onClose: () => void ) => {
		const dropdownClasses = [
			'woocommerce-admin-dismiss-notification',
			'components-popover__content',
			'components-dropdown__content',
		];
		// This line is for IE compatibility.
		let relatedTarget: EventTarget | Element | null = null;
		if ( event.relatedTarget ) {
			relatedTarget = event.relatedTarget;
		} else if ( toggleButtonRef.current ) {
			const ownerDoc = toggleButtonRef.current.ownerDocument;
			relatedTarget = ownerDoc ? ownerDoc.activeElement : null;
		}
		let isClickOutsideDropdown = false;
		if ( relatedTarget && 'className' in relatedTarget ) {
			const classNames = relatedTarget.className;
			isClickOutsideDropdown = dropdownClasses.some( ( cName ) =>
				classNames.includes( cName )
			);
		}
		if ( isClickOutsideDropdown ) {
			event.preventDefault();
		} else {
			onClose();
		}
	};

	const onDropdownDismiss = (
		type: 'note' | 'all',
		onToggle: () => void
	) => {
		if ( onDismiss ) {
			onDismiss( note, type );
		}
		onToggle();
	};

	const renderDismissButton = () => {
		if ( clickedActionText ) {
			return null;
		}

		return (
			<DropdownWithPopoverProps
				contentClassName="woocommerce-admin-dismiss-dropdown"
				position="bottom right"
				renderToggle={ ( { onClose, onToggle } ) => (
					<Button
						isTertiary
						onClick={ ( event: React.MouseEvent ) => {
							( event.target as HTMLElement ).focus();
							onToggle();
						} }
						ref={ toggleButtonRef }
						onBlur={ ( event: React.FocusEvent ) =>
							handleBlur( event, onClose )
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
									onDropdownDismiss( 'note', onToggle )
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
									onDropdownDismiss( 'all', onToggle )
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
	};

	const onActionClicked = ( action: InboxNoteAction ) => {
		if ( onNoteActionClick ) {
			onNoteActionClick( note, action );
		}
		if ( ! action.actioned_text ) {
			return;
		}

		setClickedActionText( action.actioned_text );
	};

	const renderActions = () => {
		const { actions: noteActions } = note;

		if ( !! clickedActionText ) {
			return clickedActionText;
		}

		if ( ! noteActions ) {
			return;
		}

		return (
			<>
				{ noteActions.map( ( action ) => (
					<InboxNoteActionButton
						key={ action.id }
						label={ action.label }
						href={
							action && action.url && action.url.length
								? action.url
								: undefined
						}
						onClick={ () => onActionClicked( action ) }
					/>
				) ) }
			</>
		);
	};

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
	const cardClassName = classnames(
		'woocommerce-inbox-message',
		className,
		layout,
		{
			'message-is-unread': unread && status === 'unactioned',
		}
	);

	return (
		<VisibilitySensor onChange={ onVisible }>
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
								ref={ linkCallbackRef }
							/>
						</Section>
					</div>
					<div className="woocommerce-inbox-message__actions">
						{ renderActions() }
						{ renderDismissButton() }
					</div>
				</div>
			</section>
		</VisibilitySensor>
	);
};

export { InboxNoteCard, InboxNote, InboxNoteAction };
