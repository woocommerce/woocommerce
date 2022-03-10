export default EmptyContent;
/**
 * A component to be used when there is no data to show.
 * It can be used as an opportunity to provide explanation or guidance to help a user progress.
 */
declare class EmptyContent extends Component<any, any, any> {
    constructor(props: any);
    constructor(props: any, context: any);
    renderIllustration(): JSX.Element;
    renderActionButtons(type: any): JSX.Element | null;
    renderActions(): JSX.Element;
    render(): JSX.Element;
}
declare namespace EmptyContent {
    namespace propTypes {
        const title: PropTypes.Validator<string>;
        const message: PropTypes.Requireable<PropTypes.ReactNodeLike>;
        const illustration: PropTypes.Requireable<string>;
        const illustrationHeight: PropTypes.Requireable<number>;
        const illustrationWidth: PropTypes.Requireable<number>;
        const actionLabel: PropTypes.Validator<string>;
        const actionURL: PropTypes.Requireable<string>;
        const actionCallback: PropTypes.Requireable<(...args: any[]) => any>;
        const secondaryActionLabel: PropTypes.Requireable<string>;
        const secondaryActionURL: PropTypes.Requireable<string>;
        const secondaryActionCallback: PropTypes.Requireable<(...args: any[]) => any>;
        const className: PropTypes.Requireable<string>;
    }
    namespace defaultProps {
        const illustration_1: string;
        export { illustration_1 as illustration };
        const illustrationWidth_1: number;
        export { illustrationWidth_1 as illustrationWidth };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map