export default Rating;
/**
 * Use `Rating` to display a set of stars, filled, empty or half-filled, that represents a
 * rating in a scale between 0 and the prop `totalStars` (default 5).
 */
declare class Rating extends Component<any, any, any> {
    constructor(props: any);
    constructor(props: any, context: any);
    stars(icon: any): JSX.Element[];
    render(): JSX.Element;
}
declare namespace Rating {
    namespace propTypes {
        const rating: PropTypes.Requireable<number>;
        const totalStars: PropTypes.Requireable<number>;
        const size: PropTypes.Requireable<number>;
        const className: PropTypes.Requireable<string>;
        const icon: PropTypes.Requireable<PropTypes.ReactComponentLike>;
        const outlineIcon: PropTypes.Requireable<PropTypes.ReactComponentLike>;
    }
    namespace defaultProps {
        const rating_1: number;
        export { rating_1 as rating };
        const totalStars_1: number;
        export { totalStars_1 as totalStars };
        const size_1: number;
        export { size_1 as size };
    }
}
import { Component } from "@wordpress/element/build-types/react";
import PropTypes from "prop-types";
//# sourceMappingURL=index.d.ts.map