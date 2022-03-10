export default SectionHeader;
/**
 * A header component. The header can contain a title, actions via children, and an `EllipsisMenu` menu.
 */
declare class SectionHeader extends Component<any, any, any> {
    constructor(props: any);
    constructor(props: any, context: any);
    render(): JSX.Element;
}
declare namespace SectionHeader {
    namespace propTypes {
        const className: PropTypes.Requireable<string>;
        const menu: (props: any, propName: any, componentName: any) => Error | undefined;
        const title: PropTypes.Validator<string | number | boolean | {} | PropTypes.ReactElementLike | PropTypes.ReactNodeArray>;
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map