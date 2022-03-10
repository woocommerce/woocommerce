export default CompareButton;
/**
 * A button used when comparing items, if `count` is less than 2 a hoverable tooltip is added with `helpText`.
 *
 * @param {Object} props
 * @param {string} props.className
 * @param {number} props.count
 * @param {Node} props.children
 * @param {boolean} props.disabled
 * @param {string} props.helpText
 * @param {Function} props.onClick
 * @return {Object} -
 */
declare function CompareButton({ className, count, children, disabled, helpText, onClick, }: {
    className: string;
    count: number;
    children: Node;
    disabled: boolean;
    helpText: string;
    onClick: Function;
}): Object;
declare namespace CompareButton {
    namespace propTypes {
        const className: PropTypes.Requireable<string>;
        const count: PropTypes.Validator<number>;
        const children: PropTypes.Validator<string | number | boolean | {} | PropTypes.ReactElementLike | PropTypes.ReactNodeArray>;
        const helpText: PropTypes.Validator<string>;
        const onClick: PropTypes.Validator<(...args: any[]) => any>;
        const disabled: PropTypes.Requireable<boolean>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=button.d.ts.map