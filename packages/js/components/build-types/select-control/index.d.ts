/**
 * A search box which filters options while typing,
 * allowing a user to select from an option from a filtered list.
 */
export class SelectControl extends Component<any, any, any> {
    constructor(props: any);
    state: {
        searchOptions: never[];
        selectedIndex: any;
        isExpanded: boolean;
        isFocused: boolean;
        query: string;
    };
    bindNode(node: any): void;
    decrementSelectedIndex(): void;
    incrementSelectedIndex(): void;
    onAutofillChange(event: any): void;
    updateSearchOptions(query: any): void;
    search(query: any): void;
    selectOption(option: any): void;
    setExpanded(value: any): void;
    setNewValue(newValue: any): void;
    node: any;
    reset(selected?: any): void;
    handleFocusOutside(): void;
    hasMultiple(): boolean;
    getSelected(): any;
    announce(searchOptions: any): void;
    getOptions(): any;
    getOptionsByQuery(options: any, query: any): any;
    activePromise: any;
    cacheSearchOptions: any;
    render(): JSX.Element;
}
export namespace SelectControl {
    namespace propTypes {
        const autofill: PropTypes.Requireable<string>;
        const children: PropTypes.Requireable<PropTypes.ReactNodeLike>;
        const className: PropTypes.Requireable<string>;
        const controlClassName: PropTypes.Requireable<string>;
        const disabled: PropTypes.Requireable<boolean>;
        const excludeSelectedOptions: PropTypes.Requireable<boolean>;
        const onFilter: PropTypes.Requireable<(...args: any[]) => any>;
        const getSearchExpression: PropTypes.Requireable<(...args: any[]) => any>;
        const help: PropTypes.Requireable<string | number | boolean | {} | PropTypes.ReactElementLike | PropTypes.ReactNodeArray>;
        const inlineTags: PropTypes.Requireable<boolean>;
        const isSearchable: PropTypes.Requireable<boolean>;
        const label: PropTypes.Requireable<string>;
        const onChange: PropTypes.Requireable<(...args: any[]) => any>;
        const onSearch: PropTypes.Requireable<(...args: any[]) => any>;
        const options: PropTypes.Validator<(PropTypes.InferProps<{
            isDisabled: PropTypes.Requireable<boolean>;
            key: PropTypes.Validator<string | number>;
            keywords: PropTypes.Requireable<(string | number | null | undefined)[]>;
            label: PropTypes.Requireable<string | object>;
            value: PropTypes.Requireable<any>;
        }> | null | undefined)[]>;
        const placeholder: PropTypes.Requireable<string>;
        const searchDebounceTime: PropTypes.Requireable<number>;
        const selected: PropTypes.Requireable<string | (PropTypes.InferProps<{
            key: PropTypes.Validator<string | number>;
            label: PropTypes.Requireable<string>;
        }> | null | undefined)[]>;
        const maxResults: PropTypes.Requireable<number>;
        const multiple: PropTypes.Requireable<boolean>;
        const showClearButton: PropTypes.Requireable<boolean>;
        const searchInputType: PropTypes.Requireable<string>;
        const hideBeforeSearch: PropTypes.Requireable<boolean>;
        const showAllOnFocus: PropTypes.Requireable<boolean>;
        const staticList: PropTypes.Requireable<boolean>;
        const autoComplete: PropTypes.Requireable<string>;
    }
    namespace defaultProps {
        const autofill_1: null;
        export { autofill_1 as autofill };
        const excludeSelectedOptions_1: boolean;
        export { excludeSelectedOptions_1 as excludeSelectedOptions };
        export { identity as getSearchExpression };
        const inlineTags_1: boolean;
        export { inlineTags_1 as inlineTags };
        const isSearchable_1: boolean;
        export { isSearchable_1 as isSearchable };
        export { noop as onChange };
        export { identity as onFilter };
        export function onSearch_1(options: any): Promise<any>;
        export { onSearch_1 as onSearch };
        const maxResults_1: number;
        export { maxResults_1 as maxResults };
        const multiple_1: boolean;
        export { multiple_1 as multiple };
        const searchDebounceTime_1: number;
        export { searchDebounceTime_1 as searchDebounceTime };
        const searchInputType_1: string;
        export { searchInputType_1 as searchInputType };
        const selected_1: never[];
        export { selected_1 as selected };
        const showAllOnFocus_1: boolean;
        export { showAllOnFocus_1 as showAllOnFocus };
        const showClearButton_1: boolean;
        export { showClearButton_1 as showClearButton };
        const hideBeforeSearch_1: boolean;
        export { hideBeforeSearch_1 as hideBeforeSearch };
        const staticList_1: boolean;
        export { staticList_1 as staticList };
        const autoComplete_1: string;
        export { autoComplete_1 as autoComplete };
    }
}
declare var _default: any;
export default _default;
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
import { identity } from "lodash/common/util";
import { noop } from "lodash/common/util";
//# sourceMappingURL=index.d.ts.map