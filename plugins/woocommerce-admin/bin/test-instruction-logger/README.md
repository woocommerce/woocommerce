# Test Instruction Logger

Test Instruction Logger retrieves test instructions from the PRs in the `changelog.txt` and writes them into TESTING-INSTRUCTION.md.

## Prerequisites

Test Instruction Logger requires Github username and a personal access token to use the Github REST API.

1. Follow this [guide](https://docs.github.com/en/github/authenticating-to-github/keeping-your-account-and-data-secure/creating-a-personal-access-token) to create a new personal access token.
2. Run `npm run test-instruction-logger github-credentials`. Enter your Github username and the perosnal access token. The data will be saved in `$HOME/.wca-test-instruction-logger.json`

## Writing to TESTING-INSTRUCTION.md

1. Update the `changelog.txt` 
2. Run `npm run test-instruction-logger -- write :version`.
3. Verify `TESTING-INSTRUCTION.md`.

### Options

#### types

A comma seperated list of changelog types to retrieve the testing instructions from.

`npm run test-instruction-logger -- write :version --types=enhancement,add`

#### save-to

Allows you to save the testing instructions to a different file. Default: TESTING-INSTRUCTIONS.md

`npm run test-instruction-logger -- write :version --save-to=instructions.md`
