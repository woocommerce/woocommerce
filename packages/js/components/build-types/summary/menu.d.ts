export default Menu;
declare function Menu({ label, orientation, itemCount, items }: {
    label: any;
    orientation: any;
    itemCount: any;
    items: any;
}): JSX.Element;
declare namespace Menu {
    namespace propTypes {
        const label: PropTypes.Requireable<string>;
        const orientation: PropTypes.Validator<string>;
        const items: PropTypes.Validator<string | number | boolean | {} | PropTypes.ReactElementLike | PropTypes.ReactNodeArray>;
        const itemCount: PropTypes.Validator<number>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=menu.d.ts.map