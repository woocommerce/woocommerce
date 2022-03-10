export default ViewMoreList;
/**
 * This component displays a 'X more' button that displays a list of items on a popover when clicked.
 *
 * @param {Object} props
 * @param {Array} props.items
 * @return {Object} -
 */
declare function ViewMoreList({ items }: {
    items: any[];
}): Object;
declare namespace ViewMoreList {
    namespace propTypes {
        const items: PropTypes.Requireable<PropTypes.ReactNodeLike[]>;
    }
    namespace defaultProps {
        const items_1: never[];
        export { items_1 as items };
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map