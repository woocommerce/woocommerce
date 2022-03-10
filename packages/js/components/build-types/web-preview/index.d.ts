export default WebPreview;
/**
 * WebPreview component to display an iframe of another page.
 */
declare class WebPreview extends Component<any, any, any> {
    constructor(props: any);
    state: {
        isLoading: boolean;
    };
    iframeRef: import("react").RefObject<any>;
    setLoaded(): void;
    componentDidMount(): void;
    render(): JSX.Element;
}
declare namespace WebPreview {
    namespace propTypes {
        const className: PropTypes.Requireable<string>;
        const loadingContent: PropTypes.Requireable<PropTypes.ReactNodeLike>;
        const onLoad: PropTypes.Requireable<(...args: any[]) => any>;
        const src: PropTypes.Validator<string>;
        const title: PropTypes.Validator<string>;
    }
    namespace defaultProps {
        const loadingContent_1: JSX.Element;
        export { loadingContent_1 as loadingContent };
        export { noop as onLoad };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
import { noop } from "lodash/common/util";
//# sourceMappingURL=index.d.ts.map