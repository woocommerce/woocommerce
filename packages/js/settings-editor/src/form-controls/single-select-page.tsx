/**
 * External dependencies
 */
import { Button, SelectControl, Spinner } from '@wordpress/components';
import { createElement, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore, Page } from '@wordpress/core-data';
import { close } from '@wordpress/icons';
import type { DataFormControlProps } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { DataFormItem } from '../types';
import { sanitizeHTML } from '../utils';

// https://github.com/woocommerce/woocommerce/blob/83a090f70d1f7b07325d9df9bd03fe2f753d4fd4/plugins/woocommerce/includes/admin/class-wc-admin-settings.php#L626-L636
const PAGE_QUERY_ARGS = {
	orderby: 'menu_order',
	order: 'asc',
	status: [ 'publish', 'private', 'draft' ],
	per_page: -1,
	_fields: [ 'id', 'title' ],
};

const usePages = () => {
	return useSelect( ( select ) => {
		const { getEntityRecords, hasFinishedResolution } =
			select( coreDataStore );

		const args: [ string, string, typeof PAGE_QUERY_ARGS ] = [
			'postType',
			'page',
			PAGE_QUERY_ARGS,
		];

		return {
			pages: getEntityRecords( ...args ) as Page[],
			isLoading: ! hasFinishedResolution( 'getEntityRecords', args ),
		};
	}, [] );
};

type SingleSelectPageEditProps = DataFormControlProps< DataFormItem > & {
	help?: React.ReactNode;
	className?: string;
};

export const SingleSelectPage = ( {
	data,
	field,
	onChange,
	hideLabelFromVision,
	help,
	className,
}: SingleSelectPageEditProps ) => {
	const { pages, isLoading } = usePages();
	const { id } = field;

	// DataForm will automatically use the id as the label if no label is provided so we conditionally set the label to undefined if it matches the id to avoid displaying it.
	// We should contribute upstream to allow label to be optional.
	const label =
		field.label === id ? undefined : (
			<div
				dangerouslySetInnerHTML={ {
					__html: sanitizeHTML( field.label ),
				} }
			/>
		);

	const value = field.getValue( { item: data } ) ?? '';

	const onChangeControl = useCallback(
		( newValue: string ) =>
			onChange( {
				[ id ]: newValue,
			} ),
		[ id, onChange ]
	);

	const options = useMemo(
		() => [
			{
				label: isLoading
					? __( 'Loading…', 'woocommerce' )
					: __( 'Select a page…', 'woocommerce' ),
				value: '',
				disabled: true,
			},
			...( pages ?? [] ).map( ( page ) => ( {
				value: page.id.toString(),
				label: page.title.rendered,
			} ) ),
		],
		[ isLoading, pages ]
	);

	const getSuffix = () => {
		if ( isLoading ) {
			return <Spinner />;
		}

		if ( value ) {
			return (
				<Button
					icon={ close }
					iconSize={ 16 }
					size="compact"
					onClick={ () => onChangeControl( '' ) }
				/>
			);
		}

		return null;
	};

	return (
		<SelectControl
			className={ className }
			suffix={ getSuffix() }
			id={ id }
			label={ label }
			value={ value }
			help={ help }
			options={ options }
			onChange={ onChangeControl }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			hideLabelFromVision={ hideLabelFromVision }
		/>
	);
};
