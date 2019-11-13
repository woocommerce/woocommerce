/**
 * External dependencies
 */
import {
	useCollection,
	useQueryStateByKey,
	useQueryStateByContext,
} from '@woocommerce/base-hooks';
import {
	useCallback,
	Fragment,
	useEffect,
	useState,
	useMemo,
} from '@wordpress/element';
import { sortBy } from 'lodash';
import CheckboxList from '@woocommerce/base-components/checkbox-list';

/**
 * Internal dependencies
 */
import './style.scss';
import { getTaxonomyFromAttributeId } from '../../utils/attributes';

/**
 * Component displaying an attribute filter.
 */
const AttributeFilterBlock = ( { attributes, isPreview = false } ) => {
	const [ options, setOptions ] = useState( [] );
	const [ checkedOptions, setCheckedOptions ] = useState( [] );
	const { showCounts, attributeId, queryType } = attributes;
	const taxonomy = getTaxonomyFromAttributeId( attributeId );

	const [ queryState ] = useQueryStateByContext( 'product-grid' );
	const [ productAttributes, setProductAttributes ] = useQueryStateByKey(
		'product-grid',
		'attributes',
		[]
	);

	const filteredCountsQueryState = useMemo( () => {
		// If doing an "AND" query, we need to remove current taxonomy query so counts are not affected.
		const modifiedQueryState =
			queryType === 'or'
				? productAttributes.filter(
						( item ) => item.attribute !== taxonomy
				  )
				: productAttributes;

		// Take current query and remove paging args.
		return {
			...queryState,
			orderby: undefined,
			order: undefined,
			per_page: undefined,
			page: undefined,
			attributes: modifiedQueryState,
			calculate_attribute_counts: [ taxonomy ],
		};
	}, [ queryState, taxonomy, queryType, productAttributes ] );

	const {
		results: attributeTerms,
		isLoading: attributeTermsLoading,
	} = useCollection( {
		namespace: '/wc/store',
		resourceName: 'products/attributes/terms',
		resourceValues: [ attributeId ],
	} );

	const {
		results: filteredCounts,
		isLoading: filteredCountsLoading,
	} = useCollection( {
		namespace: '/wc/store',
		resourceName: 'products/collection-data',
		query: filteredCountsQueryState,
	} );

	const getLabel = useCallback(
		( name, count ) => {
			return (
				<Fragment key="label">
					{ name }
					{ showCounts && (
						<span className="wc-block-attribute-filter-list-count">
							{ count }
						</span>
					) }
				</Fragment>
			);
		},
		[ showCounts ]
	);

	const getFilteredTerm = useCallback(
		( id ) => {
			if ( ! filteredCounts.attribute_counts ) {
				return {};
			}
			return filteredCounts.attribute_counts.find(
				( { term } ) => term === id
			);
		},
		[ filteredCounts ]
	);

	/**
	 * Compare intersection of all terms and filtered counts to get a list of options to display.
	 */
	useEffect( () => {
		// Do nothing until we have the attribute terms from the API.
		if ( attributeTermsLoading || filteredCountsLoading ) {
			return;
		}

		const newOptions = [];

		attributeTerms.forEach( ( term ) => {
			const filteredTerm = getFilteredTerm( term.id );
			const isChecked = checkedOptions.includes( term.slug );
			const inCollection = !! filteredTerm;

			// If there is no match this term doesn't match the current product collection - only render if checked.
			if ( ! inCollection && ! isChecked ) {
				return;
			}

			const filteredCount = filteredTerm
				? filteredTerm.count
				: term.count;
			const count = ! inCollection && isChecked ? 0 : filteredCount;

			newOptions.push( {
				key: term.slug,
				label: getLabel( term.name, count ),
			} );
		} );

		setOptions( newOptions );
	}, [
		filteredCountsLoading,
		attributeTerms,
		attributeTermsLoading,
		getFilteredTerm,
		getLabel,
		checkedOptions,
	] );

	useEffect( () => {
		const newProductAttributes = productAttributes.filter(
			( item ) => item.attribute !== taxonomy
		);

		if ( checkedOptions ) {
			const updatedQuery = {
				attribute: taxonomy,
				operator: queryType === 'or' ? 'in' : 'and',
				slug: checkedOptions,
			};
			newProductAttributes.push( updatedQuery );
		}

		setProductAttributes( sortBy( newProductAttributes, 'attribute' ) );
	}, [ checkedOptions, taxonomy, productAttributes, queryType ] );

	const onChange = useCallback( ( checked ) => {
		setCheckedOptions( checked );
	}, [] );

	if ( ! taxonomy || ( options.length === 0 && ! attributeTermsLoading ) ) {
		return null;
	}

	const TagName = `h${ attributes.headingLevel }`;

	return (
		<Fragment>
			{ ! isPreview && attributes.heading && (
				<TagName>{ attributes.heading }</TagName>
			) }
			<div className="wc-block-attribute-filter">
				<CheckboxList
					className={ 'wc-block-attribute-filter-list' }
					options={ options }
					onChange={ onChange }
					isLoading={ attributeTermsLoading }
				/>
			</div>
		</Fragment>
	);
};

export default AttributeFilterBlock;
