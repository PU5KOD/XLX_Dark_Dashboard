#!/bin/bash
echo ""
echo "Atualizando arquivo da Base de Dados"
echo ""
wget -O /xlxd/user.csv https://radioid.net/static/user.csv 
echo "Compilando Banco de Dados"
echo ""
php /var/www/html/xlxd/update_user_db.php
echo ""
echo "Base e Banco de Dados atualizados com sucesso!"
echo ""
