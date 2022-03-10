export default EmptyTable;
/**
 * `EmptyTable` displays a blank space with an optional message passed as a children node
 * with the purpose of replacing a table with no rows.
 * It mimics the same height a table would have according to the `numberOfRows` prop.
 *
 * @param {Object} props
 * @param {Node} props.children
 * @param {number} props.numberOfRows
 * @return {Object} -
 */
declare function EmptyTable({ children, numberOfRows }: {
    children: Node;
    numberOfRows: number;
}): Object;
declare namespace EmptyTable {
    namespace propTypes {
        const numberOfRows: PropTypes.Requireable<number>;
    }
    namespace defaultProps {
        const numberOfRows_1: number;
        export { numberOfRows_1 as numberOfRows };
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=empty.d.ts.map