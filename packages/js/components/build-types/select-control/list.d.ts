export default List;
/**
 * A list box that displays filtered options after search.
 */
declare class List extends Component<any, any, any> {
    constructor(...args: any[]);
    handleKeyDown(event: any): void;
    select(option: any): void;
    optionRefs: {};
    listbox: import("react").RefObject<any>;
    componentDidUpdate(prevProps: any): void;
    getOptionRef(index: any): any;
    scrollToOption(index: any): void;
    toggleKeyEvents(isListening: any): void;
    componentDidMount(): void;
    componentWillUnmount(): void;
    render(): JSX.Element;
}
declare namespace List {
    namespace propTypes {
        const instanceId: PropTypes.Requireable<number>;
        const listboxId: PropTypes.Requireable<string>;
        const node: PropTypes.Validator<Element>;
        const onSelect: PropTypes.Requireable<(...args: any[]) => any>;
        const options: PropTypes.Validator<(PropTypes.InferProps<{
            isDisabled: PropTypes.Requireable<boolean>;
            key: PropTypes.Validator<string | number>;
            keywords: PropTypes.Requireable<(string | number | null | undefined)[]>;
            label: PropTypes.Requireable<string | object>;
            value: PropTypes.Requireable<any>;
        }> | null | undefined)[]>;
        const selectedIndex: PropTypes.Requireable<number>;
        const staticList: PropTypes.Requireable<boolean>;
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=list.d.ts.map