/**
 * External dependencies
 */
import {
	useEffect,
	useMemo,
	useState,
	useRef,
	forwardRef,
} from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { Field, Input, InputLayout, Popover } from '@wordpress/ui';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import useIsEqualRefValue from './useIsEqualRefValue';
import Tags from './tags';
import Options from './options';
import {
	ARROW_DOWN,
	ARROW_UP,
	ENTER,
	ESCAPE,
	ROOT_VALUE,
	BACKSPACE,
} from './constants';
import type {
	TreeSelectControlProps,
	Option,
	InnerOption,
	TagItem,
	OptionPropertyDescriptors,
} from './types';

/**
 * Renders a component with a searchable control, tags and a tree selector.
 */
export const TreeSelectControl = forwardRef<
	HTMLDivElement,
	TreeSelectControlProps
>( function TreeSelectControl(
	{
		id,
		label,
		selectAllLabel = __( 'All', 'woocommerce' ),
		description,
		placeholder,
		className,
		disabled,
		options = [],
		value = [],
		maxVisibleTags,
		onValueChange = () => {},
		onDropdownVisibilityChange = () => {},
		onInputChange = () => {},
		includeParent = false,
		individuallySelectParent = false,
		alwaysShowPlaceholder = false,
		minFilterQueryLength = 3,
		clearOnSelect = true,
	},
	ref
) {
	const instanceId = useInstanceId( TreeSelectControl );
	const resolvedId = id ?? instanceId;

	const [ treeVisible, setTreeVisible ] = useState< boolean >( false );
	const [ nodesExpanded, setNodesExpanded ] = useState< string[] >( [] );
	const [ inputControlValue, setInputControlValue ] =
		useState< string >( '' );

	const controlRef = useRef< HTMLInputElement >( null );
	const inputLayoutRef = useRef< HTMLDivElement >( null );
	const dropdownRef = useRef< HTMLDivElement >( null );
	const onDropdownVisibilityChangeRef =
		useRef< ( visible: boolean ) => void >();
	onDropdownVisibilityChangeRef.current = onDropdownVisibilityChange;

	// We will save in a REF previous search filter queries to avoid re-query the tree and save performance
	const cacheRef = useRef< {
		filteredOptionsMap: Map< string, InnerOption[] >;
		expandedValues?: string[];
		selectedValues?: string[];
	} >( { filteredOptionsMap: new Map() } );
	cacheRef.current.expandedValues = nodesExpanded;
	cacheRef.current.selectedValues = value;

	const showTree = ! disabled && treeVisible;

	const root: Option | null =
		selectAllLabel !== false
			? {
					label: selectAllLabel,
					value: ROOT_VALUE,
					children: options,
			  }
			: null;

	const treeOptions: Option[] = useIsEqualRefValue(
		root ? [ root ] : options
	);

	const handlePopoverOpenChange = ( open: boolean ) => {
		if ( ! open ) {
			setTreeVisible( false );
		}
	};

	const filterQuery: string = inputControlValue.trim().toLowerCase();
	// we only trigger the filter when there are more than 3 characters in the input.
	const filter: string =
		filterQuery.length >= minFilterQueryLength ? filterQuery : '';

	/**
	 * Optimizes the performance for getting the tags info
	 */
	const optionsRepository: Record< string, Option > = useMemo( () => {
		const repository: Record< string, Option > = {};
		cacheRef.current.filteredOptionsMap.clear();

		function loadOption( option: Option, parent?: string ) {
			option = { ...option, parent } as Option & { parent?: string };
			option.children?.forEach( ( el ) =>
				loadOption( el, option.value )
			);

			repository[ option.key ?? option.value ] = option;
		}

		treeOptions.forEach( ( option ) => loadOption( option ) );

		return repository;
	}, [ treeOptions ] );

	/*
	 * Perform the search query filter in the Tree options
	 *
	 * 1. Check if the search query is already cached and return it if so.
	 * 2. Deep copy the tree with adding properties for rendering.
	 * 3. In case of filter, we apply the filter option function to the tree.
	 * 4. In the filter function we also highlight the label with the matching letters
	 * 5. Finally we set the cache with the obtained results and apply the filters
	 */
	const filteredOptions: InnerOption[] = useMemo( () => {
		const { current: cache } = cacheRef;
		const cachedFilteredOptions = cache.filteredOptionsMap.get( filter );

		if ( cachedFilteredOptions ) {
			return cachedFilteredOptions;
		}

		const isSearching = Boolean( filter );

		const highlightOptionLabel = (
			optionLabel: string,
			matchPosition: number
		) => {
			const matchLength: number = matchPosition + filter.length;

			if ( ! isSearching ) {
				return optionLabel;
			}

			return (
				<span>
					<span>{ optionLabel.substring( 0, matchPosition ) }</span>
					<strong>
						{ optionLabel.substring( matchPosition, matchLength ) }
					</strong>
					<span>{ optionLabel.substring( matchLength ) }</span>
				</span>
			);
		};

		const descriptors: OptionPropertyDescriptors = {
			hasChildren: {
				/**
				 * Returns whether this option has children.
				 *
				 * @return {boolean} True if has children, false otherwise.
				 */
				get() {
					return Boolean( this.children?.length );
				},
			},
			leaves: {
				/**
				 * Return all leaf options flattened under this option. The options are filtered if in searching.
				 *
				 * @return {InnerOption[]} All leaf options that are flattened under this option. The options are filtered if in searching.
				 */
				get() {
					if ( ! this.hasChildren ) {
						return [];
					}
					return ( this.children ?? [] ).flatMap( ( option ) => {
						if ( option.hasChildren ) {
							return includeParent && option.value !== ROOT_VALUE
								? [ option, ...option.leaves ]
								: option.leaves;
						}
						return option;
					} );
				},
			},
			checked: {
				/**
				 * Returns whether this option is checked.
				 * A leaf option is checked if its value is selected.
				 * A parent option is checked if all leaves are checked.
				 *
				 * @return {boolean} True if checked, false otherwise.
				 */
				get() {
					if (
						( includeParent && this.value !== ROOT_VALUE ) ||
						individuallySelectParent
					) {
						return (
							cache.selectedValues?.includes( this.value ) ??
							false
						);
					}
					if ( this.hasChildren ) {
						return this.leaves.every( ( opt ) => opt.checked );
					}
					return (
						cache.selectedValues?.includes( this.value ) ?? false
					);
				},
			},
			partialChecked: {
				/**
				 * Returns whether this option is partially checked.
				 * A leaf option always returns false.
				 * A parent option is partially checked if at least one but not all leaves are checked.
				 *
				 * @return {boolean} True if partially checked, false otherwise.
				 */
				get() {
					if ( ! this.hasChildren ) {
						return false;
					}
					return (
						! this.checked &&
						( this.children ?? [] ).some(
							( opt ) => opt.checked || opt.partialChecked
						)
					);
				},
			},
			isVisible: {
				/**
				 * Returns whether this option should be visible based on search.
				 * All options are visible when not searching. Otherwise, true if this option is
				 * a search result or it has a descendent that is being searched for.
				 *
				 * @return {boolean} True if option should be visible, false otherwise.
				 */
				get() {
					// everything is visible when not searching.
					if ( ! isSearching ) {
						return true;
					}

					// Exit true if this is searched result.
					if ( this.isSearchResult ) {
						return true;
					}

					// If any children are search results, remain visible.
					if ( this.hasChildren ) {
						return ( this.children ?? [] ).some(
							( opt ) => opt.isVisible
						);
					}

					return this.leaves.some( ( opt ) => opt.isSearchResult );
				},
			},
			isSearchResult: {
				/**
				 * Returns whether this option is a searched result.
				 *
				 * @return {boolean} True if option is being searched, false otherwise.
				 */
				get() {
					if ( ! isSearching ) {
						return false;
					}
					return !! this.filterMatch;
				},
			},
			expanded: {
				/**
				 * Returns whether this option is expanded.
				 * A leaf option always returns false.
				 *
				 * @return {boolean} True if expanded, false otherwise.
				 */
				get() {
					return (
						( isSearching && this.isVisible ) ||
						this.value === ROOT_VALUE ||
						( cache.expandedValues?.includes( this.value ) ??
							false )
					);
				},
			},
		};

		/**
		 * Decompose accented characters into their composable parts, then remove accents.
		 * See https://www.unicode.org/reports/tr15/ and https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/normalize.
		 */
		const removeAccents = ( str: string ): string => {
			return str.normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' );
		};

		const reduceOptions = (
			acc: InnerOption[],
			{ children = [], ...option }: Option
		): InnerOption[] => {
			const mutableOption: InnerOption = {
				...option,
			} as InnerOption;

			if ( children.length ) {
				mutableOption.children = children.reduce( reduceOptions, [] );
			}

			if ( isSearching ) {
				const originalLabel = option.label; // Use original string label
				const labelWithAccentsRemoved = removeAccents( originalLabel );
				const filterWithAccentsRemoved = removeAccents( filter );
				const match = labelWithAccentsRemoved
					.toLowerCase()
					.indexOf( filterWithAccentsRemoved );
				if ( match > -1 ) {
					mutableOption.label = highlightOptionLabel(
						originalLabel,
						match
					);
					mutableOption.filterMatch = true;
				}
			}

			Object.defineProperties( mutableOption, descriptors );
			acc.push( mutableOption );

			return acc;
		};

		const filteredTreeOptions: InnerOption[] = treeOptions.reduce(
			reduceOptions,
			[]
		);
		cache.filteredOptionsMap.set( filter, filteredTreeOptions );

		return filteredTreeOptions;
	}, [ filter, treeOptions, includeParent, individuallySelectParent ] );

	/**
	 * Handle key down events in the component
	 *
	 * Keys:
	 * If key down is ESCAPE. Collapse the tree
	 * If key down is ENTER. Expand the tree
	 * If key down is ARROW_UP. Navigate up to the previous option
	 * If key down is ARROW_DOWN. Navigate down to the next option
	 * If key down is ARROW_DOWN. Navigate down to the next option
	 */
	const onKeyDown = ( event: React.KeyboardEvent ) => {
		if ( disabled ) {
			return;
		}

		if ( ESCAPE === event.key ) {
			if ( showTree ) {
				event.stopPropagation();
			}
			setTreeVisible( false );
		}

		if ( ENTER === event.key ) {
			setTreeVisible( true );

			if (
				event.target instanceof HTMLInputElement &&
				event.target.type === 'checkbox'
			) {
				event.target.click();
			}

			event.preventDefault();
		}

		const stepDict: Record< string, number > = {
			[ ARROW_UP ]: -1,
			[ ARROW_DOWN ]: 1,
		};
		const step = stepDict[ event.key ];

		if (
			step &&
			dropdownRef.current &&
			filteredOptions.length &&
			event.target instanceof HTMLElement
		) {
			const elements = Array.from(
				dropdownRef.current.querySelectorAll< HTMLElement >(
					'[role="checkbox"]'
				)
			);
			const currentIndex = elements.indexOf( event.target );
			const index = Math.max( currentIndex + step, -1 ) % elements.length;
			elements.at( index )?.focus();
			event.preventDefault();
		}
	};

	useEffect( () => {
		onDropdownVisibilityChangeRef.current?.( showTree );
	}, [ showTree ] );

	/**
	 * Get formatted Tags from the selected values.
	 */
	const tags: TagItem[] = useMemo( () => {
		if ( ! options.length ) {
			return [];
		}

		return value.map( ( key ): TagItem => {
			const option = optionsRepository[ key ];
			return { id: String( key ), label: option?.label };
		} );
	}, [ optionsRepository, value, options ] );

	/**
	 * Handle click event on the option expander
	 */
	const handleExpanderClick = ( e: React.MouseEvent ) => {
		if ( ! dropdownRef.current ) {
			return;
		}
		const elements = Array.from(
			dropdownRef.current.querySelectorAll< HTMLElement >(
				'button, [role="checkbox"]'
			)
		);
		const index =
			e.currentTarget instanceof HTMLElement
				? elements.indexOf( e.currentTarget ) + 1
				: 0;
		elements[ index ]?.focus();
	};

	/**
	 * Expands/Collapses the Option
	 */
	const handleToggleExpanded = ( option: InnerOption ) => {
		setNodesExpanded(
			option.expanded
				? nodesExpanded.filter( ( el ) => option.value !== el )
				: [ ...nodesExpanded, option.value ]
		);
	};

	/**
	 * Handles a change of a child element.
	 */
	const handleSingleChange = (
		checked: boolean,
		option: InnerOption,
		parent: InnerOption | null
	) => {
		const newValue: string[] = checked
			? [ ...value, option.value ]
			: value.filter( ( el ) => el !== option.value );
		if (
			includeParent &&
			parent &&
			parent.value !== ROOT_VALUE &&
			parent.children &&
			parent.children.every( ( child ) =>
				newValue.includes( child.value )
			) &&
			! newValue.includes( parent.value )
		) {
			newValue.push( parent.value );
		}
		onValueChange( newValue );
	};

	/**
	 * Handles a change of a Parent element.
	 */
	const handleParentChange = ( checked: boolean, option: InnerOption ) => {
		let newValue: string[];
		const changedValues: string[] = individuallySelectParent
			? [ option.value ]
			: option.leaves
					.filter( ( opt ) => opt.checked !== checked )
					.map( ( opt ) => opt.value );
		/**
		 * If includeParent is true, we need to add the parent value to the array of
		 * changed values. However, if for some reason includeParent AND individuallySelectParent
		 * are both set to true, we want to avoid duplicating the parent value in the array.
		 */
		if (
			includeParent &&
			! individuallySelectParent &&
			option.value !== ROOT_VALUE
		) {
			changedValues.push( option.value );
		}
		if ( checked ) {
			if ( ! option.expanded ) {
				handleToggleExpanded( option );
			}
			newValue = value.concat( changedValues );
		} else {
			newValue = value.filter( ( el ) => ! changedValues.includes( el ) );
		}

		onValueChange( newValue );
	};

	/**
	 * Handles a change on the Tree options. Could be a click on a parent option
	 * or a child option
	 */
	const handleOptionsChange = (
		checked: boolean,
		option: InnerOption,
		parent: InnerOption | null
	) => {
		if ( option.hasChildren ) {
			handleParentChange( checked, option );
		} else {
			handleSingleChange( checked, option, parent );
		}

		if ( clearOnSelect ) {
			onInputChange( '' );
			setInputControlValue( '' );
			if ( option.parent && ! nodesExpanded.includes( option.parent ) ) {
				controlRef.current?.focus();
			}
		}
	};

	/**
	 * Handles a change of a Tag element. We map them to Value format.
	 */
	const handleTagsChange = ( newTags: TagItem[] ) => {
		onValueChange( [ ...newTags.map( ( el ) => el.id ) ] );
	};

	/**
	 * Prepares and sets the search filter.
	 * Filters of less than 3 characters are not considered, so we convert them to ''
	 */
	const handleOnInputChange = (
		e: React.ChangeEvent< HTMLInputElement >
	) => {
		setTreeVisible( true );
		onInputChange( e.target.value );
		setInputControlValue( e.target.value );
	};

	/**
	 * Handles keydown event
	 *
	 * Keys:
	 * When key down is BACKSPACE. Delete the last tag.
	 */
	const handleInputKeydown = (
		event: React.KeyboardEvent< HTMLInputElement >
	) => {
		if ( BACKSPACE === event.key ) {
			if ( inputControlValue ) {
				return;
			}
			handleTagsChange( tags.slice( 0, -1 ) );
			event.preventDefault();
		}
	};

	const hasTags = tags.length > 0;
	const showPlaceholder = alwaysShowPlaceholder
		? true
		: ! hasTags && ! showTree;

	return (
		<Popover.Root
			open={ showTree }
			onOpenChange={ handlePopoverOpenChange }
		>
			{ /* eslint-disable-next-line jsx-a11y/no-static-element-interactions */ }
			<Field.Root
				ref={ ref }
				onKeyDown={ onKeyDown }
				className={ clsx(
					className,
					'woocommerce-tree-select-control'
				) }
			>
				<Field.Label>{ label }</Field.Label>
				<InputLayout
					ref={ inputLayoutRef }
					className="woocommerce-tree-select-control__control"
					visuallyDisabled={ disabled }
				>
					{ hasTags && (
						<Tags
							disabled={ disabled }
							tags={ tags }
							maxVisibleTags={ maxVisibleTags }
							onChange={ handleTagsChange }
							nonRemovableIds={ [ ROOT_VALUE ] }
							onTagClick={ () => setTreeVisible( true ) }
						/>
					) }
					<Input
						ref={ controlRef }
						id={ `tree-select-control-${ resolvedId }__control-input` }
						type="search"
						placeholder={ showPlaceholder ? placeholder : '' }
						autoComplete="off"
						className="woocommerce-tree-select-control__control-input"
						role="combobox"
						aria-autocomplete="list"
						value={ inputControlValue }
						aria-expanded={ showTree }
						disabled={ disabled }
						onFocus={ () => {
							setTreeVisible( true );
						} }
						onChange={ handleOnInputChange }
						onKeyDown={ handleInputKeydown }
					/>
				</InputLayout>
				{ description && (
					<Field.Description>{ description }</Field.Description>
				) }
			</Field.Root>
			{ showTree && (
				<Popover.Popup
					ref={ dropdownRef }
					className="woocommerce-tree-select-control__tree"
					variant="unstyled"
					anchor={ inputLayoutRef }
					align="start"
					sideOffset={ 8 }
					collisionPadding={ 12 }
					initialFocus={ false }
					finalFocus={ false }
					role="tree"
					tabIndex={ -1 }
					aria-multiselectable="true"
					onKeyDown={ onKeyDown }
				>
					<Options
						options={ filteredOptions }
						onChange={ handleOptionsChange }
						onExpanderClick={ handleExpanderClick }
						onToggleExpanded={ handleToggleExpanded }
					/>
				</Popover.Popup>
			) }
		</Popover.Root>
	);
} );
