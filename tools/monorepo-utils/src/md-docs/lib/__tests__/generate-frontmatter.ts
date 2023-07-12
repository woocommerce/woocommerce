/**
 * Internal dependencies
 */
import { generatePostFrontMatter } from '../generate-frontmatter';

describe( 'generateFrontmatter', () => {
	it( 'should not allow disallowed attributes', () => {
		const frontMatter = generatePostFrontMatter( `---
title: Hello World
description: This is a description
post_content: This is some content
post_title: This is a title
post_date: 2023-07-12 15:30:00
---
` );

		expect( frontMatter ).toEqual( {
			post_title: 'This is a title',
			post_date: '2023-07-12 15:30:00',
		} );
	} );
} );
