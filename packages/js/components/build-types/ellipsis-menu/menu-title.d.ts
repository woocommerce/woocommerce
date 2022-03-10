export default MenuTitle;
/**
 * `MenuTitle` is another valid Menu child, but this does not have any accessibility attributes associated
 * (so this should not be used in place of the `EllipsisMenu` prop `label`).
 *
 * @param {Object} props
 * @param {Node} props.children
 * @return {Object} -
 */
declare function MenuTitle({ children }: {
    children: Node;
}): Object;
declare namespace MenuTitle {
    namespace propTypes {
        const children: PropTypes.Requireable<PropTypes.ReactNodeLike>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=menu-title.d.ts.map