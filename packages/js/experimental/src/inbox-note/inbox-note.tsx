/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement, Fragment, useState, useRef } from '@wordpress/element';
import { Button } from '@wordpress/components';
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
	is_read: boolean;
};

type InboxNoteProps = {
	note: InboxNote;
	onDismiss?: ( note: InboxNote ) => void;
	onNoteActionClick?: ( note: InboxNote, action: InboxNoteAction ) => void;
	onBodyLinkClick?: ( note: InboxNote, link: string ) => void;
	onNoteVisible?: ( note: InboxNote ) => void;
	className?: string;
};

const InboxNoteCard: React.FC< InboxNoteProps > = ( {
	note,
	onDismiss,
	onNoteActionClick,
	onBodyLinkClick,
	onNoteVisible,
	className,
} ) => {
	const [ clickedActionText, setClickedActionText ] = useState( false );
	const hasBeenSeen = useRef( false );
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

	const renderDismissButton = () => {
		if ( clickedActionText ) {
			return null;
		}

		return (
			<Button
				className="woocommerce-admin-dismiss-notification"
				onClick={ () => onDismiss && onDismiss( note ) }
			>
				{ __( 'Dismiss', 'woocommerce' ) }
			</Button>
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
						variant="secondary"
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
		is_read,
	} = note;

	if ( isDeleted ) {
		return null;
	}

	const unread = is_read === false;
	const hasImage = layout === 'thumbnail';
	const cardClassName = classnames(
		'woocommerce-inbox-message',
		className,
		layout,
		{
			'message-is-unread': unread && status === 'unactioned',
		}
	);

	const actionWrapperClassName = classnames(
		'woocommerce-inbox-message__actions',
		{
			'has-multiple-actions': note.actions?.length > 1,
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
						{ dateCreatedGmt && (
							<span className="woocommerce-inbox-message__date">
								{ moment.utc( dateCreatedGmt ).fromNow() }
							</span>
						) }
						<H className="woocommerce-inbox-message__title">
							{ note.actions && note.actions.length === 1 && (
								<InboxNoteActionButton
									key={ note.actions[ 0 ].id }
									label={ title }
									preventBusyState={ true }
									variant="link"
									href={
										note.actions[ 0 ].url &&
										note.actions[ 0 ].url.length
											? note.actions[ 0 ].url
											: undefined
									}
									onClick={ () =>
										onActionClicked( note.actions[ 0 ] )
									}
								/>
							) }

							{ note.actions && note.actions.length > 1 && title }
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
					<div className={ actionWrapperClassName }>
						{ renderActions() }
						{ renderDismissButton() }
					</div>
				</div>
			</section>
		</VisibilitySensor>
	);
};

export { InboxNoteCard, InboxNote, InboxNoteAction };
