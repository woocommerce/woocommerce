export default TableSummary;
export function TableSummaryPlaceholder(): Object;
/**
 * A component to display summarized table data - the list of data passed in on a single line.
 *
 * @param {Object} props
 * @param {Array} props.data
 * @return {Object} -
 */
declare function TableSummary({ data }: {
    data: any[];
}): Object;
declare namespace TableSummary {
    namespace propTypes {
        const data: PropTypes.Requireable<any[]>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=summary.d.ts.map