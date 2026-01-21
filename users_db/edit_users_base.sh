#!/bin/bash

ARQUIVO="users_base.csv"

# --------------------------------------------
# FUNÇÕES AUXILIARES
# --------------------------------------------

validar_sem_virgulas() {
    if [[ "$1" == *","* ]]; then
        echo "Erro: O campo não pode conter vírgulas."
        return 1
    fi
    return 0
}

pausar() {
    read -rp "Pressione ENTER para continuar..."
}

listar_por_callsign() {
    local CALL="$1"
    awk -F',' -v call="$CALL" '$2 == call {printf("%5s  %s\n", NR, $0)}' "$ARQUIVO"
}

buscar_linhas_por_call() {
    local CALL="$1"
    awk -F',' -v call="$CALL" '$2 == call {print NR}' "$ARQUIVO"
}

buscar_linha_por_dmrid() {
    local DMR="$1"
    awk -F',' -v dmr="$DMR" '$1 == dmr {print NR; exit}' "$ARQUIVO"
}

# --------------------------------------------
# EXCLUSÃO DE REGISTRO
# --------------------------------------------
excluir_registro() {
    echo
    echo "=== Exclusão de Registro ==="
    echo "1) Excluir por DMRID"
    echo "2) Excluir por Indicativo"
    echo "3) Voltar"
    read -rp "Escolha: " OP

    case "$OP" in
        1)
            read -rp "DMRID (7 dígitos): " DMR
            [[ "$DMR" =~ ^[0-9]{7}$ ]] || { echo "DMRID inválido."; return; }
            LINHA=$(buscar_linha_por_dmrid "$DMR")
            if [[ -z "$LINHA" ]]; then
                echo "Nenhum registro encontrado."
                return
            fi
            sed -n "${LINHA}p" "$ARQUIVO"
            read -rp "Confirmar exclusão? (s/N): " C
            [[ "$C" =~ ^[sS]$ ]] || return
            sed -i "${LINHA}d" "$ARQUIVO"
            echo "Registro excluído."
            ;;

        2)
            read -rp "Indicativo: " CALL
            CALL=$(echo "$CALL" | tr '[:lower:]' '[:upper:]')
            LINHAS=( $(buscar_linhas_por_call "$CALL") )

            if (( ${#LINHAS[@]} == 0 )); then
                echo "Nenhum registro encontrado."
                return
            fi

            echo
            echo "Registros encontrados:"
            listar_por_callsign "$CALL"

            if (( ${#LINHAS[@]} > 1 )); then
                read -rp "Digite o número da linha a excluir: " LSEL
            else
                LSEL="${LINHAS[0]}"
            fi

            sed -n "${LSEL}p" "$ARQUIVO"
            read -rp "Confirmar exclusão? (s/N): " C
            [[ "$C" =~ ^[sS]$ ]] || return

            sed -i "${LSEL}d" "$ARQUIVO"
            echo "Registro excluído."
            ;;

        3) return ;;
    esac
}

# --------------------------------------------
# INSERÇÃO / ALTERAÇÃO
# --------------------------------------------
adicionar_ou_editar() {

    echo
    echo "=== Inclusão / Alteração de usuário ==="
    echo

    LINHA_EXISTENTE=""

    # ----- DMRID -----
    while true; do
        read -rp "DMRID (opcional, 7 dígitos): " DMRID
        validar_sem_virgulas "$DMRID" || continue

        if [[ -z "$DMRID" ]]; then
            break
        fi

        if [[ ! "$DMRID" =~ ^[0-9]{7}$ ]]; then
            echo "DMRID inválido."
            continue
        fi

        LINHA_EXISTENTE=$(buscar_linha_por_dmrid "$DMRID")

        if [[ -n "$LINHA_EXISTENTE" ]]; then
            echo "DMRID encontrado na linha $LINHA_EXISTENTE:"
            sed -n "${LINHA_EXISTENTE}p" "$ARQUIVO"
            read -rp "Editar este registro? (s/N): " R
            [[ "$R" =~ ^[sS]$ ]] || return
        fi
        break
    done


    # ----- INDICATIVO -----
    while true; do
        read -rp "Indicativo (obrigatório, até 8 caracteres): " CALL
        CALL=$(echo "$CALL" | tr '[:lower:]' '[:upper:]')

        validar_sem_virgulas "$CALL" || continue

        if [[ ! "$CALL" =~ ^[A-Z0-9]{1,8}$ ]]; then
            echo "Indicativo inválido."
            continue
        fi

        MAPA_LINHAS=( $(buscar_linhas_por_call "$CALL") )

        if (( ${#MAPA_LINHAS[@]} > 1 )); then
            echo
            echo "Foram encontrados vários registros com este indicativo:"
            listar_por_callsign "$CALL"
            echo
            read -rp "Escolha a linha desejada para editar: " LSEL
            LINHA_EXISTENTE="$LSEL"
            break

        elif (( ${#MAPA_LINHAS[@]} == 1 )); then

            if [[ -n "$LINHA_EXISTENTE" ]] && [[ "$LINHA_EXISTENTE" != "${MAPA_LINHAS[0]}" ]]; then
                echo "Erro: Indicativo pertence a outro registro."
                exit 1
            fi

            LINHA_EXISTENTE="${MAPA_LINHAS[0]}"
            echo "Indicativo existente na linha $LINHA_EXISTENTE:"
            sed -n "${LINHA_EXISTENTE}p" "$ARQUIVO"
            read -rp "Editar este registro? (s/N): " R
            [[ "$R" =~ ^[sS]$ ]] || return

            break
        fi
        break
    done


    # ----- OUTROS CAMPOS -----
    while true; do
        read -rp "Primeiro nome (obrigatório): " NOME
        validar_sem_virgulas "$NOME" || continue
        [[ -n "$NOME" ]] && break
    done

    while true; do
        read -rp "Sobrenome (opcional): " SOBRENOME
        validar_sem_virgulas "$SOBRENOME" || continue
        break
    done

    while true; do
        read -rp "Cidade (obrigatório): " CIDADE
        validar_sem_virgulas "$CIDADE" || continue
        [[ -n "$CIDADE" ]] && break
    done

    while true; do
        read -rp "Estado (obrigatório): " ESTADO
        validar_sem_virgulas "$ESTADO" || continue
        [[ -n "$ESTADO" ]] && break
    done

    while true; do
        read -rp "País (obrigatório): " PAIS
        validar_sem_virgulas "$PAIS" || continue
        [[ -n "$PAIS" ]] && break
    done

    NOVA="${DMRID},${CALL},${NOME},${SOBRENOME},${CIDADE},${ESTADO},${PAIS}"

    echo
    echo "Linha final:"
    echo "$NOVA"
    echo

    read -rp "Confirmar gravação? (s/N): " C
    [[ "$C" =~ ^[sS]$ ]] || return

    if [[ -n "$LINHA_EXISTENTE" ]]; then
        sed -i "${LINHA_EXISTENTE}s~.*~$NOVA~" "$ARQUIVO"
        echo "Registro atualizado."
    else
        echo "$NOVA" >> "$ARQUIVO"
        echo "Registro adicionado."
    fi
}

# --------------------------------------------
# MENU PRINCIPAL
# --------------------------------------------
while true; do
    echo
    echo "===== MENU ====="
    echo "1) Adicionar / Alterar registro"
    echo "2) Excluir registro"
    echo "3) Listar todos os registros de um Indicativo"
    echo "4) Sair"
    read -rp "Escolha: " OP

    case "$OP" in
        1) adicionar_ou_editar ;;
        2) excluir_registro ;;
        3)
            read -rp "Indicativo: " CALL
            CALL=$(echo "$CALL" | tr '[:lower:]' '[:upper:]')
            echo
            listar_por_callsign "$CALL"
            ;;
        4) exit 0 ;;
        *) echo "Opção inválida." ;;
    esac

    pausar
done
