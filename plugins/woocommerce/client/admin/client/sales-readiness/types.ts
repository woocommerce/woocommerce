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
	/** The check's id. */
	id: string;
	state: RowState;
	/** What to do while the check fails, its passing title once it passes. */
	title: string;
	/** Required. */
	badge: ViewBadge | null;
	/** Why it matters, and what the check found while it fails. */
	text: string;
	/** What to do, or '' when there is nothing more specific than the title. */
	fix: string;
	link: ViewLink | null;
	seen_at: ViewLink | null;
	/** A name in the text to link, such as a tool to test with. */
	text_link?: ViewLink | null;
}

export interface FixFirstItem {
	/** The check's id. */
	id: string;
	tier: string;
	/** Shown beside the tier heading: Required for the Required tier. */
	badge: ViewBadge | null;
	title: string;
	why: string;
	link: ViewLink | null;
}

export interface FixFirst {
	title: string;
	ranking: string;
	/** Where to turn on Analytics, while it is off. */
	link: ViewLink | null;
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
	badge: ViewBadge;
	text: string;
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
	/** Null before the first check, and when there is nothing to fix. */
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

/**
 * What WooCommerce > Settings > AI tools > AI shopping shows, built by
 * AgenticCommerceSettingsPage in PHP and handed to the page on its root.
 */
export interface ShoppingStep {
	/** The store condition's id. */
	id: string;
	state: 'required' | 'open';
	title: string;
	/** Required, for a step AI shopping needs. */
	badge: ViewBadge | null;
	/** Why it matters, then the fix line when there is one. */
	lines: string[];
	/** Where to fix it; `sr` names the policy for screen readers, or is ''. */
	link: ( ViewLink & { sr: string } ) | null;
}

export interface ShoppingCount {
	id: string;
	label: string;
	/** Already formatted for the store's locale. */
	count: string;
	/** What the count means, shown in a popover, or ''. */
	tip: string;
	/** The accessible name of the popover's button. */
	tip_label: string;
	link: ViewLink;
}

export interface PolicyField {
	/** The option the settings form saves. */
	id: string;
	/** The input name the settings form reads the value from. */
	name: string;
	label: string;
	value: string;
	options: { value: string; label: string }[];
}

export interface ShoppingView {
	parent: ViewLink;
	title: string;
	description: string;
	ucp: {
		enabled: boolean;
		/** Off, and a required step fails, so it can't be turned on. */
		blocked: boolean;
		title: string;
		description: string;
		learn_more: ViewLink;
		/** What the switch says under it: that AI shopping is on, or ''. */
		message: string;
		path: string;
		/** Said when turning AI shopping on didn't save, unless required steps are the reason. */
		error_on: string;
		/** Said when turning AI shopping off didn't save. */
		error_off: string;
		unsaved: string;
	};
	coming_soon: { text: string; link: ViewLink } | null;
	visibility: {
		title: string;
		description: string;
		/** Said instead of the counts before the first check. */
		empty: string | null;
		counts: ShoppingCount[];
	};
	checklist: {
		title: string;
		badge: ViewBadge | null;
		summary: string;
		steps: ShoppingStep[];
		done: { id: string; title: string; text: string }[];
		show_done: string;
		hide_done: string;
	};
	policies: {
		title: string;
		description: string;
		fields: PolicyField[];
	};
	connection: {
		title: string;
		description: string;
		url: string;
		copy: string;
		copied: string;
		test: ViewLink;
	};
}
