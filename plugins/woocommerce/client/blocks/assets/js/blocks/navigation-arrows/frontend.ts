/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { store, getContext } from '@wordpress/interactivity';

type NavigationArrowsContext = {
	isDisabledPrevious: boolean;
	isDisabledNext: boolean;
	labelPrevious: string;
	labelNext: string;
};

const getExternalState = ( stateName: string, defaultValue: any ) => {
	const ctx = getContext( 'woocommerce/navigation-arrows/context' );
	return ctx?.[ stateName ] || defaultValue;
};

const doExternalAction = ( actionName: string, event: Event ) => {
	const actions = getContext( 'woocommerce/navigation-arrows/actions' );
	const action = actions?.[ actionName ];

	if ( action ) {
		store( action.namespace )[ action.action ]( event );
	}
};

const navigationArrows = {
	state: {
		get isPreviousDisabled(): boolean {
			return getExternalState( 'isDisabledPrevious', false );
		},
		get isNextDisabled(): boolean {
			return getExternalState( 'isDisabledNext', false );
		},
		get labelPrevious(): string {
			return getExternalState(
				'labelPrevious',
				__( 'Previous', 'woocommerce' )
			);
		},
		get labelNext(): string {
			return getExternalState( 'labelNext', __( 'Next', 'woocommerce' ) );
		},
	},
	actions: {
		onClickPrevious: ( event: MouseEvent ) => {
			doExternalAction( 'onClickPrevious', event );
		},
		onClickNext: ( event: MouseEvent ) => {
			doExternalAction( 'onClickNext', event );
		},
		onKeyDownPrevious: () => {
			// TODO: Implement previous keydown action
		},
		onKeyDownNext: () => {
			// TODO: Implement next keydown action
		},
	},
};

export const { actions } = store(
	'woocommerce/navigation-arrows',
	navigationArrows,
	{
		lock: true,
	}
);

export type Store = typeof navigationArrows;
