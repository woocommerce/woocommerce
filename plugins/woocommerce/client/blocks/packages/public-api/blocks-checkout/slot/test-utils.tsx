/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { Provider as SlotFillProvider } from 'wordpress-components-slotfill/build-module/slot-fill'; // eslint-disable-line @typescript-eslint/no-unused-vars -- Provider is used as JSX.

type SlotFillComponent = {
	( props: { children: React.ReactNode } ): JSX.Element;
	Slot: ( props: Record< string, unknown > ) => JSX.Element;
};

/**
 * Renders a SlotFill pair within a SlotFillProvider.
 *
 * Places a spy component inside the Fill that captures the fillProps
 * passed via cloneElement. Returns both the render result and a
 * jest.fn() that was called with the received fillProps.
 *
 * @param Fill      The Fill component (e.g. ExperimentalOrderMeta).
 * @param slotProps Props passed to Fill.Slot (including fillProps).
 * @return Object with render result and fillPropsSpy.
 */
export const renderSlotFill = (
	Fill: SlotFillComponent,
	slotProps: Record< string, unknown > = {}
) => {
	const fillPropsSpy = jest.fn();

	// A component that captures all props it receives (merged via cloneElement)
	// and calls the spy with them for assertion.
	const FillPropsSpy = ( props: Record< string, unknown > ) => {
		fillPropsSpy( props );
		return <div data-testid="fill-content" />;
	};

	const result = render(
		<SlotFillProvider>
			<Fill>
				<FillPropsSpy />
			</Fill>
			<Fill.Slot { ...slotProps } />
		</SlotFillProvider>
	);

	return { ...result, fillPropsSpy };
};

/**
 * Renders a SlotFill and returns the fillProps received by the fill child.
 *
 * Unlike JSON-based approaches, this preserves function references and
 * other non-serializable values.
 *
 * @param Fill      The Fill component.
 * @param slotProps Props to pass to Fill.Slot.
 * @return The fillProps object.
 */
export const getFillProps = (
	Fill: SlotFillComponent,
	slotProps: Record< string, unknown > = {}
): Record< string, unknown > => {
	const { fillPropsSpy } = renderSlotFill( Fill, slotProps );

	expect( fillPropsSpy ).toHaveBeenCalled();
	return fillPropsSpy.mock.calls[ 0 ][ 0 ];
};
