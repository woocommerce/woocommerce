#!/bin/bash

set -eo pipefail

log() {
	local level="$1"
	shift
	echo "[$level] $*"
}

check_env_vars() {
	local missing_vars=()
	for var in "$@"; do
		if [[ -z "${!var}" ]]; then
			missing_vars+=("$var")
		fi
	done
	
	if [[ ${#missing_vars[@]} -ne 0 ]]; then
		log "ERROR" "Missing required environment variables: ${missing_vars[*]}"
		exit 1
	fi
}

check_dependencies() {
	local missing_deps=()
	for cmd in "$@"; do
		if ! command -v "$cmd" >/dev/null 2>&1; then
			missing_deps+=("$cmd")
		fi
	done
	
	if [[ ${#missing_deps[@]} -ne 0 ]]; then
		log "ERROR" "Missing required dependencies: ${missing_deps[*]}"
		exit 1
	fi
}

declare -A LABEL_MAP=(
	["fix"]="type: fix"
	["add"]="type: feature"
	["update"]="type: enhancement"
	["tweak"]="type: enhancement"
	["enhancement"]="type: enhancement"
	["performance"]="type: performance"
	["dev"]="type: infrastructure"
)

VALID_TYPES=$(echo "${!LABEL_MAP[@]}" | tr ' ' '|')

check_env_vars "PR_NUMBER" "GITHUB_WORKSPACE"
check_dependencies "gh" "grep" "awk"

APPLIED_LABELS=()

# Get list of changed files in the PR and filter to changelog files
log "INFO" "Fetching changed files for PR #$PR_NUMBER"
CHANGED_FILES=$(gh pr view "$PR_NUMBER" --json files -q '.files[].path' | grep '^plugins/woocommerce/changelog/' || true)

if [[ -z "$CHANGED_FILES" ]]; then
	log "INFO" "No changelog files were modified in this PR"
	exit 0
fi

log "INFO" "Changed files detected:"
echo "$CHANGED_FILES"

# Loop through only changed changelog files
for FILE in $CHANGED_FILES; do
	FULL_PATH="$GITHUB_WORKSPACE/$FILE"

	if [[ ! -f "$FULL_PATH" ]]; then
		log "WARN" "Skipping non-existent file: $FULL_PATH (Check if checkout fetched all changes)"
		continue
	fi

	log "INFO" "Processing file: $FULL_PATH"

	# Extract the type from file content
	if ! TYPE=$(grep -iE "^Type: ($VALID_TYPES)" "$FULL_PATH" | awk -F': ' '{print tolower($2)}' | head -n 1); then
		log "WARN" "No valid changelog type found in $FULL_PATH"
		continue
	fi

	log "INFO" "Found type: $TYPE"

	# Validate extracted type
	if [[ ! "${LABEL_MAP[$TYPE]}" ]]; then
		log "WARN" "Invalid changelog type found in $FULL_PATH: '$TYPE'. Skipping."
		continue
	fi

	# Map the type to a label if it exists
	LABEL="${LABEL_MAP[$TYPE]}"
	log "INFO" "Mapped to label: $LABEL"

	if [[ -n "$LABEL" && ! " ${APPLIED_LABELS[*]} " =~ " $LABEL " ]]; then
		APPLIED_LABELS+=("$LABEL")
	fi
done

log "INFO" "Labels to be applied: ${APPLIED_LABELS[*]:-none}"

# Apply labels if any were found
if [ ${#APPLIED_LABELS[@]} -ne 0 ]; then
	log "INFO" "Applying labels: ${APPLIED_LABELS[*]}"
	if ! gh pr edit "$PR_NUMBER" --add-label "${APPLIED_LABELS[@]}"; then
		log "ERROR" "Failed to apply labels via GitHub CLI"
		exit 1
	fi
	log "INFO" "Successfully applied labels"
else
	log "INFO" "No matching changelog types found or no relevant changelog files"
fi
