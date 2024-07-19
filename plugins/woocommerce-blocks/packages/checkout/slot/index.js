/**
 * External dependencies
 */
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';
import { Children, cloneElement } from '@wordpress/element';
// It is very important to export this directly from the build module to avoid introducing side-effects
// from importing the index of the @wordpress/components package.
// eslint-disable-next-line -- When adding comments to imports it breaks the external/internal dependencies lint.
import {
	createSlotFill as baseCreateSlotFill,
	useSlot,
	useSlotFills,
} from 'wordpress-components-slotfill/build-module/slot-fill';

/**
 * Internal dependencies
 */
import BlockErrorBoundary from '../components/error-boundary';

/**
 * Checks if this slot has any valid fills. A valid fill is one that isn't falsy.
 *
 * @param {Array} fills The list of fills to check for a valid one in.
 * @return {boolean} True if this slot contains any valid fills.
 */
export const hasValidFills = ( fills ) =>
	Array.isArray( fills ) && fills.filter( Boolean ).length > 0;

export { useSlot, useSlotFills };

/**
 * Abstracts @wordpress/components createSlotFill, wraps Fill in an error boundary and passes down fillProps.
 *
 * @param {string}                         slotName  The generated slotName, based down to createSlotFill.
 * @param {null|function(Element):Element} [onError] Returns an element to display the error if the current use is an admin.
 *
 * @return {Object} Returns a newly wrapped Fill and Slot.
 */
export const createSlotFill = ( slotName, onError = null ) => {
	const { Fill: BaseFill, Slot: BaseSlot } = baseCreateSlotFill( slotName );

	/**
	 * A Fill that will get rendered inside associate slot.
	 * If the code inside has a error, it would be caught and removed.
	 * The error is only visible to admins.
	 *
	 * @param {Object} props          Items props.
	 * @param {Array}  props.children Children to be rendered.
	 */
	const Fill = ( { children } ) => (
		<BaseFill>
			{ ( fillProps ) =>
				Children.map( children, ( fill ) => (
					<BlockErrorBoundary
						/* Returning null would trigger the default error display.
						 * Returning () => null would render nothing.
						 */
						renderError={
							CURRENT_USER_IS_ADMIN ? onError : () => null
						}
					>
						{ cloneElement( fill, fillProps ) }
					</BlockErrorBoundary>
				) )
			}
		</BaseFill>
	);

	/**
	 * A Slot that will get rendered inside our tree.
	 * This forces Slot to use the Portal implementation that allows events to be bubbled to react tree instead of dom tree.
	 *
	 * @param {Object}         [props]         Slot props.
	 * @param {string}         props.className Class name to be used on slot.
	 * @param {Object}         props.fillProps Props to be passed to fills.
	 * @param {Element|string} props.as        Element used to render the slot, defaults to div.
	 *
	 */
	const Slot = ( props ) => <BaseSlot { ...props } bubblesVirtually />;

	return {
		Fill,
		Slot,
	};
};
