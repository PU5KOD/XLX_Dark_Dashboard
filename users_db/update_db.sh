#!/bin/bash
echo ""
echo "Updating Database file"
echo ""
FILE_SIZE=$(wget --spider --server-response https://radioid.net/static/user.csv 2>&1 | grep -i Content-Length | awk '{print $2}')
if [ -z "$FILE_SIZE" ]; then
    echo "Downloading..."
    wget -q -O /xlxd/users_db/user.csv https://radioid.net/static/user.csv
else
    wget -q -O - https://radioid.net/static/user.csv | pv -s "$FILE_SIZE" > /xlxd/users_db/user.csv
fi
echo "Compiling Database"
php /xlxd/users_db/create_user_db.php
echo "Database and Base updated successfully!"
echo ""
