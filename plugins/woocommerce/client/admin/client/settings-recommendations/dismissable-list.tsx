/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { Button, Card, CardHeader } from '@wordpress/components';
import { optionsStore } from '@woocommerce/data';
import { EllipsisMenu } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { createContext, useContext } from '@wordpress/element';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './dismissable-list.scss';

// using a context provider for the option name so that the option name prop doesn't need to be passed to the `DismissableListHeading` too
const OptionNameContext = createContext( '' );

export const DismissableListHeading = ( {
	onDismiss = () => null,
	children,
}: {
	children: React.ReactNode;
	onDismiss?: () => void;
} ) => {
	const { updateOptions } = useDispatch( optionsStore );
	const dismissOptionName = useContext( OptionNameContext );

	const handleDismissClick = () => {
		onDismiss();
		// Persist via the legacy Options API only in option-backed mode. In
		// controlled mode (no option name) the parent handles persistence.
		if ( dismissOptionName ) {
			updateOptions( {
				[ dismissOptionName ]: 'yes',
			} );
		}
	};

	return (
		<CardHeader>
			<div className="woocommerce-dismissable-list__header">
				{ children }
			</div>
			<div>
				<EllipsisMenu
					label={ __( 'Task List Options', 'woocommerce' ) }
					renderContent={ () => (
						<div className="woocommerce-dismissable-list__controls">
							<Button onClick={ handleDismissClick }>
								{ __( 'Hide this', 'woocommerce' ) }
							</Button>
						</div>
					) }
				/>
			</div>
		</CardHeader>
	);
};

export const DismissableList = ( {
	children,
	className,
	dismissOptionName,
	isDismissed,
}: {
	children: React.ReactNode;
	className?: string;
	/**
	 * Legacy Options API option name used to persist the dismissal. Omit when
	 * the parent controls dismissal (e.g. via a dedicated endpoint) and pass
	 * `isDismissed` instead.
	 */
	dismissOptionName?: string;
	/**
	 * Controlled dismissal state. Only used when `dismissOptionName` is omitted.
	 */
	isDismissed?: boolean;
} ) => {
	const optionIsVisible = useSelect(
		( select ) => {
			if ( ! dismissOptionName ) {
				return null;
			}

			const { getOption, hasFinishedResolution } = select( optionsStore );

			const hasFinishedResolving = hasFinishedResolution( 'getOption', [
				dismissOptionName,
			] );

			return (
				hasFinishedResolving && getOption( dismissOptionName ) !== 'yes'
			);
		},
		[ dismissOptionName ]
	);

	const isVisible = dismissOptionName ? optionIsVisible : ! isDismissed;

	if ( ! isVisible ) {
		return null;
	}

	return (
		<Card
			size="medium"
			className={ clsx( 'woocommerce-dismissable-list', className ) }
		>
			<OptionNameContext.Provider value={ dismissOptionName ?? '' }>
				{ children }
			</OptionNameContext.Provider>
		</Card>
	);
};
