/**
 * Internal dependencies
 */
import { ImportStatusBar } from '../import-status-bar';
import ReportFilters from '../report-filters';

import './report-header.scss';

interface ReportHeaderProps {
	/**
	 * Config option passed through to `AdvancedFilters`
	 */
	advancedFilters?: object;
	/**
	 * Config option passed through to `FilterPicker`
	 */
	filters?: Array< unknown >;
	/**
	 * The `path` parameter supplied by React-Router
	 */
	path: string;
	/**
	 * The query string represented in object form
	 */
	query: object;
	/**
	 * Whether the date picker must be shown
	 */
	showDatePicker?: boolean;
	/**
	 * The report where filters are placed
	 */
	report: string;
}

export default function ReportHeader( props: ReportHeaderProps ): JSX.Element {
	return (
		<div className="woocommerce-analytics-report-header">
			{ /* @ts-expect-error - ReportFilters is a valid component but not typed */ }
			<ReportFilters { ...props } />
			{ !! window.wcAdminFeatures?.[ 'analytics-scheduled-import' ] && (
				<ImportStatusBar />
			) }
		</div>
	);
}
