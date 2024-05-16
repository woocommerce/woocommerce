/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { createSlotFill, hasValidFills, useSlotFills } from '../../slot';
import TotalsWrapper from '../../../components/totals-wrapper';

const slotName = '__experimentalOrderMeta';

const { Fill: ExperimentalOrderMeta, Slot: OrderMetaSlot } =
	createSlotFill( slotName );

const Slot = ( { className, extensions, cart, context } ) => {
	const fills = useSlotFills( slotName );

	return (
		hasValidFills( fills ) && (
			<TotalsWrapper slotWrapper={ true }>
				<OrderMetaSlot
					className={ classnames(
						className,
						'wc-block-components-order-meta'
					) }
					fillProps={ { extensions, cart, context } }
				/>
			</TotalsWrapper>
		)
	);
};

ExperimentalOrderMeta.Slot = Slot;

export default ExperimentalOrderMeta;
