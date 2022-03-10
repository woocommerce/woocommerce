export default SearchListItem;
declare function SearchListItem({ countLabel, className, depth, controlId, item, isSelected, isSingle, onSelect, search, ...props }: {
    [x: string]: any;
    countLabel: any;
    className: any;
    depth?: number | undefined;
    controlId?: string | undefined;
    item: any;
    isSelected: any;
    isSingle: any;
    onSelect: any;
    search?: string | undefined;
}): JSX.Element;
declare namespace SearchListItem {
    namespace propTypes {
        const className: PropTypes.Requireable<string>;
        const countLabel: PropTypes.Requireable<PropTypes.ReactNodeLike>;
        const controlId: PropTypes.Requireable<PropTypes.ReactNodeLike>;
        const depth: PropTypes.Requireable<number>;
        const item: PropTypes.Requireable<object>;
        const name: PropTypes.Requireable<string>;
        const isSelected: PropTypes.Requireable<boolean>;
        const isSingle: PropTypes.Requireable<boolean>;
        const onSelect: PropTypes.Requireable<(...args: any[]) => any>;
        const search: PropTypes.Requireable<string>;
    }
}
import PropTypes from "prop-types";
//# sourceMappingURL=item.d.ts.map