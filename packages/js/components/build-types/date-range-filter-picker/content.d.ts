export default DatePickerContent;
declare class DatePickerContent extends Component<any, any, any> {
    constructor();
    onTabSelect(tab: any): void;
    controlsRef: import("react").RefObject<any>;
    isFutureDate(dateString: any): boolean;
    render(): JSX.Element;
}
declare namespace DatePickerContent {
    namespace propTypes {
        const period: PropTypes.Validator<string>;
        const compare: PropTypes.Validator<string>;
        const onUpdate: PropTypes.Validator<(...args: any[]) => any>;
        const onClose: PropTypes.Validator<(...args: any[]) => any>;
        const onSelect: PropTypes.Validator<(...args: any[]) => any>;
        const resetCustomValues: PropTypes.Validator<(...args: any[]) => any>;
        const focusedInput: PropTypes.Requireable<string>;
        const afterText: PropTypes.Requireable<string>;
        const beforeText: PropTypes.Requireable<string>;
        const afterError: PropTypes.Requireable<string>;
        const beforeError: PropTypes.Requireable<string>;
        const shortDateFormat: PropTypes.Validator<string>;
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=content.d.ts.map