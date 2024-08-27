// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
/**
 * External dependencies
 */
import { DataForm } from '@wordpress/dataviews';
import { createElement, useState, useMemo } from '@wordpress/element';
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import { privateApis as editorPrivateApis } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import classNames from 'classnames';
import {
	__experimentalHeading as Heading,
	__experimentalText as Text,
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
	FlexItem,
} from '@wordpress/components';

const { NavigableRegion } = unlock( editorPrivateApis );

const STATUSES = [
	{ value: 'draft', label: __( 'Draft' ) },
	{ value: 'future', label: __( 'Scheduled' ) },
	{ value: 'pending', label: __( 'Pending Review' ) },
	{ value: 'private', label: __( 'Private' ) },
	{ value: 'publish', label: __( 'Published' ) },
	{ value: 'trash', label: __( 'Trash' ) },
];

/**
 * TODO: auto convert some of the product editor blocks ( from the blocks directory ) to this format.
 * The edit function should work relatively well with the edit from the blocks, the only difference is that the blocks rely on getEntityProp to get the value
 */
const fields = [
	{
		id: 'title',
		label: 'Title',
		enableHiding: false,
		render: ( { item } ) => {
            if ( item && item.title ) {
			    return item.title.rendered;
            }
		},
	},
	{
		id: 'date',
		label: 'Date',
		render: ( { item } ) => {
			return <time>{ item.date }</time>;
		},
	},
	{
		id: 'author',
		label: __( 'Author' ),
		render: ( { item } ) => {
			return <a href="...">{ item.author }</a>;
		},
		elements: [
			{ value: 1, label: 'Admin' },
			{ value: 2, label: 'User' },
		],
		filterBy: {
			operators: [ 'is', 'isNot' ],
		},
		enableSorting: false,
	},
	{
		label: __( 'Status' ),
		id: 'status',
		getValue: ( { item } ) =>
			STATUSES.find( ( { value } ) => value === item.status )?.label ??
			item.status,
		elements: STATUSES,
		filterBy: {
			operators: [ 'isAny' ],
		},
		enableSorting: false,
	},
];

const form = {
	type: 'panel',
	fields: [ 'title', 'status', 'author' ],
};

export default function ProductEdit( { 
	subTitle,
	actions,
	className,
	hideTitleFromUI = false,
    postType, postId } ) {
	const classes = classNames( 'edit-site-page', className );
    const ids = useMemo( () => postId.split( ',' ), [ postId ] );
	const { initialEdits } = useSelect(
		( select ) => {
			if ( ids.length !== 1 ) {
			}
			return {
				initialEdits:
					ids.length === 1
						? select( 'core' ).getEntityRecord(
								'postType',
								postType,
								ids[ 0 ]
						  )
						: null,
			};
		},
		[ postType, ids ]
	);
    const [ edits, setEdits ] = useState( {} );
	const itemWithEdits = useMemo( () => {
		return {
			...initialEdits,
			...edits,
		};
	}, [ initialEdits, edits ] );

	return (
		<NavigableRegion className={ classes } ariaLabel={ __( 'Products', 'woocommerce') }>
			<div className="edit-site-page-content">
				{ ! hideTitleFromUI  && (
					<VStack className="edit-site-page-header" as="header" spacing={ 0 }>
					<HStack className="edit-site-page-header__page-title">
						<Heading
							as="h2"
							level={ 3 }
							weight={ 500 }
							className="edit-site-page-header__title"
							truncate
						>
							{ __( 'Products', 'woocommerce') }
						</Heading>
						<FlexItem className="edit-site-page-header__actions">
							{ actions }
						</FlexItem>
					</HStack>
					{ subTitle && (
						<Text
							variant="muted"
							as="p"
							className="edit-site-page-header__sub-title"
						>
							{ subTitle }
						</Text>
					) }
				</VStack>
				) }
			<VStack spacing={ 4 }>
				<DataForm
					data={ itemWithEdits }
					fields={ fields }
					form={ form }
					onChange={ setEdits }
				/>
			</VStack>
		</NavigableRegion>
	);
}
