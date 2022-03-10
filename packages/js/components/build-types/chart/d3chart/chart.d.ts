export default D3Chart;
/**
 * A simple D3 line and bar chart component for timeseries data in React.
 */
declare class D3Chart extends Component<any, any, any> {
    constructor(props: any);
    drawChart(node: any): void;
    getParams(uniqueDates: any): {
        getColor: (key: any) => any;
        interval: any;
        mode: any;
        chartType: any;
        uniqueDates: any;
        visibleKeys: any;
    };
    tooltipRef: import("react").RefObject<any>;
    getFormatParams(): {
        screenReaderFormat: Function;
        xFormat: Function;
        x2Format: Function;
        yBelow1Format: Function;
        yFormat: Function;
    };
    getScaleParams(uniqueDates: any): {
        step: any;
        xScale: Function;
        yMax: any;
        yMin: any;
        yScale: Function;
        xGroupScale?: undefined;
    } | {
        step: any;
        xGroupScale: Function;
        xScale: Function;
        yMax: any;
        yMin: any;
        yScale: Function;
    };
    createTooltip(chart: any, getColorFunction: any, visibleKeys: any): void;
    tooltip: ChartTooltip | undefined;
    shouldBeCompact(): boolean;
    getMargin(): any;
    getWidth(): any;
    getEmptyMessage(): JSX.Element | undefined;
    render(): JSX.Element;
}
declare namespace D3Chart {
    namespace propTypes {
        const baseValue: PropTypes.Requireable<number>;
        const className: PropTypes.Requireable<string>;
        const colorScheme: PropTypes.Requireable<(...args: any[]) => any>;
        const data: PropTypes.Validator<any[]>;
        const dateParser: PropTypes.Validator<string>;
        const emptyMessage: PropTypes.Requireable<string>;
        const height: PropTypes.Requireable<number>;
        const interval: PropTypes.Requireable<string>;
        const margin: PropTypes.Requireable<PropTypes.InferProps<{
            bottom: PropTypes.Requireable<number>;
            left: PropTypes.Requireable<number>;
            right: PropTypes.Requireable<number>;
            top: PropTypes.Requireable<number>;
        }>>;
        const mode: PropTypes.Requireable<string>;
        const screenReaderFormat: PropTypes.Requireable<string | ((...args: any[]) => any)>;
        const orderedKeys: PropTypes.Requireable<any[]>;
        const tooltipLabelFormat: PropTypes.Requireable<string | ((...args: any[]) => any)>;
        const tooltipValueFormat: PropTypes.Requireable<string | ((...args: any[]) => any)>;
        const tooltipPosition: PropTypes.Requireable<string>;
        const tooltipTitle: PropTypes.Requireable<string>;
        const chartType: PropTypes.Requireable<string>;
        const width: PropTypes.Requireable<number>;
        const xFormat: PropTypes.Requireable<string | ((...args: any[]) => any)>;
        const x2Format: PropTypes.Requireable<string | ((...args: any[]) => any)>;
        const yBelow1Format: PropTypes.Requireable<string | ((...args: any[]) => any)>;
        const yFormat: PropTypes.Requireable<string | ((...args: any[]) => any)>;
    }
    namespace defaultProps {
        const baseValue_1: number;
        export { baseValue_1 as baseValue };
        const data_1: never[];
        export { data_1 as data };
        const dateParser_1: string;
        export { dateParser_1 as dateParser };
        const height_1: number;
        export { height_1 as height };
        export namespace margin_1 {
            const bottom: number;
            const left: number;
            const right: number;
            const top: number;
        }
        export { margin_1 as margin };
        const mode_1: string;
        export { mode_1 as mode };
        const screenReaderFormat_1: string;
        export { screenReaderFormat_1 as screenReaderFormat };
        const tooltipPosition_1: string;
        export { tooltipPosition_1 as tooltipPosition };
        const tooltipLabelFormat_1: string;
        export { tooltipLabelFormat_1 as tooltipLabelFormat };
        const tooltipValueFormat_1: string;
        export { tooltipValueFormat_1 as tooltipValueFormat };
        const chartType_1: string;
        export { chartType_1 as chartType };
        const width_1: number;
        export { width_1 as width };
        const xFormat_1: string;
        export { xFormat_1 as xFormat };
        const x2Format_1: string;
        export { x2Format_1 as x2Format };
        const yBelow1Format_1: string;
        export { yBelow1Format_1 as yBelow1Format };
        const yFormat_1: string;
        export { yFormat_1 as yFormat };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import ChartTooltip from "./utils/tooltip";
import PropTypes from "prop-types";
//# sourceMappingURL=chart.d.ts.map