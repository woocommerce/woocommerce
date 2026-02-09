#!/bin/bash

# Assuming all necessary environment variables like GITHUB_EVENT_PATH are correctly set

# Function to calculate the second Tuesday of the given month and year
# as the release day is always the 2nd Tuesday
calculate_second_tuesday() {
  year=$1
  month=$2

  first_of_month="$year-$month-01"
  day_of_week=$(date -d "$first_of_month" "+%u")
  offset_to_first_tuesday=$(( (9 - day_of_week) % 7 ))
  first_tuesday=$(date -d "$first_of_month +$offset_to_first_tuesday days" "+%Y-%m-%d")
  # Calculate the second Tuesday by adding 7 days
  second_tuesday=$(date -d "$first_tuesday +7 days" "+%Y-%m-%d")
  
  echo $second_tuesday
}

# Set the initial values for version calculation
initial_version_major=8  # Major version start
initial_version_minor=8  # Minor version start
initial_year=2024
initial_month=4  # April, for the 8.8.0 release

# Assuming the script is run in or after 2024
current_year=$(date +%Y) # Get the current year

# Calculate the number of versions to generate based on the current year
additional_versions=$(( (current_year - 2024 + 1) * 12 ))

# Versions to calculate
versions_to_calculate=$additional_versions

# Declare the associative array outside the loop
declare -A MILESTONE_DATES

for (( i=0; i<versions_to_calculate; i++ )); do
  # Calculate year and month offset
  offset_year=$(( (initial_month + i - 1) / 12 ))
  current_year=$(( initial_year + offset_year ))
  current_month=$(( (initial_month + i - 1) % 12 + 1 ))

  # Format current month correctly
  current_month_formatted=$(printf "%02d" $current_month)

  # Calculate the release date
  release_date=$(calculate_second_tuesday $current_year $current_month_formatted)

  # Calculate the total versions from start, adjusting for starting at 8.8.0
  total_versions_from_start=$(( i + initial_version_minor ))

  # Adjust version major and minor calculations
  version_major=$(( initial_version_major + total_versions_from_start / 10 ))
  version_minor=$(( total_versions_from_start % 10 ))

  # Construct version string
  version="$version_major.$version_minor.0"

  # Populate the associative array with version as key and release_date as value
  MILESTONE_DATES["$version"]=$release_date

  echo "Version $version will be released on $release_date"     
done     

MILESTONE_TITLE="${GITHUB_EVENT_PATH_PULL_REQUEST_MILESTONE_TITLE}"
MILESTONE_DATE="Undefined"

# Check if the milestone title exists in our predefined list and get the date
if [[ -v "MILESTONE_DATES[${MILESTONE_TITLE}]" ]]; then
  MILESTONE_DATE=${MILESTONE_DATES[${MILESTONE_TITLE}]}
fi

# Export for later steps
echo "MILESTONE_DATE=${MILESTONE_DATE}" >> $GITHUB_ENV

# Print the array in the desired format for display purposes
echo "MILESTONE_DATES=("
for version in "${!MILESTONE_DATES[@]}"; do
echo "  [\"$version\"]=\"${MILESTONE_DATES[$version]}\""
done
echo ")"
