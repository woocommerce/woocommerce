/**
 * Internal dependencies
 */
import './style.scss';

/**
 * The reason for using this component instead of the core/disabled component is
 * that the Disabled component disrupts the focus on inner blocks. For example,
 * when a heading block is nested inside, the text cursor, which indicates the
 * editable area, isn't visible when focused on the heading block.
 *
 * This component only uses CSS to control the selected behavior of inner
 * blocks, which fixes the abovementioned issues. However, being a static
 * component comes with a limitation: this component is meant to be placed
 * directly inside the block wrapper element that holds block props.
 */
export const InitialDisabled = ( {
	children,
}: {
	children: React.ReactNode;
} ): JSX.Element => (
	<div className="wc-block-product-filter-components-initial-disabled">
		<div className="wc-block-product-filter-components-initial-disabled-overlay" />
		{ children }
	</div>
);
