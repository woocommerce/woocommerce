/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import {
	BaseControl,
	ComboboxControl as CoreComboboxControl,
	Spinner,
} from '@wordpress/components';
import {
	createElement,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME,
	ProductAttributesActions,
	WPDataActions,
} from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { TRACKS_SOURCE } from '../../constants';
import type {
	AttributesComboboxControlItemProps,
	AttributesComboboxControlProps,
	ComboboxAttributeProps,
} from './types';

function mapAttributeToComboboxOption(
	attr: AttributesComboboxControlItemProps
): ComboboxAttributeProps {
	return {
		label: attr.name,
		value: `attr-${ attr.id }`,
		disabled: !! attr.isDisabled,
	};
}

const createNewAttributeItemInitialState: ComboboxAttributeProps = {
	label: '',
	value: '',
	state: 'draft',
};

/*
 * Create an interface that includes the `__experimentalRenderItem` prop
 */
interface ComboboxControlProps
	extends Omit< CoreComboboxControl.Props, 'label' | 'help' > {
	__experimentalRenderItem?: ( args: {
		item: ComboboxAttributeProps;
	} ) => string | JSX.Element;
}

/*
 * Create an alias for the ComboBoxControl core component,
 * but with the new interface that includes the `__experimentalRenderItem` prop
 */
const ComboboxControl =
	CoreComboboxControl as React.ComponentType< ComboboxControlProps >;

type ComboboxControlItemProps = {
	item: ComboboxAttributeProps;
};

/**
 * ComboboxControlItem component.
 *
 * @param {ComboboxControlItemProps} props - props.
 * @return {JSX.Element}                     Component item.
 */
function ComboboxControlItem( props: ComboboxControlItemProps ): JSX.Element {
	const { item } = props;
	if ( item.disabled ) {
		return <div className="item-wrapper is-disabled">{ item.label }</div>;
	}

	return <div className="item-wrapper">{ item.label }</div>;
}

const AttributesComboboxControl: React.FC<
	AttributesComboboxControlProps
