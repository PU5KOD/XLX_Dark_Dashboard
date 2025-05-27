#!/bin/bash
echo ""
echo "Atualizando arquivo da Base de Dados"
echo ""
wget -O /xlxd/users_db/user.csv https://radioid.net/static/user.csv
echo "Compilando Banco de Dados"
echo ""
php /xlxd/users_db/create_user_db.php
echo ""
echo "Base e Banco de Dados atualizados com sucesso!"
echo ""
