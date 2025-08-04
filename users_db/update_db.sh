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
echo "Creating database"
php /xlxd/users_db/create_user_db.php
echo "Database updated successfully!"
echo ""
