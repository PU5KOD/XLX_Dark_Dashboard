#!/bin/bash

ARQUIVO="users_base.csv"

if [ ! -f "$ARQUIVO" ]; then
    echo "Erro: O arquivo $ARQUIVO não foi encontrado."
    exit 1
fi

echo "=== Inclusão / Alteração interativa de usuário ==="
echo

# ---------- DMRID (opcional, verificação imediata) ----------
LINHA_EXISTENTE=""

while true; do
    read -rp "DMRID (opcional, 7 dígitos): " DMRID

    if [ -z "$DMRID" ]; then
        break
    elif [[ "$DMRID" =~ ^[0-9]{7}$ ]]; then
        LINHA_EXISTENTE=$(awk -F',' -v dmr="$DMRID" '$1 == dmr {print NR; exit}' "$ARQUIVO")
        if [ -n "$LINHA_EXISTENTE" ]; then
            echo "DMRID já existe no arquivo (linha $LINHA_EXISTENTE)."
            sed -n "${LINHA_EXISTENTE}p" "$ARQUIVO"
            read -rp "Deseja ALTERAR este registro? (s/N): " RESP
            [[ "$RESP" =~ ^[sS]$ ]] || exit 0
        fi
        break
    else
        echo "Erro: DMRID inválido."
    fi
done

# ---------- Indicativo (obrigatório, verificação imediata) ----------
while true; do
    read -rp "Indicativo (obrigatório, até 6 caracteres): " CALL
    CALL=$(echo "$CALL" | tr '[:lower:]' '[:upper:]')

    if [[ "$CALL" =~ ^[A-Z0-9]{1,6}$ ]]; then
        LINHA_CALL=$(awk -F',' -v call="$CALL" '$2 == call {print NR; exit}' "$ARQUIVO")
        if [ -n "$LINHA_CALL" ]; then
            # Se já encontrou pelo DMRID, garante que seja a mesma linha
            if [ -n "$LINHA_EXISTENTE" ] && [ "$LINHA_CALL" != "$LINHA_EXISTENTE" ]; then
                echo "Erro: Indicativo pertence a outro registro."
                exit 1
            fi
            LINHA_EXISTENTE="$LINHA_CALL"
            echo "Indicativo já existe no arquivo (linha $LINHA_EXISTENTE)."
            sed -n "${LINHA_EXISTENTE}p" "$ARQUIVO"
            read -rp "Deseja ALTERAR este registro? (s/N): " RESP
            [[ "$RESP" =~ ^[sS]$ ]] || exit 0
        fi
        break
    else
        echo "Erro: Indicativo inválido."
    fi
done

# ---------- Demais campos ----------
while true; do
    read -rp "Primeiro nome (obrigatório): " NOME
    [ -n "$NOME" ] && break
done

read -rp "Sobrenome (opcional): " SOBRENOME

while true; do
    read -rp "Cidade (obrigatório): " CIDADE
    [ -n "$CIDADE" ] && break
done

while true; do
    read -rp "Estado (obrigatório): " ESTADO
    [ -n "$ESTADO" ] && break
done

while true; do
    read -rp "País (obrigatório): " PAIS
    [ -n "$PAIS" ] && break
done

# ---------- Montagem da linha ----------
NOVA_LINHA="${DMRID},${CALL},${NOME},${SOBRENOME},${CIDADE},${ESTADO},${PAIS}"

echo
echo "Linha final:"
echo "$NOVA_LINHA"
echo
read -rp "Confirmar gravação? (s/N): " CONFIRMA

[[ "$CONFIRMA" =~ ^[sS]$ ]] || exit 0

# ---------- Escrita ----------
if [ -n "$LINHA_EXISTENTE" ]; then
    sed -i "${LINHA_EXISTENTE}s~.*~$NOVA_LINHA~" "$ARQUIVO"
    echo "Registro alterado com sucesso."
else
    echo "$NOVA_LINHA" >> "$ARQUIVO"
    echo "Registro adicionado com sucesso."
fi