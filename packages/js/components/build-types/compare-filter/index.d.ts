export { default as CompareButton } from "./button";
/**
 * Displays a card + search used to filter results as a comparison between objects.
 */
export class CompareFilter extends Component<any, any, any> {
    constructor({ getLabels, param, query }: {
        getLabels: any;
        param: any;
        query: any;
    }, ...args: any[]);
    state: {
        selected: never[];
    };
    clearQuery(): void;
    updateQuery(): void;
    updateLabels(selected: any): void;
    onButtonClicked(e: any): void;
    componentDidUpdate({ param: prevParam, query: prevQuery }: {
        param: any;
        query: any;
    }, { selected: prevSelected }: {
        selected: any;
    }): void;
    render(): JSX.Element;
}
export namespace CompareFilter {
    namespace propTypes {
        const getLabels: PropTypes.Validator<(...args: any[]) => any>;
        const labels: PropTypes.Requireable<PropTypes.InferProps<{
            /**
             * Label for the search placeholder.
             */
            placeholder: PropTypes.Requireable<string>;
            /**
             * Label for the card title.
             */
            title: PropTypes.Requireable<string>;
            /**
             * Label for button which updates the URL/report.
             */
            update: PropTypes.Requireable<string>;
        }>>;
        const param: PropTypes.Validator<string>;
        const path: PropTypes.Validator<string>;
        const query: PropTypes.Requireable<object>;
        const type: PropTypes.Validator<string>;
        const autocompleter: PropTypes.Requireable<object>;
    }
    namespace defaultProps {
        const labels_1: {};
        export { labels_1 as labels };
        const query_1: {};
        export { query_1 as query };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map