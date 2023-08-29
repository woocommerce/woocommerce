/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl } from '@wordpress/components';
import {
	useState,
	useEffect,
	createElement,
	createInterpolateElement,
	useCallback,
} from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import {
	__experimentalSelectTreeControl as SelectTree,
	TreeItemType as Item,
} from '@woocommerce/components';
import { useDebounce } from '@wordpress/compose';
/**
 * Internal dependencies
 */
import { Taxonomy } from './types';
import useTaxonomySearch from './use-taxonomy-search';

type CreateTaxonomyModalProps = {
	initialName?: string;
	taxonomyName: string;
	title: string;
	onCancel: () => void;
	onCreate: ( brand: Taxonomy ) => void;
};

export const CreateTaxonomyModal: React.FC< CreateTaxonomyModalProps > = ( {
	onCancel,
	onCreate,
	initialName,
	taxonomyName,
	title,
} ) => {
	const [ categoryParentTypedValue, setCategoryParentTypedValue ] =
		useState( '' );
	const [ allEntries, setAllEntries ] = useState< Taxonomy[] >( [] );

	const { searchEntity, isResolving } = useTaxonomySearch( taxonomyName, {
		fetchParents: false,
	} );

	const searchDelayed = useDebounce(
		useCallback(
			( val ) => searchEntity( val || '' ).then( setAllEntries ),
			[]
		),
		150
	);

	useEffect( () => {
		searchDelayed( '' );
	}, [] );

	const { createNotice } = useDispatch( 'core/notices' );
	const { saveEntityRecord } = useDispatch( 'core' );
	const [ isCreating, setIsCreating ] = useState( false );
	const [ name, setName ] = useState( initialName || '' );
	const [ parent, setParent ] = useState< Taxonomy | null >( null );

	const onSave = async () => {
		try {
			const newBrand: Taxonomy = await saveEntityRecord(
				'taxonomy',
				taxonomyName,
				{
					name,
					parent: parent ? parent.id : 0,
				},
				{
					throwOnError: true,
				}
			);
			onCreate( newBrand );
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
		} catch ( e: any ) {
			if ( e.message ) {
				createNotice( 'error', e.message );
			} else {
				createNotice(
					'error',
					__( `Failed to create taxonomy`, 'woocommerce' )
				);
			}
			setIsCreating( false );
			onCancel();
		}
	};

	return (
		<Modal
			title={ title }
			onRequestClose={ onCancel }
			className="woocommerce-create-new-brand-modal"
		>
			<div className="woocommerce-create-new-brand-modal__wrapper">
				<TextControl
					label={ __( 'Name', 'woocommerce' ) }
					name="Tops"
					value={ name }
					onChange={ setName }
				/>
				<SelectTree
					isLoading={ isResolving }
					label={ createInterpolateElement(
						__( 'Parent <optional/>', 'woocommerce' ),
						{
							optional: (
								<span className="woocommerce-product-form__optional-input">
									{ __( '(optional)', 'woocommerce' ) }
								</span>
							),
						}
					) }
					id="parent-brand-field"
					items={ allEntries.map( ( brand ) => ( {
						label: brand.name,
						value: String( brand.id ),
					} ) ) }
					shouldNotRecursivelySelect
					selected={
						parent
							? {
									value: String( parent.id ),
									label: parent.name,
							  }
							: undefined
					}
					onSelect={ ( item: Item ) =>
						item &&
						setParent( {
							id: +item.value,
							name: item.label,
							parent: item.parent ? +item.parent : 0,
						} )
					}
					onRemove={ () => setParent( null ) }
					onInputChange={ ( value ) => {
						searchDelayed( value );
						setCategoryParentTypedValue( value || '' );
					} }
					createValue={ categoryParentTypedValue }
				/>
				<div className="woocommerce-create-new-brand-modal__buttons">
					<Button
						isSecondary
						onClick={ onCancel }
						disabled={ isCreating }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						isPrimary
						disabled={ name.length === 0 || isCreating }
						isBusy={ isCreating }
						onClick={ onSave }
					>
						{ __( 'Save', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
};
