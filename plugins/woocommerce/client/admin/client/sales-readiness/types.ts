/**
 * What wc/v3/agent-readiness/view returns: the Sales readiness page and the AI tools
 * Overview as text, badges and links, built by ReadinessCheckPage in PHP.
 */

export type BadgeIntent =
	'high' | 'medium' | 'low' | 'stable' | 'informational' | 'draft' | 'none';

export interface ViewBadge {
	intent: BadgeIntent;
	text: string;
}

export interface ViewLink {
	/** Empty when the text is shown without a link. */
	href: string;
	text: string;
}

export type RowState = 'open' | 'required' | 'unknown' | 'pass';

export interface CheckRow {
	/** The check's id, for tracking. */
	id: string;
	state: RowState;
	/** What to do while the check fails, its passing title once it passes. */
	title: string;
	/** Required, or Likely conflict. */
	badge: ViewBadge | null;
	/** Why it matters, and what the check found while it fails. */
	text: string;
	/** What to do, or '' when there is nothing more specific than the title. */
	fix: string;
	link: ViewLink | null;
	seen_at: ViewLink | null;
}

export interface FixFirstItem {
	/** The check's id, for tracking. */
	id: string;
	tier: string;
	/** Under the Required for AI shopping heading, which carries a Required badge. */
	required: boolean;
	title: string;
	why: string;
	link: ViewLink | null;
}

export interface FixFirst {
	title: string;
	ranking: string;
	items: FixFirstItem[];
}

export interface Channel {
	name: string;
	listable: string;
	/** "of 15 can be listed". */
	of: string;
}

export interface Section {
	id: string;
	title: string;
	badge: ViewBadge;
	note: string | null;
	channels: Channel[];
	rows: CheckRow[];
	folded: CheckRow[];
	/** What the card says instead of checks: every product passed, or there are no products yet. */
	empty: { title: string; text: string; link: ViewLink | null } | null;
}

export interface Declared {
	title: string;
	text: string;
	more: string;
	items: { name: string; found: boolean }[];
}

export interface ShoppingCard {
	title: string;
	badge: ViewBadge | null;
	text: string;
	button: ViewLink;
}

export interface ReadinessView {
	title: string;
	description: string;
	last_checked: string;
	checked_note: string | null;
	intro: string | null;
	run: {
		label: string;
		pending: boolean;
		stalled: boolean;
		message: string;
		/** Scheduled Actions, when the check can't start. */
		link: ViewLink | null;
	};
	fix_first: FixFirst | null;
	sections: Section[];
	declared: Declared | null;
	overview: {
		shopping: ShoppingCard;
		view_all: ViewLink;
		/** "Based on your 5,000 most recently changed products.", when the check stopped at the limit. */
		based_on: string | null;
	};
}
