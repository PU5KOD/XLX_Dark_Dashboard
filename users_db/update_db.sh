#!/bin/bash
echo ""
echo "Updating database file"
echo ""
FILE_SIZE=$(wget --spider --server-response https://radioid.net/static/user.csv 2>&1 | grep -i Content-Length | awk '{print $2}')
if [ -z "$FILE_SIZE" ]; then
    echo "Downloading..."
    wget -q -O /xlxd/users_db/user.csv https://radioid.net/static/user.csv
else
    echo "File size: $FILE_SIZE bytes"
    wget -q -O - https://radioid.net/static/user.csv | pv --force -p -t -r -b -s "$FILE_SIZE" > /xlxd/users_db/user.csv
fi

# Updating the database only with new information
# Checks if files exist
if [ ! -f "/xlxd/users_db/users_base.csv" ] || [ ! -f "/xlxd/users_db/user.csv" ]; then
    echo "Error: One or both of the files (users_base.csv or user.csv) were not found."
    exit 1
fi

# Use awk to add only new rows to users_base.csv
awk -F, '
NR==FNR && NR>1 { ids[$1]=1; next }  # Stores IDs from users_base.csv (ignoring header)
FNR>1 && !($1 in ids) { print }      # Print new lines from user.csv (ignoring header)
' users_base.csv user.csv >> users_base.csv

echo "Update complete! New rows (if any) have been added to users_base.csv without changing existing data."

# Recreates the updated database
echo "Creating database"
php /xlxd/users_db/create_user_db.php
echo "Database updated successfully!"
echo ""
