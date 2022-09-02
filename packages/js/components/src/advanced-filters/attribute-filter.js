/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { SelectControl as Select, Spinner } from '@wordpress/components';
import interpolateComponents from '@automattic/interpolate-components';
import classnames from 'classnames';
import {
	createElement,
	Fragment,
	useEffect,
	useState,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Search from '../search';
import SelectControl from '../select-control';
import { textContent } from './utils';

const getScreenReaderText = ( {
	attributeTerms,
	config,
	filter,
	selectedAttribute,
	selectedAttributeTerm,
} ) => {
	if (
		! attributeTerms ||
		attributeTerms.length === 0 ||
		! selectedAttribute ||
		selectedAttribute.length === 0 ||
		selectedAttributeTerm === ''
	) {
		return '';
	}

	const rule = Array.isArray( config.rules )
		? config.rules.find(
				( configRule ) => configRule.value === filter.rule
		  ) || {}
		: {};

	const attributeName = selectedAttribute[ 0 ].label;
	const termObject = attributeTerms.find(
		( { key } ) => key === selectedAttributeTerm
	);
	const attributeTerm = termObject && termObject.label;

	if ( ! attributeName || ! attributeTerm ) {
		return '';
	}

	const filterStr = interpolateComponents( {
		/* eslint-disable-next-line max-len */
		/* translators: Sentence fragment describing a product attribute match. Example: "Color Is Not Blue" - attribute = Color, equals = Is Not, value = Blue */
		mixedString: __(
			'{{attribute /}} {{equals /}} {{value /}}',
			'woocommerce'
		),
		components: {
			attribute: <Fragment>{ attributeName }</Fragment>,
			equals: <Fragment>{ rule.label }</Fragment>,
			value: <Fragment>{ attributeTerm }</Fragment>,
		},
	} );

	return textContent(
		interpolateComponents( {
			mixedString: config.labels.title,
			components: {
				filter: <Fragment>{ filterStr }</Fragment>,
				rule: <Fragment />,
				title: <Fragment />,
			},
		} )
	);
};

const AttributeFilter = ( props ) => {
	const { className, config, filter, isEnglish, onFilterChange } = props;
	const { rule, value } = filter;
	const { labels, rules } = config;

	const [ selectedAttribute, setSelectedAttribute ] = useState( [] );

	// Set selected attribute from filter value (in query string).
	useEffect( () => {
		if (
			! selectedAttribute.length &&
			Array.isArray( value ) &&
			value[ 0 ]
		) {
			apiFetch( {
				path: `/wc-analytics/products/attributes/${ value[ 0 ] }`,
			} )
				.then( ( { id, name } ) => [
					{
						key: id.toString(),
						label: name,
					},
				] )
				.then( setSelectedAttribute );
		}
	}, [ value, selectedAttribute ] );

	const [ attributeTerms, setAttributeTerms ] = useState( [] );

	// Fetch all product attributes on mount.
	useEffect( () => {
		if ( ! selectedAttribute.length ) {
			return;
		}
		setAttributeTerms( false );
		apiFetch( {
			path: `/wc-analytics/products/attributes/${ selectedAttribute[ 0 ].key }/terms?per_page=100`,
		} )
			.then( ( terms ) =>
				terms.map( ( { id, name } ) => ( {
					key: id.toString(),
					label: name,
				} ) )
			)
			.then( setAttributeTerms );
	}, [ selectedAttribute ] );

	const [ selectedAttributeTerm, setSelectedAttributeTerm ] = useState(
		Array.isArray( value ) ? value[ 1 ] || '' : ''
	);

	const screenReaderText = getScreenReaderText( {
		attributeTerms,
		config,
		filter,
		selectedAttribute,
		selectedAttributeTerm,
	} );

	/*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
	return (
		<fieldset
			className="woocommerce-filters-advanced__line-item"
			tabIndex="0"
		>
			<legend className="screen-reader-text">{ labels.add || '' }</legend>
			<div
				className={ classnames(
					'woocommerce-filters-advanced__fieldset',
					{
						'is-english': isEnglish,
					}
				) }
			>
				{ interpolateComponents( {
					mixedString: labels.title,
					components: {
						title: <span className={ className } />,
						rule: (
							<Select
								className={ classnames(
									className,
									'woocommerce-filters-advanced__rule'
								) }
								options={ rules }
								value={ rule }
								onChange={ ( selectedValue ) =>
									onFilterChange( {
										property: 'rule',
										value: selectedValue,
									} )
								}
								aria-label={ labels.rule }
							/>
						),
						filter: (
							<div
								className={ classnames(
									className,
									'woocommerce-filters-advanced__attribute-fieldset'
								) }
							>
								{ ! Array.isArray( value ) ||
								! value.length ||
								selectedAttribute.length ? (
									<Search
										className="woocommerce-filters-advanced__input woocommerce-search"
										onChange={ ( [ attr ] ) => {
											setSelectedAttribute(
												attr ? [ attr ] : []
											);
											setSelectedAttributeTerm( '' );
											onFilterChange( {
												property: 'value',
												value: [
													attr && attr.key,
												].filter( Boolean ),
											} );
										} }
										type="attributes"
										placeholder={ __(
											'Attribute name',
											'woocommerce'
										) }
										multiple={ false }
										selected={ selectedAttribute }
										inlineTags
										aria-label={ __(
											'Attribute name',
											'woocommerce'
										) }
									/>
								) : (
									<Spinner />
								) }
								{ selectedAttribute.length > 0 &&
									( attributeTerms.length ? (
										<Fragment>
											<span className="woocommerce-filters-advanced__attribute-field-separator">
												=
											</span>
											<SelectControl
												className="woocommerce-filters-advanced__input woocommerce-search"
												placeholder={ __(
													'Attribute value',
													'woocommerce'
												) }
												inlineTags
												isSearchable
												multiple={ false }
												showAllOnFocus
												options={ attributeTerms }
												selected={
													selectedAttributeTerm
												}
												onChange={ ( term ) => {
													// Clearing the input using delete/backspace causes an empty array to be passed here.
													if (
														typeof term !== 'string'
													) {
														term = '';
													}
													setSelectedAttributeTerm(
														term
													);
													onFilterChange( {
														property: 'value',
														value: [
															selectedAttribute[ 0 ]
																.key,
															term,
														].filter( Boolean ),
													} );
												} }
											/>
										</Fragment>
									) : (
										<Spinner />
									) ) }
							</div>
						),
					},
				} ) }
			</div>
			{ screenReaderText && (
				<span className="screen-reader-text">{ screenReaderText }</span>
			) }
		</fieldset>
	);
	/*eslint-enable jsx-a11y/no-noninteractive-tabindex*/
};

AttributeFilter.propTypes = {
	/**
	 * The configuration object for the single filter to be rendered.
	 */
	config: PropTypes.shape( {
		labels: PropTypes.shape( {
			rule: PropTypes.string,
			title: PropTypes.string,
			filter: PropTypes.string,
		} ),
		rules: PropTypes.arrayOf( PropTypes.object ),
		input: PropTypes.object,
	} ).isRequired,
	/**
	 * The activeFilter handed down by AdvancedFilters.
	 */
	filter: PropTypes.shape( {
		key: PropTypes.string,
		rule: PropTypes.string,
		value: PropTypes.arrayOf(
			PropTypes.oneOfType( [ PropTypes.string, PropTypes.number ] )
		),
	} ).isRequired,
	/**
	 * Function to be called on update.
	 */
	onFilterChange: PropTypes.func.isRequired,
};

export default AttributeFilter;
