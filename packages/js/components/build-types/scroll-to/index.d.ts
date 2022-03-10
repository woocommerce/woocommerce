export default ScrollTo;
declare class ScrollTo extends Component<any, any, any> {
    constructor(props: any);
    scrollTo(): void;
    componentDidMount(): void;
    render(): JSX.Element;
    ref: import("react").RefObject<any> | undefined;
}
declare namespace ScrollTo {
    namespace propTypes {
        const offset: PropTypes.Requireable<string>;
    }
    namespace defaultProps {
        const offset_1: string;
        export { offset_1 as offset };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map