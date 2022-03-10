/**
 * A search box which autocompletes results while typing, allowing for the user to select an existing object
 * (product, order, customer, etc). Currently only products are supported.
 */
export class Search extends Component<any, any, any> {
    constructor(props: any);
    state: {
        options: never[];
    };
    appendFreeTextSearch(options: any, query: any): any;
    fetchOptions(previousOptions: any, query: any): never[] | Promise<any[]>;
    updateSelected(selected: any): void;
    getAutocompleter(): any;
    getFormattedOptions(options: any, query: any): any[];
    render(): JSX.Element;
}
export namespace Search {
    namespace propTypes {
        const allowFreeTextSearch: PropTypes.Requireable<boolean>;
        const className: PropTypes.Requireable<string>;
        const onChange: PropTypes.Requireable<(...args: any[]) => any>;
        const type: PropTypes.Validator<string>;
        const autocompleter: PropTypes.Requireable<object>;
        const placeholder: PropTypes.Requireable<string>;
        const selected: PropTypes.Requireable<string | (PropTypes.InferProps<{
            key: PropTypes.Validator<string | number>;
            label: PropTypes.Requireable<string>;
        }> | null | undefined)[]>;
        const inlineTags: PropTypes.Requireable<boolean>;
        const showClearButton: PropTypes.Requireable<boolean>;
        const staticResults: PropTypes.Requireable<boolean>;
        const disabled: PropTypes.Requireable<boolean>;
    }
    namespace defaultProps {
        const allowFreeTextSearch_1: boolean;
        export { allowFreeTextSearch_1 as allowFreeTextSearch };
        export { noop as onChange };
        const selected_1: never[];
        export { selected_1 as selected };
        const inlineTags_1: boolean;
        export { inlineTags_1 as inlineTags };
        const showClearButton_1: boolean;
        export { showClearButton_1 as showClearButton };
        const staticResults_1: boolean;
        export { staticResults_1 as staticResults };
        const disabled_1: boolean;
        export { disabled_1 as disabled };
        export const multiple: boolean;
    }
}
export default Search;
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
import { noop } from "lodash/common/util";
//# sourceMappingURL=index.d.ts.map