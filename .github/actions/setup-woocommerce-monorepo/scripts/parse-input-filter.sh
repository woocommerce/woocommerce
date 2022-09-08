# Convert the input into filters
IFS=$'\n'
FILTERS=""
for filter in $1
do
    FILTERS+=" --filter='$filter'"
done
echo $FILTERS