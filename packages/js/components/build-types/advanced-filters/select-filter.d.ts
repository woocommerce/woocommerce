export default SelectFilter;
declare class SelectFilter extends Component<any, any, any> {
    constructor({ filter, config, onFilterChange }: {
        filter: any;
        config: any;
        onFilterChange: any;
    }, ...args: any[]);
    state: {
        options: any;
    };
    updateOptions(options: any): any;
    getScreenReaderText(filter: any, config: any): string;
    render(): JSX.Element;
}
declare namespace SelectFilter {
    namespace propTypes {
        const config: PropTypes.Validator<PropTypes.InferProps<{
            labels: PropTypes.Requireable<PropTypes.InferProps<{
                rule: PropTypes.Requireable<string>;
                title: PropTypes.Requireable<string>;
                filter: PropTypes.Requireable<string>;
            }>>;
            rules: PropTypes.Requireable<(object | null | undefined)[]>;
            input: PropTypes.Requireable<object>;
        }>>;
        const filter: PropTypes.Validator<PropTypes.InferProps<{
            key: PropTypes.Requireable<string>;
            rule: PropTypes.Requireable<string>;
            value: PropTypes.Requireable<string>;
        }>>;
        const onFilterChange: PropTypes.Validator<(...args: any[]) => any>;
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=select-filter.d.ts.map