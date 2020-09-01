/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { SelectControl as Select, Spinner } from '@wordpress/components';
import { partial } from 'lodash';
import interpolateComponents from 'interpolate-components';
import classnames from 'classnames';
import { Fragment, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SelectControl from '../select-control';
import { textContent } from './utils';

const getScreenReaderText = ( {
	attributes,
	attributeTerms,
	config,
	filter,
	selectedAttribute,
	selectedAttributeTerm,
} ) => {
	if (
		! attributes ||
		attributes.length === 0 ||
		! attributeTerms ||
		attributeTerms.length === 0 ||
		selectedAttribute === '' ||
		selectedAttributeTerm === ''
	) {
		return '';
	}

	const rule = Array.isArray( config.rules )
		? config.rules.find(
				( configRule ) => configRule.value === filter.rule
		  ) || {}
		: {};

	const { label: attributeName } =
		attributes.find( ( attr ) => attr.key === selectedAttribute ) || {};
	const { label: attributeTerm } =
		attributeTerms.find( ( term ) => term.key === selectedAttributeTerm ) ||
		{};

	if ( ! attributeName || ! attributeTerm ) {
		return '';
	}

	const filterStr = interpolateComponents( {
		/* eslint-disable-next-line max-len */
		/* translators: Sentence fragment describing a product attribute match. Example: "Color Is Not Blue" - attribute = Color, equals = Is Not, value = Blue */
		mixedString: __(
			'{{attribute /}} {{equals /}} {{value /}}',
			'woocommerce-admin'
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

	const [ attributes, setAttributes ] = useState( [] );

	// Fetch all product attributes on mount.
	useEffect( () => {
		apiFetch( {
			path: '/wc/v3/products/attributes',
		} )
			.then( ( attrs ) =>
				attrs.map( ( { id, name } ) => ( {
					key: id.toString(),
					label: name,
				} ) )
			)
			.then( setAttributes );
	}, [] );

	const [ selectedAttribute, setSelectedAttribute ] = useState(
		Array.isArray( value ) ? value[ 0 ] : ''
	);

	// Set selected attribute from filter value (in query string).
	useEffect( () => {
		if ( Array.isArray( value ) ) {
			setSelectedAttribute( value[ 0 ] );
		}
	}, [ value ] );

	const [ attributeTerms, setAttributeTerms ] = useState( false );

	// Fetch all product attributes on mount.
	useEffect( () => {
		if ( ! selectedAttribute ) {
			return;
		}
		setAttributeTerms( false );
		apiFetch( {
			path: `/wc/v3/products/attributes/${ selectedAttribute }/terms`,
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
		attributes,
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
								onChange={ partial( onFilterChange, 'rule' ) }
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
								{ attributes.length > 0 ? (
									<SelectControl
										className="woocommerce-filters-advanced__input woocommerce-search"
										label={ __(
											'Attribute name',
											'woocommerce-admin'
										) }
										isSearchable
										showAllOnFocus
										options={ attributes }
										selected={ selectedAttribute }
										onChange={ ( attr ) => {
											// Clearing the input using delete/backspace causes an empty array to be passed here.
											if ( typeof attr !== 'string' ) {
												attr = '';
											}
											setSelectedAttribute( attr );
											setSelectedAttributeTerm( '' );
											onFilterChange( 'value', [ attr ] );
										} }
									/>
								) : (
									<Spinner />
								) }
								{ attributes.length > 0 &&
									selectedAttribute !== '' &&
									( attributeTerms !== false ? (
										<Fragment>
											<span className="woocommerce-filters-advanced__attribute-field-separator">
												=
											</span>
											<SelectControl
												className="woocommerce-filters-advanced__input woocommerce-search"
												label={ __(
													'Attribute value',
													'woocommerce-admin'
												) }
												isSearchable
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
													onFilterChange( 'value', [
														selectedAttribute,
														term,
													] );
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
