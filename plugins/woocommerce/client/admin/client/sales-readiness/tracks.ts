/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import type { ReadinessView } from './types';

/** Where the event happened: the Sales readiness page or the AI tools Overview. */
export type Surface = 'page' | 'overview';

type Props = Record< string, string | number | boolean >;

/**
 * Records a Sales readiness event; Tracks adds the wcadmin_ prefix and respects the store's
 * tracking opt-in.
 */
export function trackReadiness( name: string, props: Props ) {
	recordEvent( 'sales_readiness_' + name, props );
}

/**
 * What the merchant saw when the view loaded: whether the store was checked, and how many
 * checks were in each state.
 */
export function viewedProps( view: ReadinessView, surface: Surface ): Props {
	const counts = { open: 0, required: 0, unknown: 0, pass: 0 };
	for ( const section of view.sections ) {
		for ( const row of [ ...section.rows, ...section.folded ] ) {
			counts[ row.state ]++;
		}
	}
	return {
		surface,
		checked: ! view.intro,
		pending: view.run.pending,
		fix_first_count: view.fix_first?.items.length ?? 0,
		open_count: counts.open,
		required_count: counts.required,
		unknown_count: counts.unknown,
		pass_count: counts.pass,
	};
}
