export default ChartTooltip;
declare class ChartTooltip {
    ref: any;
    chart: any;
    position: string;
    title: string;
    labelFormat: string;
    valueFormat: string;
    visibleKeys: string;
    getColor: any;
    margin: number;
    calculateXPosition(elementCoords: any, chartCoords: any, elementWidthRatio: any): number;
    calculateYPosition(elementCoords: any, chartCoords: any): any;
    calculatePosition(element: any, elementWidthRatio?: number): {
        x: number;
        y: any;
    };
    hide(): void;
    getTooltipRowLabel(d: any, row: any): any;
    show(d: any, triggerElement: any, parentNode: any, elementWidthRatio?: number): void;
}
//# sourceMappingURL=tooltip.d.ts.map