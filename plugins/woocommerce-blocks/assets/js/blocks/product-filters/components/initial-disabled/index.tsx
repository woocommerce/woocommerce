/**
 * Internal dependencies
 */
import './style.scss';

/**
 * The reason for this component instead of using core/disabled is, the Disabled
 * component messes up the focus on inner blocks. For example, when a there is
 * an heading block nested inside, the text cursor, which indicates the editable
 * area, isn't visible when focus on the heading block.
 *
 * This component only use CSS to control the selecte behavior of inner blocks,
 * which fix the issues like mentioned above.
 *
 * Limitation: being a static component comes with a limitation, this component
 * is meant to put directly inside the block wrapper element that holds block
 * props.
 */
const InitialDisabled = ( {
	children,
}: {
	isDisabled: boolean;
	children: React.ReactNode;
} ): JSX.Element => (
	<div className="wc-component-initial-disabled">
		<div className="wc-component-initial-disabled-overlay" />
		{ children }
	</div>
);

export default InitialDisabled;
