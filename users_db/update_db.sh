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

# Atualizando a base de dados somente com novas informacoes
# Verifica se os arquivos existem
if [ ! -f "users_base.csv" ] || [ ! -f "user.csv" ]; then
    echo "Erro: Um ou ambos os arquivos (users_base.csv ou user.csv) não foram encontrados."
    exit 1
fi

# Usa awk para adicionar apenas novas linhas ao users_base.csv
awk -F, '
NR==FNR && NR>1 { ids[$1]=1; next }  # Armazena IDs do users_base.csv (ignorando cabeçalho)
FNR>1 && !($1 in ids) { print }       # Imprime linhas novas do user.csv (ignorando cabeçalho)
' users_base.csv user.csv >> users_base.csv

echo "Atualização concluída! Novas linhas (se houverem) foram adicionadas ao users_base.csv sem alterar os dados existentes."

# Recria a base de dados atualizada
echo "Creating database"
php /xlxd/users_db/create_user_db.php
echo "Database updated successfully!"
echo ""
