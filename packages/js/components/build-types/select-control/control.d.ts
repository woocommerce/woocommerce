export default Control;
/**
 * A search control to allow user input to filter the options.
 */
declare class Control extends Component<any, any, any> {
    constructor(props: any);
    state: {
        isActive: boolean;
    };
    input: import("react").RefObject<any>;
    updateSearch(onSearch: any): (event: any) => void;
    onFocus(onSearch: any): (event: any) => void;
    onBlur(): void;
    onKeyDown(event: any): void;
    renderButton(): JSX.Element | null;
    renderInput(): JSX.Element;
    getInputValue(): any;
    render(): JSX.Element;
}
declare namespace Control {
    namespace propTypes {
        const hasTags: PropTypes.Requireable<boolean>;
        const help: PropTypes.Requireable<string | number | boolean | {} | PropTypes.ReactElementLike | PropTypes.ReactNodeArray>;
        const inlineTags: PropTypes.Requireable<boolean>;
        const isSearchable: PropTypes.Requireable<boolean>;
        const instanceId: PropTypes.Requireable<number>;
        const label: PropTypes.Requireable<string>;
        const listboxId: PropTypes.Requireable<string>;
        const onBlur: PropTypes.Requireable<(...args: any[]) => any>;
        const onChange: PropTypes.Requireable<(...args: any[]) => any>;
        const onSearch: PropTypes.Requireable<(...args: any[]) => any>;
        const placeholder: PropTypes.Requireable<string>;
        const query: PropTypes.Requireable<string>;
        const selected: PropTypes.Requireable<(PropTypes.InferProps<{
            key: PropTypes.Validator<string | number>;
            label: PropTypes.Requireable<string>;
        }> | null | undefined)[]>;
        const showAllOnFocus: PropTypes.Requireable<boolean>;
        const autoComplete: PropTypes.Requireable<string>;
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=control.d.ts.map