export default DatePicker;
declare class DatePicker extends Component<any, any, any> {
    constructor(props: any);
    onDateChange(onToggle: any, dateString: any): void;
    onInputChange(event: any): void;
    handleFocus(isOpen: any, onToggle: any): void;
    handleBlur(isOpen: any, onToggle: any, event: any): void;
    render(): JSX.Element;
}
declare namespace DatePicker {
    namespace propTypes {
        const date: PropTypes.Requireable<object>;
        const disabled: PropTypes.Requireable<boolean>;
        const text: PropTypes.Requireable<string>;
        const error: PropTypes.Requireable<string>;
        const onUpdate: PropTypes.Validator<(...args: any[]) => any>;
        const dateFormat: PropTypes.Validator<string>;
        const isInvalidDate: PropTypes.Requireable<(...args: any[]) => any>;
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=date-picker.d.ts.map