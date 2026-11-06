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
	state: RowState;
	title: string;
	/** "Affects: …", or ''. */
	affects: string;
	lines: string[];
	link: ViewLink | null;
	seen_at: ViewLink | null;
}

export interface FixFirstItem {
	tier: string;
	title: string;
	why: string;
	impact: ViewBadge | null;
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
	empty: string | null;
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
	};
	fix_first: FixFirst | null;
	sections: Section[];
	declared: Declared | null;
	overview: {
		shopping: ShoppingCard;
		view_all: ViewLink;
	};
}
