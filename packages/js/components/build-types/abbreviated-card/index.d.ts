export default AbbreviatedCard;
declare function AbbreviatedCard({ children, className, href, icon, onClick, type, }: {
    children: any;
    className: any;
    href: any;
    icon: any;
    onClick: any;
    type: any;
}): JSX.Element;
declare namespace AbbreviatedCard {
    namespace propTypes {
        const children: PropTypes.Validator<string | number | boolean | {} | PropTypes.ReactElementLike | PropTypes.ReactNodeArray>;
        const className: PropTypes.Requireable<string>;
        const href: PropTypes.Validator<string>;
        const icon: PropTypes.Validator<PropTypes.ReactElementLike>;
        const onClick: PropTypes.Requireable<(...args: any[]) => any>;
        const type: PropTypes.Requireable<string>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map