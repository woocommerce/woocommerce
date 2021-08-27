# Test Instruction Logger

Test Instruction Logger retrives test instructions from the PRs in the `changelog.txt` and write them into TESTING-INSTRUCTION.md.

## Prerequisites

Test Instruction Logger requires Github username and a personal access token to use the Github REST API.

1. Follow this [guide](https://docs.github.com/en/github/authenticating-to-github/keeping-your-account-and-data-secure/creating-a-personal-access-token) to create a new personal access token.
2. Run `npm run test-instruction-logger github-credentials`. Enter your Github username and the perosnal access token. The data will be saved in `$HOME/.wca-test-instruction-logger.json`

## Writing to TESTING-INSTRUCTION.md

1. Update the `changelog.txt` 
2. Run `npm run test-instruction-logger :version`.
3. Verify `TESTING-INSTRUCTION.md`.
