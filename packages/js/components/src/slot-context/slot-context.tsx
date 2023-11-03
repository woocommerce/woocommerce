/**
 * External dependencies
 */
import {
	createElement,
	createContext,
	useContext,
	useCallback,
	useReducer,
} from '@wordpress/element';

type FillConfigType = {
	visible: boolean;
};

type FillType = Record< string, FillConfigType >;

type FillCollection = readonly ( readonly JSX.Element[] )[];

export type SlotContextHelpersType = {
	hideFill: ( id: string ) => void;
	showFill: ( id: string ) => void;
	getFills: () => FillType;
};

export type SlotContextType = {
	fills: FillType;
	getFillHelpers: () => SlotContextHelpersType;
	registerFill: ( id: string ) => void;
	filterRegisteredFills: ( fillsArrays: FillCollection ) => FillCollection;
};

const SlotContext = createContext< SlotContextType | undefined >( undefined );

export const SlotContextProvider: React.FC = ( { children } ) => {
	const [ fills, updateFills ] = useReducer(
		( data: FillType, updates: FillType ) => ( { ...data, ...updates } ),
		{}
	);

	const updateFillConfig = (
		id: string,
		update: Partial< FillConfigType >
	) => {
		if ( ! fills[ id ] ) {
			throw new Error( `No fill found with ID: ${ id }` );
		}
		updateFills( { [ id ]: { ...fills[ id ], ...update } } );
	};

	const registerFill = useCallback(
		( id: string ) => {
			if ( fills[ id ] ) {
				return;
			}
			updateFills( { [ id ]: { visible: true } } );
		},
		[ fills ]
	);

	const hideFill = useCallback(
		( id: string ) => updateFillConfig( id, { visible: false } ),
		[ fills ]
	);

	const showFill = useCallback(
		( id: string ) => updateFillConfig( id, { visible: true } ),
		[ fills ]
	);

	const getFills = useCallback( () => ( { ...fills } ), [ fills ] );

	return (
		<SlotContext.Provider
			value={ {
				registerFill,
				getFillHelpers() {
					return { hideFill, showFill, getFills };
				},
				filterRegisteredFills( fillsArrays: FillCollection ) {
					return fillsArrays.filter(
						( arr ) =>
							fills[ arr[ 0 ].props._id ]?.visible !== false
					);
				},
				fills,
			} }
		>
			{ children }
		</SlotContext.Provider>
	);
};

export const useSlotContext = () => {
	const slotContext = useContext( SlotContext );

	if ( slotContext === undefined ) {
		throw new Error(
			'useSlotContext must be used within a SlotContextProvider'
		);
	}

	return slotContext;
};
