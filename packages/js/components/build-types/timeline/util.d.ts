export namespace groupByOptions {
    const DAY: string;
    const WEEK: string;
    const MONTH: string;
}
export function groupItemsUsing(groupBy: any): (groups: any, newItem: any) => any;
export namespace orderByOptions {
    const ASC: string;
    const DESC: string;
}
export function sortByDateUsing(orderBy: any): (groupA: any, groupB: any) => number;
//# sourceMappingURL=util.d.ts.map