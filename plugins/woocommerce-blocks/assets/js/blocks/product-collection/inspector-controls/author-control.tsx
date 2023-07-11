/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEntityRecords } from '@wordpress/core-data';
import {
	FormTokenField,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { QueryControlProps } from '../types';

interface Author {
	id: string;
	name: string;
}

interface AuthorsInfo {
	authors: Author[];
	mapById: Map< number, Author >;
	mapByName: Map< string, Author >;
	names: string[];
}

const AUTHORS_QUERY = {
	who: 'authors',
	per_page: -1,
	_fields: 'id,name',
	context: 'view',
};

export const getAuthorsInfo = ( authors: Author[] ): AuthorsInfo => {
	const mapById = new Map< number, Author >();
	const mapByName = new Map< string, Author >();
	const names: string[] = [];

	authors.forEach( ( author ) => {
		mapById.set( Number( author.id ), author );
		mapByName.set( author.name, author );
		names.push( author.name );
	} );

	return {
		authors,
		mapById,
		mapByName,
		names,
	};
};

const getIdByValue = (
	entitiesMappedByName: Map< string, Author >,
	authorValue: string | Author
) => {
	const id =
		( authorValue as Author )?.id ||
		entitiesMappedByName.get( authorValue as string )?.id;
	if ( id ) return id;
};

function AuthorControl( { query, setQueryAttribute }: QueryControlProps ) {
	const value = query.author;
	const { records: authorsList, error } = useEntityRecords< Author[] >(
		'root',
		'user',
		AUTHORS_QUERY
	);

	if ( error ) {
		return (
			<ToolsPanelItem
				label={ __( 'Authors', 'woo-gutenberg-products-block' ) }
				hasValue={ () => true }
			>
				<FormTokenField
					label={ __( 'Authors', 'woo-gutenberg-products-block' ) }
					value={ [
						__(
							'Error occurred while loading authors.',
							'woo-gutenberg-products-block'
						),
					] }
					disabled={ true }
				/>
			</ToolsPanelItem>
		);
	}

	if ( ! authorsList ) return null;

	const authorsInfo = getAuthorsInfo( authorsList as Author[] );

	/**
	 * We need to normalize the value because the block operates on a
	 * comma(`,`) separated string value and `FormTokenFields` needs an
	 * array.
	 *
	 * Returns only the existing authors ids. This prevents the component
	 * from crashing in the editor, when non existing ids are provided.
	 */
	const sanitizedValue = value
		? ( value
				.split( ',' )
				.map( ( authorId ) => {
					const author = authorsInfo.mapById.get(
						Number( authorId )
					);
					return author
						? {
								id: author.id,
								value: author.name,
						  }
						: null;
				} )
				.filter( Boolean ) as { id: string; value: string }[] )
		: [];

	const onAuthorChange = ( newValue: string[] ) => {
		const ids = Array.from(
			newValue.reduce( ( accumulator: Set< string >, author: string ) => {
				// Verify that new values point to existing entities.
				const id = getIdByValue( authorsInfo.mapByName, author );
				if ( id ) accumulator.add( id );
				return accumulator;
			}, new Set() )
		);
		setQueryAttribute( { author: ids.join( ',' ) } );
	};

	return (
		<ToolsPanelItem
			hasValue={ () => !! value }
			label={ __( 'Authors', 'woo-gutenberg-products-block' ) }
			onDeselect={ () => setQueryAttribute( { author: '' } ) }
		>
			<FormTokenField
				label={ __( 'Authors', 'woo-gutenberg-products-block' ) }
				value={ sanitizedValue }
				suggestions={ authorsInfo.names }
				onChange={ onAuthorChange }
			/>
		</ToolsPanelItem>
	);
}

export default AuthorControl;
