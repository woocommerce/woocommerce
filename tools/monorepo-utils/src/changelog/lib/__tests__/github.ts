/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import {
	getChangelogSignificance,
	getChangelogType,
	getChangelogDetails,
} from '../github';
import { Logger } from '../../../core/logger';

jest.mock( '../../../core/logger', () => {
	return {
		Logger: {
			error: jest.fn(),
		},
	};
} );

describe( 'getChangelogSignificance', () => {
	it( 'should return the selected significance', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message\r\n' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment\r\n' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		const significance = getChangelogSignificance( body );
		expect( significance ).toBe( 'patch' );
	} );

	it( 'should error when no significance selected', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [ ] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message\r\n' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment\r\n' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		getChangelogSignificance( body );
		expect( Logger.error ).toHaveBeenCalledWith(
			'No changelog significance found'
		);
	} );

	it( 'should error when more than one significance selected', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [x] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message\r\n' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment\r\n' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		getChangelogSignificance( body );
		expect( Logger.error ).toHaveBeenCalledWith(
			'Multiple changelog significances found. Only one can be entered'
		);
	} );
} );

describe( 'getChangelogType', () => {
	it( 'should return the selected changelog type', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message\r\n' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment\r\n' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		const type = getChangelogType( body );
		expect( type ).toBe( 'fix' );
	} );

	it( 'should error when no type selected', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [ ] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message\r\n' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment\r\n' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		getChangelogType( body );
		expect( Logger.error ).toHaveBeenCalledWith(
			'No changelog type found'
		);
	} );

	it( 'should error more than one type selected', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [ ] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [x] Update - Update existing functionality\r\n' +
			'- [x] Dev - Development related task\r\n' +
			'- [x] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message\r\n' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment\r\n' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		getChangelogType( body );
		expect( Logger.error ).toHaveBeenCalledWith(
			'Multiple changelog types found. Only one can be entered'
		);
	} );
} );

describe( 'getChangelogDetails', () => {
	it( 'should return the changelog details', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message\r\n' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment\r\n' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		const details = getChangelogDetails( body );
		expect( details.significance ).toEqual( 'patch' );
		expect( details.type ).toEqual( 'fix' );
		expect( details.message ).toEqual( 'This is a very useful fix.' );
		expect( details.comment ).toEqual( '' );
	} );

	it( 'should error if a comment and message are added', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message\r\n' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment\r\n' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'This is a very useful comment.\r\n' +
			'\r\n' +
			'</details>';

		getChangelogDetails( body );
		expect( Logger.error ).toHaveBeenCalledWith(
			'Both a message and comment were found. Only one can be entered'
		);
	} );

	it( 'should error if a comment is entered with a significance other than patch', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [ ] Patch\r\n' +
			'- [x] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message\r\n' +
			'<!-- Add a changelog message here -->\r\n' +
			'\r\n' +
			'#### Comment\r\n' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'This is a very useful comment.\r\n' +
			'\r\n' +
			'</details>';

		getChangelogDetails( body );
		expect( Logger.error ).toHaveBeenCalledWith(
			'Only patch changes can have a comment. Please change the significance to patch or remove the comment'
		);
	} );
} );
