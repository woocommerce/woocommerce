export default Link;
/**
 * Use `Link` to create a link to another resource. It accepts a type to automatically
 * create wp-admin links, wc-admin links, and external links.
 */
declare function Link({ children, href, type, ...props }: {
    [x: string]: any;
    children: any;
    href: any;
    type: any;
}): JSX.Element;
declare namespace Link {
    namespace propTypes {
        const href: PropTypes.Validator<string>;
        const type: PropTypes.Validator<string>;
    }
    namespace defaultProps {
        const type_1: string;
        export { type_1 as type };
    }
    namespace contextTypes {
        const router: PropTypes.Requireable<object>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map