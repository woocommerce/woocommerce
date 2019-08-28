/** @format */
/**
 * Internal dependencies
 */
import { SplitButton } from '@woocommerce/components';

/**
 * External dependencies
 */
import Gridicon from 'gridicons';

export default () => (
	<div>
		<SplitButton
			isPrimary
			mainLabel="Primary Button"
			menuLabel="Select an action"
			onClick={ () => alert( 'Primary Main Action clicked' ) }
			controls={ [
				{
					label: 'Up',
					onClick: () => alert( 'Primary Up clicked' ),
				},
				{
					label: 'Right',
					onClick: () => alert( 'Primary Right clicked' ),
				},
				{
					label: 'Down',
					icon: <Gridicon icon="arrow-down" />,
					onClick: () => alert( 'Primary Down clicked' ),
				},
				{
					label: 'Left',
					icon: <Gridicon icon="arrow-left" />,
					onClick: () => alert( 'Primary Left clicked' ),
				},
			] }
		/>

		<SplitButton
			mainIcon={ <Gridicon icon="pencil" /> }
			menuLabel="Select an action"
			onClick={ () => alert( 'Icon Only Action clicked' ) }
			controls={ [
				{
					label: 'Up',
					icon: <Gridicon icon="arrow-up" />,
					onClick: () => alert( 'Icon Only Up clicked' ),
				},
				{
					label: 'Right',
					onClick: () => alert( 'Icon Only Right clicked' ),
				},
				{
					label: 'Down',
					icon: <Gridicon icon="arrow-down" />,
					onClick: () => alert( 'Icon Only Down clicked' ),
				},
				{
					label: 'Left',
					icon: <Gridicon icon="arrow-left" />,
					onClick: () => alert( 'Icon Only Left clicked' ),
				},
			] }
		/>
	</div>
);
