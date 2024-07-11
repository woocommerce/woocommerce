/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BaseControl, Button, Modal, TextControl } from '@wordpress/components';
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
import { useDebounce, useInstanceId } from '@wordpress/compose';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import type { Taxonomy } from '../../../types';
import useTaxonomySearch from './use-taxonomy-search';

type CreateTaxonomyModalProps = {
	initialName?: string;
	dialogNameHelpText?: string;
	parentTaxonomyText?: string;
	hierarchical: boolean;
	slug: string;
	title: string;
	onCancel: () => void;
	onCreate: ( taxonomy: Taxonomy ) => void;
};

export const CreateTaxonomyModal: React.FC< CreateTaxonomyModalProps > = ( {
	onCancel,
	onCreate,
	initialName,
	slug,
	hierarchical,
	dialogNameHelpText,
	parentTaxonomyText,
	title,
} ) => {
	const [ categoryParentTypedValue, setCategoryParentTypedValue ] =
		useState( '' );
	const [ allEntries, setAllEntries ] = useState< Taxonomy[] >( [] );

	const { searchEntity, isResolving } = useTaxonomySearch( slug );

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

	const { saveEntityRecord } = useDispatch( 'core' );
	const [ isCreating, setIsCreating ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState< string | null >( null );
	const [ name, setName ] = useState( initialName || '' );
	const [ parent, setParent ] = useState< Taxonomy | null >( null );

	const onSave = async () => {
		setErrorMessage( null );
		setIsCreating( true );
		try {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			const newTaxonomy: Taxonomy = await saveEntityRecord(
				'taxonomy',
				slug,
				{
					name,
					parent: parent ? parent.id : null,
				},
				// eslint-disable-next-line @typescript-eslint/ban-ts-comment
				// @ts-ignore
				{
					throwOnError: true,
				}
			);
			setIsCreating( false );
			onCreate( newTaxonomy );
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
		} catch ( e: any ) {
			setIsCreating( false );
			if ( e.message ) {
				setErrorMessage( e.message );
			} else {
				setErrorMessage(
					__( `Failed to create taxonomy`, 'woocommerce' )
				);
				throw e;
			}
		}
	};

	const id = useInstanceId( BaseControl, 'taxonomy_name' ) as string;

	const selectId = useInstanceId(
		SelectTree,
		'parent-taxonomy-select'
	) as string;

	return (
		<Modal
			title={ title }
			onRequestClose={ onCancel }
			className="woocommerce-create-new-taxonomy-modal"
		>
			<div className="woocommerce-create-new-taxonomy-modal__wrapper">
				<BaseControl
					id={ id }
					label={ __( 'Name', 'woocommerce' ) }
					help={ errorMessage || dialogNameHelpText }
					className={ classNames( {
						'has-error': errorMessage,
					} ) }
				>
					<TextControl
						id={ id }
						value={ name }
						onChange={ setName }
					/>
				</BaseControl>
				{ hierarchical && (
					<SelectTree
						isLoading={ isResolving }
						label={ createInterpolateElement(
							`${
								parentTaxonomyText ||
								__( 'Parent', 'woocommerce' )
							} <optional/>`,
							{
								optional: (
									<span className="woocommerce-create-new-taxonomy-modal__optional">
										{ __( '(optional)', 'woocommerce' ) }
									</span>
								),
							}
						) }
						id={ selectId }
						items={ allEntries.map( ( taxonomy ) => ( {
							label: taxonomy.name,
							value: String( taxonomy.id ),
							parent:
								taxonomy.parent > 0
									? String( taxonomy.parent )
									: undefined,
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
				) }
				<div className="woocommerce-create-new-taxonomy-modal__buttons">
					<Button
						variant="tertiary"
						onClick={ onCancel }
						disabled={ isCreating }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						variant="primary"
						disabled={ name.length === 0 || isCreating }
						isBusy={ isCreating }
						onClick={ onSave }
					>
						{ __( 'Create', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
};
