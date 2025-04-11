declare -A LABEL_MAP=(
                ["fix"]="type: fix"
                ["add"]="type: feature"
                ["update"]="type: enhancement"
                ["tweak"]="type: enhancement"
                ["enhancement"]="type: enhancement"
                ["performance"]="type: performance"
                ["dev"]="type: infrastructure"
              )

              APPLIED_LABELS=()

              # Get list of changed files in the PR and filter to changelog files
              CHANGED_FILES=$(gh pr view "$PR_NUMBER" --json files -q '.files[].path' | grep '^plugins/woocommerce/changelog/' || true)

              echo "Changed files detected:"
              echo "$CHANGED_FILES"

              # Loop through only changed changelog files
              for FILE in $CHANGED_FILES; do
                FULL_PATH="$GITHUB_WORKSPACE/$FILE"

                if [[ -f "$FULL_PATH" ]]; then
                  echo "Processing file: $FULL_PATH"

                  # Extract the type from file content
                  TYPE=$(grep -iE '^Type: (fix|add|update|tweak|enhancement|performance|dev)' "$FULL_PATH" | awk -F': ' '{print tolower($2)}' | head -n 1)
                  echo "Type: $TYPE"

                  # Validate extracted type
                  if [[ ! "${LABEL_MAP[$TYPE]}" ]]; then
                    echo "Invalid changelog type found in $FULL_PATH: '$TYPE'. Skipping."
                    continue
                  fi

                  # Map the type to a label if it exists
                  LABEL="${LABEL_MAP[$TYPE]}"
                  echo "Label: $LABEL"

                  if [[ -n "$LABEL" && ! " ${APPLIED_LABELS[*]} " =~ " $LABEL " ]]; then
                    APPLIED_LABELS+=("$LABEL")
                  fi
                else
                  echo "Skipping non-existent file: $FULL_PATH (Check if checkout fetched all changes)"
                fi
              done

              echo "APPLIED_LABELS: ${APPLIED_LABELS[*]}"
              # Apply labels if any were found
              if [ ${#APPLIED_LABELS[@]} -ne 0 ]; then
                echo "Applying labels: ${APPLIED_LABELS[*]}"
                gh pr edit "$PR_NUMBER" --add-label "${APPLIED_LABELS[@]}" || {
                  echo "Error: Failed to apply labels via GitHub CLI."
                  exit 1
                }
              else
                echo "No matching changelog types found or no relevant changelog files."
              fi