> = ( {
	label,
	help,
	current = null,
	items = [],
	createNewAttributesAsGlobal = false,
	instanceNumber = 0,
	isLoading = false,
	onChange,
} ) => {
	const createErrorNotice = useDispatch( 'core/notices' )?.createErrorNotice;
	const { createProductAttribute } = useDispatch(
		EXPERIMENTAL_PRODUCT_ATTRIBUTES_STORE_NAME
	) as unknown as ProductAttributesActions & WPDataActions;

	const [ createNewAttributeItem, updateCreateNewAttributeItem ] =
		useState< ComboboxAttributeProps >(
			createNewAttributeItemInitialState
		);

	const clearCreateNewAttributeItem = () =>
		updateCreateNewAttributeItem( createNewAttributeItemInitialState );

	/**
	 * Map the items to the Combobox options.
	 * Each option is an object with a label and value.
	 * Both are strings.
	 */
	const attributeOptions: ComboboxAttributeProps[] = items?.map(
		mapAttributeToComboboxOption
	);

	const options = useMemo( () => {
		if ( ! createNewAttributeItem.label.length ) {
			return attributeOptions;
		}

		return [
			...attributeOptions,
			{
				label:
					createNewAttributeItem.state === 'draft'
						? sprintf(
								/* translators: The name of the new attribute term to be created */
								__( 'Create "%s"', 'woocommerce' ),
								createNewAttributeItem.label
						  )
						: createNewAttributeItem.label,
				value: createNewAttributeItem.value,
			},
		];
	}, [ attributeOptions, createNewAttributeItem ] );

	// Attribute selected flag.
	const [ attributeSelected, setAttributeSelected ] = useState( false );

	// Get current of the selected item.
	let currentValue = current ? `attr-${ current.id }` : '';
	if ( createNewAttributeItem.state === 'creating' ) {
		currentValue = 'create-attribute';
	}

	const addNewAttribute = ( name: string ) => {
		recordEvent( 'product_attribute_add_custom_attribute', {
			source: TRACKS_SOURCE,
		} );
		if ( createNewAttributesAsGlobal ) {
			createProductAttribute(
				{
					name,
					generate_slug: true,
				},
				{
					optimisticQueryUpdate: {
						order_by: 'name',
					},
				}
			).then(
				( newAttr ) => {
					onChange( newAttr );
					clearCreateNewAttributeItem();
					setAttributeSelected( true );
				},
				( error ) => {
					let message = __(
						'Failed to create new attribute.',
						'woocommerce'
					);
					if ( error.code === 'woocommerce_rest_cannot_create' ) {
						message = error.message;
					}

					createErrorNotice?.( message, {
						explicitDismiss: true,
					} );

					clearCreateNewAttributeItem();
				}
			);
		} else {
			onChange( name );
		}
	};

	const comboRef = useRef< HTMLDivElement | null >( null );

	// Label to link the input with the label.
	const [ labelFor, setLabelFor ] = useState< string >( '' );

	useEffect( () => {
		if ( ! comboRef?.current ) {
			return;
		}

		/*
		 * Hack to set the base control ID,
		 * to link the label with the input,
		 * picking the input ID from the ComboboxControl.
		 */
		const inputElement = comboRef.current.querySelector(
			'input.components-combobox-control__input'
		);

		const id = inputElement?.getAttribute( 'id' );
		if ( inputElement && typeof id === 'string' ) {
			setLabelFor( id );
		}

		/*
		 * Hack to handle AttributesComboboxControl instances z index,
		 * avoiding to overlap the dropdown instances list.
		 * Todo: this is a temporary/not-ideal solution.
		 * It should be handled by the core ComboboxControl component.
		 */
		const listContainerElement = comboRef.current.querySelector(
			'.components-combobox-control__suggestions-container'
		) as HTMLElement;
		const style = { zIndex: 1000 - instanceNumber };

		if ( listContainerElement ) {
			Object.assign( listContainerElement.style, style );
		}
	}, [ instanceNumber ] );

	if ( ! help ) {
		help = ! attributeSelected ? (
			<div className="woocommerce-attribute-control-help">
				{ __(
					'Select an attribute or type to create.',
					'woocommerce'
				) }
			</div>
		) : null;

		if ( isLoading ) {
			help = (
				<div className="woocommerce-attribute-control-help">
					<Spinner />
					{ __( 'Loading…', 'woocommerce' ) }
				</div>
			);
		} else if ( ! items.length ) {
			help = (
				<div className="woocommerce-attribute-control-help">
					{ __(
						'No attributes yet. Type to create.',
						'woocommerce'
					) }
				</div>
			);
		}
	}

	return (
		<div
			className={ classnames( 'woocommerce-attribute-control-container', {
				'no-items': ! options.length,
			} ) }
			ref={ comboRef }
		>
			<BaseControl label={ label } help={ help } id={ labelFor }>
				<ComboboxControl
					className="woocommerce-attribute-control"
					allowReset={ false }
					options={ options }
					value={ currentValue }
					onChange={ ( newValue ) => {
						if ( ! newValue ) {
							return;
						}

						if ( newValue === 'create-attribute' ) {
							updateCreateNewAttributeItem( {
								...createNewAttributeItem,
								state: 'creating',
							} );
							addNewAttribute( createNewAttributeItem.label );
							return;
						}

						setAttributeSelected( true );

						const selectedAttribute = items?.find(
							( item ) =>
								item.id ===
								Number( newValue.replace( 'attr-', '' ) )
						);

						/*
						 * Do not select when it is disabled.
						 * `disabled` item option should be
						 * handled by the core ComboboxControl component.
						 */
						if (
							! selectedAttribute ||
							selectedAttribute.isDisabled
						) {
							return;
						}

						onChange( selectedAttribute );
					} }
					onFilterValueChange={ ( filterValue: string ) => {
						updateCreateNewAttributeItem( {
							label: filterValue,
							value: 'create-attribute',
							state: 'draft',
						} );
					} }
					__experimentalRenderItem={ ComboboxControlItem }
				/>
			</BaseControl>
		</div>
	);
};

export default AttributesComboboxControl;
