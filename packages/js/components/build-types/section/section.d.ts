/**
 * The section wrapper, used to indicate a sub-section (and change the header level context).
 *
 * @param {Object} props
 * @param {import('react').ComponentType=} props.component
 * @param {import('react').ReactNode} props.children Children to render in the tip.
 * @param {string=} props.className
 * @return {JSX.Element} -
 */
export function Section({ component, children, ...props }: {
    component?: import('react').ComponentType | undefined;
    children: import('react').ReactNode;
    className?: string | undefined;
}): JSX.Element;
export namespace Section {
    namespace propTypes {
        const component: PropTypes.Requireable<string | boolean | ((...args: any[]) => any)>;
        const children: PropTypes.Requireable<PropTypes.ReactNodeLike>;
        const className: PropTypes.Requireable<string>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=section.d.ts.map