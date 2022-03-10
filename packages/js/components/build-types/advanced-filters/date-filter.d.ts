export default DateFilter;
declare class DateFilter extends Component<any, any, any> {
    constructor({ filter }: {
        filter: any;
    }, ...args: any[]);
    state: {
        before: any;
        beforeText: any;
        beforeError: null;
        after: any;
        afterText: any;
        afterError: null;
        rule: any;
    };
    onSingleDateChange({ date, text, error }: {
        date: any;
        text: any;
        error: any;
    }): void;
    onRangeDateChange(input: any, { date, text, error }: {
        date: any;
        text: any;
        error: any;
    }): void;
    onRuleChange(newRule: any): void;
    getBetweenString(): string;
    getScreenReaderText(filterRule: any, config: any): string;
    isFutureDate(dateString: any): boolean;
    getFormControl({ date, error, onUpdate, text }: {
        date: any;
        error: any;
        onUpdate: any;
        text: any;
    }): JSX.Element;
    getRangeInput(): JSX.Element;
    getFilterInputs(): JSX.Element;
    render(): JSX.Element;
}
import { Component } from "@wordpress/element/build-types/react";
//# sourceMappingURL=date-filter.d.ts.map