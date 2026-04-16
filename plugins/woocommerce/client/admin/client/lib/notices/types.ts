/**
 * Shared type definitions for the core notices store (`@wordpress/notices`).
 *
 * `@wordpress/notices` at wp-6.8 does not re-export named types for its
 * notice/options/status shapes from its public entry point. Rather than
 * importing deep paths or defining ad-hoc shapes per call site, this
 * module declares the shapes that admin-library consumes and proxies
 * through to the core notices store at runtime.
 */

/**
 * The set of status values accepted by `createNotice` and exposed on each
 * stored `Notice`.
 */
export type NoticeStatus = 'success' | 'info' | 'error' | 'warning';

/**
 * Shape of an action attached to a notice. Matches the runtime-shape
 * accepted by `@wordpress/notices`' `createNotice` options and the
 * `actions` slot on `@wordpress/components`' `<Notice>`.
 */
export type NoticeAction = {
	label: string;
	url?: string;
	onClick?: () => void;
};

/**
 * Options accepted by `createNotice` (and its status-specific variants).
 * Mirrors `@wordpress/notices`' `NoticeOptions` type.
 */
export type NoticeOptions = {
	context?: string;
	id?: string;
	isDismissible?: boolean;
	type?: 'default' | 'snackbar';
	speak?: boolean;
	actions?: NoticeAction[];
	icon?: string | JSX.Element | null;
	explicitDismiss?: boolean;
	onDismiss?: () => void;
};
