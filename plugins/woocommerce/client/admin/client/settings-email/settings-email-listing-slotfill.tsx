/**
 * External dependencies
 */
import { createSlotFill, Button } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { SETTINGS_SLOT_FILL_CONSTANT } from '~/settings/settings-slots';
import { ListView } from './settings-email-listing-listview';
import { shouldShowReviewUpdate } from './settings-email-listing-update-state';
import { VIEWED_FROM_EMAIL_LIST } from '../wp-admin-scripts/email-editor-integration/tracks/build-shared-payload';

export type Recipients = {
	to: string;
	cc: string;
	bcc: string;
};

export type EmailStatus = 'enabled' | 'disabled' | 'manual';

/**
 * Classification of an email post relative to the current core template.
 *
 * Sourced from `_wc_email_template_status` post meta (RSM-138), auto-surfaced
 * under `meta` in the `wp/v2/woo_email` REST response. Read-only client-side.
 * Public REST API contract — see RSM-140 spec § 4.3.
 */
export type TemplateStatus =
	| 'in_sync'
	| 'core_updated_uncustomized'
	| 'core_updated_customized';

export type EmailType = {
	title: string;
	description: string;
	id: string;
	email_key: string;
	email_class_name: string;
	post_id: string;
	recipients: Recipients;
	enabled: boolean;
	manual: boolean;
	link?: string;
	status?: EmailStatus;
	templateStatus: TemplateStatus | null;
	templateVersion: string | null;
	/**
	 * Registry-side current version of the canonical core template for this
	 * email. Sourced from `WCEmailTemplateSyncRegistry::get_email_sync_config()`
	 * server-side; serialized as `current_version` in the slotfill payload
	 * and projected to camelCase in the data hook. Combined with
	 * `templateVersion` to gate the "update available" indicator on both
	 * surfaces (list cell + RSM-141 editor banner): show only when the
	 * merchant has not yet reviewed this version.
	 */
	currentVersion: string | null;
	/**
	 * Whether the post was stamped by the RSM-149 backfill rather than created
	 * natively by the modern generator. Sourced from `_wc_email_backfilled`
	 * post meta and projected from `wp/v2/woo_email` REST in
	 * {@link useTransactionalEmails}. Reserved for future surfaces — the list
	 * page emits a single aggregate `_list_viewed` event with row counts only,
	 * so this field is not part of that payload.
	 */
	wasBackfilled: boolean;
};

const { Fill } = createSlotFill( SETTINGS_SLOT_FILL_CONSTANT );

/**
 * Session-storage key for the list-page `_list_viewed` Tracks dedup. Fires once
 * per tab session: sessionStorage persists across reloads in the same tab, so
 * refreshes do not re-fire. Closing the tab (or opening the page in a new tab)
 * resets the gate.
 */
const LIST_VIEWED_DEDUP_SESSION_KEY = 'wc_email_update_list_viewed';

export const EmailListingFill: React.FC< {
	emailTypes: EmailType[];
	editTemplateUrl: string | null;
} > = ( { emailTypes, editTemplateUrl } ) => {
	// Fire one aggregate `_list_viewed` per session covering the entire list.
	// Tracking per-row creates one event per visible cell (~20+ on a default
	// install) per page load with limited analytical lift over a single
	// page-level signal — the editor-banner `_viewed` covers per-post drilldown
	// already. sessionStorage persists for the tab's lifetime, so refreshes
	// dedup; a new tab fires once.
	useEffect( () => {
		try {
			if (
				window.sessionStorage.getItem( LIST_VIEWED_DEDUP_SESSION_KEY )
			) {
				return;
			}
			window.sessionStorage.setItem( LIST_VIEWED_DEDUP_SESSION_KEY, '1' );
		} catch {
			// sessionStorage unavailable (privacy mode / quota). Fall through
			// and fire the event anyway — duplicate counts are preferable to
			// silent dropouts when storage is blocked.
		}

		const eligibleCount = emailTypes.filter( ( post ) =>
			shouldShowReviewUpdate( post )
		).length;

		recordEvent( 'block_email_list_viewed', {
			viewed_from: VIEWED_FROM_EMAIL_LIST,
			eligible_count: eligibleCount,
			total_count: emailTypes.length,
		} );
		// `emailTypes` is sourced once from the server-rendered slot payload
		// and does not change during the page's lifetime, so this effect runs
		// exactly once per mount.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return (
		<Fill>
			<div
				id="email_notification_settings-description"
				className="woocommerce-email-listing-description"
			>
				<p>
					{ __(
						"Manage email notifications sent from WooCommerce below or click on 'Edit template' to customize your email template design.",
						'woocommerce'
					) }
				</p>
				{ editTemplateUrl && (
					<Button
						variant="primary"
						href={ editTemplateUrl }
						className="woocommerce-email-listing-edit-template-button"
					>
						{ __( 'Edit template', 'woocommerce' ) }
					</Button>
				) }
			</div>
			<ListView emailTypes={ emailTypes } />
		</Fill>
	);
};

/**
 * Normalize the raw snake_case slotfill payload into the camelCase `EmailType`
 * shape consumed by the React tree. The server projects `template_status`,
 * `template_version`, `was_backfilled`, and `current_version` directly (so the
 * list-page `_list_viewed` event can compute `eligible_count` on mount without
 * waiting for the REST enrichment in `useTransactionalEmails`). Other fields
 * already match the TS shape and pass through unchanged.
 *
 * Exported for unit testing.
 */
export const normalizeEmailTypePayload = (
	raw: Record< string, unknown >
): EmailType => {
	const templateStatus =
		typeof raw.template_status === 'string' &&
		raw.template_status.length > 0
			? ( raw.template_status as EmailType[ 'templateStatus' ] )
			: null;
	const templateVersion =
		typeof raw.template_version === 'string' &&
		raw.template_version.length > 0
			? ( raw.template_version as string )
			: null;
	const currentVersion =
		typeof raw.current_version === 'string' &&
		raw.current_version.length > 0
			? ( raw.current_version as string )
			: null;
	const wasBackfilled =
		raw.was_backfilled === true || raw.was_backfilled === 1;

	return {
		...( raw as unknown as EmailType ),
		templateStatus,
		templateVersion,
		currentVersion,
		wasBackfilled,
	};
};

export const registerSettingsEmailListingFill = () => {
	const slotElementId = 'wc_settings_email_listing_slotfill';
	const slotElement = document.getElementById( slotElementId );
	if ( ! slotElement ) {
		return null;
	}
	const emailTypesData = slotElement.getAttribute( 'data-email-types' );
	const editTemplateUrl = slotElement.getAttribute(
		'data-edit-template-url'
	);
	let emailTypes: EmailType[] = [];
	try {
		const parsed = JSON.parse( emailTypesData || '' );
		emailTypes = Array.isArray( parsed )
			? parsed.map( ( item: Record< string, unknown > ) =>
					normalizeEmailTypePayload( item )
			  )
			: [];
	} catch ( e ) {}

	registerPlugin( 'woocommerce-admin-settings-email-listing', {
		scope: 'woocommerce-email-listing',
		render: () => (
			<EmailListingFill
				emailTypes={ emailTypes }
				editTemplateUrl={ editTemplateUrl }
			/>
		),
	} );
};
