/**
 * History timeline — system activity only (events + emails) as one chronological
 * vertical timeline.
 *
 * Reads from `/wc/v3/orders/{id}/notes` filtered to non-human-authored entries.
 * The visual (rail + dots + content) is styled custom because @wordpress/dataviews
 * does not currently export a Timeline view type — only DataViews / DataForm /
 * DataViewsPicker / VIEW_LAYOUTS. If a real Timeline ships from DataViews we
 * can swap this out for the native one.
 *
 * Human-authored notes do NOT appear here — they live in the Notes panel.
 */

import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardHeader, CardBody } from '@wordpress/components';
import { useNotes } from '../data/notes-context';

const NOTE_GROUP_EMAIL = 'EMAIL_NOTIFICATION';

export function HistoryTimeline() {
	const { notes, loading } = useNotes();

	const systemNotes = useMemo(
		() => notes.filter( ( n ) => ! n.added_by_user ),
		[ notes ]
	);

	return (
		<Card
			className="wc-react-order-edit__history"
			aria-labelledby="wc-react-order-edit-history-heading"
		>
			<CardHeader className="wc-react-order-edit__panel-header">
				<h2
					id="wc-react-order-edit-history-heading"
					className="wc-react-order-edit__panel-title"
				>
					{ __( 'History', 'woocommerce' ) }
				</h2>
			</CardHeader>

			<CardBody className="wc-react-order-edit__panel-body">
				{ loading ? (
					<p className="wc-react-order-edit__loading">
						{ __( 'Loading…', 'woocommerce' ) }
					</p>
				) : systemNotes.length === 0 ? (
					<p className="wc-react-order-edit__empty">
						{ __( 'Nothing recorded yet.', 'woocommerce' ) }
					</p>
				) : (
					<ul className="wc-react-order-edit__timeline">
						{ systemNotes.map( ( note ) => (
							<li
								key={ note.id }
								className="wc-react-order-edit__timeline-row"
							>
								<div
									className="wc-react-order-edit__timeline-icon"
									data-kind={
										note.note_group === NOTE_GROUP_EMAIL
											? 'email'
											: 'event'
									}
									aria-hidden="true"
								/>
								<div className="wc-react-order-edit__timeline-body">
									<div className="wc-react-order-edit__timeline-text">
										{ note.note }
									</div>
									<div className="wc-react-order-edit__timeline-date">
										{ formatDate( note.date_created ) }
									</div>
								</div>
							</li>
						) ) }
					</ul>
				) }
			</CardBody>
		</Card>
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
