/**
 * Notes panel — dedicated home for all human-authored content on the order.
 *
 * Three categories surfaced visibly:
 *   1. Customer's checkout note (top, "Important" badge, conditional).
 *   2. Notes sent to the customer (admin notes with customer_note=true).
 *   3. Private merchant notes (admin notes with customer_note=false).
 *
 * Composer at the bottom with To-customer / Private toggle. Notes can be
 * deleted but not edited (mirrors today's WC behavior).
 */

import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	TextareaControl,
	ToggleControl,
	Notice,
	Card,
	CardHeader,
	CardBody,
} from '@wordpress/components';
import { useOrder } from '../data/order-context';
import { useNotes } from '../data/notes-context';
import { isSystemNote, type OrderNote } from '../data/types';

export function NotesPanel() {
	const { order } = useOrder();
	const { notes, loading, addNote, removeNote } = useNotes();

	const [ composerBody, setComposerBody ] = useState( '' );
	// Toggle: when true, the note is sent to the customer (fires email). Off = private.
	const [ sendEmail, setSendEmail ] = useState( false );
	const [ submitting, setSubmitting ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	// Human-authored notes (skip system-generated events — those live in the
	// History column). Sorted chronologically with the newest first.
	const humanNotes = useMemo( () => {
		return notes
			.filter( ( n ) => ! isSystemNote( n ) )
			.slice()
			.sort(
				( a, b ) =>
					new Date( b.date_created ).getTime() -
					new Date( a.date_created ).getTime()
			);
	}, [ notes ] );

	if ( ! order ) {
		return null;
	}

	const handleAdd = async () => {
		if ( ! composerBody.trim() ) {
			return;
		}
		setSubmitting( true );
		setError( null );
		try {
			await addNote( composerBody.trim(), sendEmail );
			setComposerBody( '' );
			window.dispatchEvent(
				new CustomEvent( 'wc-react-order-edit:snackbar', {
					detail: { message: __( 'Note added', 'woocommerce' ) },
				} )
			);
		} catch ( err ) {
			setError( err instanceof Error ? err.message : String( err ) );
		} finally {
			setSubmitting( false );
		}
	};

	const handleDelete = async ( id: number ) => {
		try {
			await removeNote( id );
			window.dispatchEvent(
				new CustomEvent( 'wc-react-order-edit:snackbar', {
					detail: { message: __( 'Note deleted', 'woocommerce' ) },
				} )
			);
		} catch ( err ) {
			setError( err instanceof Error ? err.message : String( err ) );
		}
	};

	const customerNote = order.customer_note?.trim();

	return (
		<Card
			className="wc-react-order-edit__panel wc-react-order-edit__notes-panel"
			aria-labelledby="wc-react-order-edit-notes-heading"
		>
			<CardHeader className="wc-react-order-edit__panel-header">
				<h2
					id="wc-react-order-edit-notes-heading"
					className="wc-react-order-edit__panel-title"
				>
					{ __( 'Notes', 'woocommerce' ) }
				</h2>
			</CardHeader>

			<CardBody className="wc-react-order-edit__panel-body">
				<div className="wc-react-order-edit__notes-section">
					<h3 className="wc-react-order-edit__subheading">
						{ __( 'Checkout notes', 'woocommerce' ) }
					</h3>
					{ customerNote ? (
						<Notice status="warning" isDismissible={ false }>
							<span className="wc-react-order-edit__customer-checkout-note-body">
								{ customerNote }
							</span>
						</Notice>
					) : (
						<p className="wc-react-order-edit__empty">
							{ __( 'No notes left at checkout.', 'woocommerce' ) }
						</p>
					) }
				</div>
			</CardBody>

			<hr className="wc-react-order-edit__card-divider" />

			<CardBody className="wc-react-order-edit__panel-body">
				<div className="wc-react-order-edit__notes-section">
					<h3 className="wc-react-order-edit__subheading">
						{ __( 'Notes', 'woocommerce' ) }
					</h3>
					{ loading ? (
						<p className="wc-react-order-edit__loading">{ __( 'Loading…', 'woocommerce' ) }</p>
					) : humanNotes.length === 0 ? (
						<p className="wc-react-order-edit__empty">
							{ __( 'No notes yet.', 'woocommerce' ) }
						</p>
					) : (
						<ul className="wc-react-order-edit__timeline wc-react-order-edit__notes-timeline">
							{ humanNotes.map( ( note ) => (
								<NoteRow
									key={ note.id }
									note={ note }
									onDelete={ () => handleDelete( note.id ) }
								/>
							) ) }
						</ul>
					) }
				</div>
			</CardBody>

			<hr className="wc-react-order-edit__card-divider" />

			<CardBody className="wc-react-order-edit__panel-body">
				<div className="wc-react-order-edit__notes-composer">
					<h3 className="wc-react-order-edit__subheading">
						{ __( 'Add a note', 'woocommerce' ) }
					</h3>
					{ error && (
						<Notice status="error" onRemove={ () => setError( null ) }>
							{ error }
						</Notice>
					) }
					<TextareaControl
						label={ __( 'Note', 'woocommerce' ) }
						hideLabelFromVision
						value={ composerBody }
						onChange={ setComposerBody }
						placeholder={ __( 'Write a note…', 'woocommerce' ) }
						__nextHasNoMarginBottom
					/>
					<div className="wc-react-order-edit__notes-composer-actions">
						<ToggleControl
							label={ __( 'Send email to customer', 'woocommerce' ) }
							checked={ sendEmail }
							onChange={ setSendEmail }
							__nextHasNoMarginBottom
						/>
						<Button
							variant="secondary"
							size="compact"
							onClick={ handleAdd }
							isBusy={ submitting }
							disabled={ submitting || ! composerBody.trim() }
						>
							{ __( 'Add note', 'woocommerce' ) }
						</Button>
					</div>
				</div>
			</CardBody>
		</Card>
	);
}

interface NoteRowProps {
	note: OrderNote;
	onDelete: () => void;
}

function NoteRow( { note, onDelete }: NoteRowProps ) {
	// Customer-facing notes fire an email and visually get the "email" dot
	// (filled blue), matching the History timeline pattern. Private notes
	// get the default hollow ring.
	const dotKind = note.customer_note ? 'email' : 'event';
	// Customer notes can't be retracted — the email has already been sent.
	// Only private merchant notes get a Delete affordance.
	const canDelete = ! note.customer_note;

	return (
		<li className="wc-react-order-edit__timeline-row">
			<div
				className="wc-react-order-edit__timeline-icon"
				data-kind={ dotKind }
				aria-hidden="true"
			/>
			<div className="wc-react-order-edit__timeline-body">
				<div
					className={ `wc-react-order-edit__note-content${
						note.customer_note ? ' wc-react-order-edit__note-content--customer' : ''
					}` }
				>
					{ note.customer_note && (
						<div className="wc-react-order-edit__note-header">
							{ __( 'Sent to customer', 'woocommerce' ) }
						</div>
					) }
					<div className="wc-react-order-edit__note-body">
						{ note.note }
					</div>
				</div>
				<div className="wc-react-order-edit__note-meta">
					<span className="wc-react-order-edit__note-author">
						{ note.author || __( 'System', 'woocommerce' ) }
					</span>
					<span className="wc-react-order-edit__note-date">
						{ formatDate( note.date_created ) }
					</span>
					{ canDelete && (
						<Button
							variant="tertiary"
							size="small"
							onClick={ onDelete }
							aria-label={ __( 'Delete note', 'woocommerce' ) }
						>
							{ __( 'Delete', 'woocommerce' ) }
						</Button>
					) }
				</div>
			</div>
		</li>
	);
}

function formatDate( iso: string ): string {
	if ( ! iso ) {
		return '';
	}
	try {
		return new Date( iso ).toLocaleString();
	} catch {
		return iso;
	}
}
