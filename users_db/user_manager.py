#!/usr/bin/env python3
import csv
import os
import re
import shutil
import sys
from datetime import datetime

# ============================================================
#                   CONFIGURAÇÃO DE DIRETÓRIOS
# ============================================================

LOG_DIR = "log"
BACKUP_DIR = "backup"

os.makedirs(LOG_DIR, exist_ok=True)
os.makedirs(BACKUP_DIR, exist_ok=True)

ARQUIVO = "users_base.csv"
HISTORICO = os.path.join(LOG_DIR, "users_history.log")


# ============================================================
#                      CORES DO TERMINAL
# ============================================================
class Cores:
    OK = "\033[92m"
    ALERTA = "\033[93m"
    ERRO = "\033[91m"
    INFO = "\033[96m"
    FIM = "\033[0m"


# ============================================================
#                FUNÇÕES DE LEITURA / ESCRITA
# ============================================================
def ler_csv():
    with open(ARQUIVO, newline="", encoding="utf-8") as f:
        return list(csv.reader(f))


def escrever_csv(linhas):
    with open(ARQUIVO, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerows(linhas)


# ============================================================
#                    BACKUP E HISTÓRICO
# ============================================================
def registrar_historico(antes, depois):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    with open(HISTORICO, "a", encoding="utf-8") as h:
        h.write(f"[{timestamp}] ALTERAÇÃO\n")
        h.write(f"ANTES : {antes}\n")
        h.write(f"DEPOIS: {depois}\n")
        h.write("-" * 60 + "\n")


def backup():
    timestamp = datetime.now().strftime("%Y%m%d-%H%M%S")
    destino = os.path.join(BACKUP_DIR, f"{ARQUIVO}.bak-{timestamp}")
    shutil.copyfile(ARQUIVO, destino)
    print(f"{Cores.INFO}Backup criado: {destino}{Cores.FIM}")


# ============================================================
#                   BUSCAS ROBUSTAS
# ============================================================
def buscar_por_dmrid(linhas, dmrid):
    if dmrid == "":
        return None
    for idx, row in enumerate(linhas):
        if len(row) < 7:
            continue
        if row[0] == dmrid:
            return idx
    return None


def buscar_por_callsign(linhas, call):
    for idx, row in enumerate(linhas):
        if len(row) < 7:
            continue
        if row[1].upper() == call.upper():
            return idx
    return None


# ============================================================
#              ENTRADAS / VALIDAÇÕES
# ============================================================
def perguntar(msg, default=None):
    if default:
        resp = input(f"{msg} [{default}]: ").strip()
        return resp if resp != "" else default
    else:
        return input(msg).strip()


def obrigatorio(msg, default=None):
    while True:
        valor = perguntar(msg, default)
        if valor.strip():
            return valor.strip()
        print(f"{Cores.ERRO}Campo obrigatório.{Cores.FIM}")


def validar(regex, msg, vazio_permitido=False, default=None):
    while True:
        valor = perguntar(msg, default)
        if valor == "" and vazio_permitido:
            return ""
        if re.match(regex, valor):
            return valor
        print(f"{Cores.ERRO}Valor inválido.{Cores.FIM}")


# ============================================================
#                CHECK MODE COM RELATÓRIO TXT
# ============================================================
def verificar_estrutura():

    timestamp = datetime.now().strftime("%Y%m%d-%H%M%S")
    RELATORIO = os.path.join(LOG_DIR, f"check_report-{timestamp}.txt")

    arquivo_relatorio = open(RELATORIO, "w", encoding="utf-8")

    class Tee:
        def __init__(self, *saidas):
            self.saidas = saidas

        def write(self, txt):
            for s in self.saidas:
                s.write(txt)

        def flush(self):
            for s in self.saidas:
                s.flush()

    saídas = Tee(sys.stdout, arquivo_relatorio)
    sys.stdout = saídas

    print(f"{Cores.INFO}=== VERIFICAÇÃO DO ARQUIVO {ARQUIVO} ==={Cores.FIM}")

    if not os.path.exists(ARQUIVO):
        print(f"{Cores.ERRO}Arquivo não encontrado.{Cores.FIM}")
        sys.stdout = sys.__stdout__
        arquivo_relatorio.close()
        return

    linhas = ler_csv()

    total = 0
    ok = 0
    vazias = []
    colunas_erradas = []
    dmrids_invalidos = []
    calls_invalidos = []
    duplicados_dmrid = {}

    vistos_dmrid = {}

    # ----------------- ANALISAR ARQUIVO -----------------
    for i, row in enumerate(linhas, start=1):
        total += 1

        if len(row) == 0 or all(c.strip() == "" for c in row):
            vazias.append(i)
            continue

        if len(row) != 7:
            colunas_erradas.append((i, len(row)))
            continue

        dmrid, call, nome, sobrenome, cidade, estado, pais = row

        if dmrid != "" and not re.match(r"^\d{7}$", dmrid):
            dmrids_invalidos.append((i, dmrid))

        if not re.match(r"^[A-Z0-9]{1,8}$", call.upper()):
            calls_invalidos.append((i, call))

        if dmrid != "":
            if dmrid not in vistos_dmrid:
                vistos_dmrid[dmrid] = i
            else:
                duplicados_dmrid.setdefault(dmrid, []).extend([vistos_dmrid[dmrid], i])

        ok += 1

    # Função auxiliar
    def exibir_lista(titulo, lista, formato=None):
        if not lista:
            print(f"{Cores.OK}{titulo}: nenhuma.{Cores.FIM}")
            return
        print(f"{Cores.ALERTA}{titulo}:{Cores.FIM}")
        for item in lista:
            print("   " + (formato(item) if formato else str(item)))

    # ---------------- RELATÓRIO -----------------
    print()
    print(f"{Cores.INFO}Total de linhas: {total}{Cores.FIM}")
    print(f"{Cores.OK}Linhas válidas: {ok}{Cores.FIM}")
    print()

    exibir_lista("Linhas vazias", vazias)
    exibir_lista("Linhas com número errado de colunas",
                 colunas_erradas,
                 lambda x: f"Linha {x[0]}: {x[1]} colunas")

    exibir_lista("DMRIDs inválidos",
                 dmrids_invalidos,
                 lambda x: f"Linha {x[0]}: '{x[1]}'")

    exibir_lista("Indicativos inválidos",
                 calls_invalidos,
                 lambda x: f"Linha {x[0]}: '{x[1]}'")

    exibir_lista("DMRIDs duplicados",
                 [[k] + v for k, v in duplicados_dmrid.items()],
                 lambda x: f"DMRID {x[0]} duplicado nas linhas {x[1:]}")

    # --------- Estatísticas (não são erros) ----------
    print()
    contagem_call = {}

    for row in linhas:
        if len(row) == 7:
            call = row[1].upper()
            contagem_call.setdefault(call, 0)
            contagem_call[call] += 1

    muitos = {k: v for k, v in contagem_call.items() if v > 1}

    if muitos:
        print(f"{Cores.INFO}Indicativos com múltiplos DMRIDs (normal):{Cores.FIM}")
        for call, qtd in sorted(muitos.items(), key=lambda x: -x[1]):
            print(f"   {call}: {qtd} registros")
    else:
        print(f"{Cores.OK}Nenhum indicativo com múltiplos DMRIDs.{Cores.FIM}")

    print()
    print(f"{Cores.INFO}=== Verificação concluída ==={Cores.FIM}")

    sys.stdout = sys.__stdout__
    arquivo_relatorio.close()

    print(f"{Cores.OK}Relatório salvo em: {RELATORIO}{Cores.FIM}")


# ============================================================
#                  MODO INTERATIVO NORMAL
# ============================================================
def main():

    if not os.path.exists(ARQUIVO):
        print(f"{Cores.ERRO}Erro: arquivo {ARQUIVO} não encontrado.{Cores.FIM}")
        return

    linhas = ler_csv()
    linha_existente = None
    registro_antigo = None

    print(f"{Cores.INFO}\n=== Inclusão / Alteração interativa ===\n{Cores.FIM}")

    # ------------------- DMRID -------------------
    while True:
        dmrid = perguntar("DMRID (opcional, 7 dígitos): ")
        if dmrid == "":
            break
        if not re.match(r"^\d{7}$", dmrid):
            print(f"{Cores.ERRO}DMRID inválido.{Cores.FIM}")
            continue

        idx = buscar_por_dmrid(linhas, dmrid)
        if idx is not None:
            print(f"{Cores.ALERTA}DMRID existe na linha {idx+1}:{Cores.FIM}")
            print(",".join(linhas[idx]))
            resp = perguntar("Editar este registro? (s/N): ")
            if resp.lower() != "s":
                return
            linha_existente = idx
            registro_antigo = linhas[idx][:]
        break

    # ------------------- INDICATIVO -------------------
    while True:
        call = perguntar("Indicativo (obrigatório, até 8 chars): ").upper()
        if not re.match(r"^[A-Z0-9]{1,8}$", call):
            print(f"{Cores.ERRO}Indicativo inválido.{Cores.FIM}")
            continue

        idx = buscar_por_callsign(linhas, call)
        if idx is not None:
            if linha_existente is not None and idx != linha_existente:
                print(f"{Cores.ERRO}Indicativo pertence a outro registro.{Cores.FIM}")
                return

            print(f"{Cores.ALERTA}Indicativo existe na linha {idx+1}:{Cores.FIM}")
            print(",".join(linhas[idx]))
            resp = perguntar("Editar este registro? (s/N): ")
            if resp.lower() != "s":
                return

            linha_existente = idx
            registro_antigo = linhas[idx][:]
        break

    # ------------------- CAMPOS COMPLEMENTARES -------------------
    if linha_existente is not None:
        base = linhas[linha_existente]
        dmrid = dmrid or base[0]
        call = call or base[1]
        nome = obrigatorio("Primeiro nome:", base[2])
        sobrenome = perguntar("Sobrenome:", base[3])
        cidade = obrigatorio("Cidade:", base[4])
        estado = obrigatorio("Estado:", base[5])
        pais = obrigatorio("País:", base[6])
    else:
        nome = obrigatorio("Primeiro nome (obrigatório): ")
        sobrenome = perguntar("Sobrenome (opcional): ")
        cidade = obrigatorio("Cidade (obrigatório): ")
        estado = obrigatorio("Estado (obrigatório): ")
        pais = obrigatorio("País (obrigatório): ")

    nova_linha = [dmrid, call, nome, sobrenome, cidade, estado, pais]

    print(f"\n{Cores.OK}Linha final:{Cores.FIM}")
    print(",".join(nova_linha))

    confirmar = perguntar("Confirmar gravação? (s/N): ")
    if confirmar.lower() != "s":
        print("Operação cancelada.")
        return

    backup()

    if linha_existente is not None:
        linhas[linha_existente] = nova_linha
        escrever_csv(linhas)
        registrar_historico(",".join(registro_antigo), ",".join(nova_linha))
        print(f"{Cores.OK}Registro alterado com sucesso.{Cores.FIM}")
    else:
        linhas.append(nova_linha)
        escrever_csv(linhas)
        registrar_historico("(NOVO REGISTRO)", ",".join(nova_linha))
        print(f"{Cores.OK}Registro adicionado com sucesso.{Cores.FIM}")


# ============================================================
#                       ENTRY POINT
# ============================================================
if __name__ == "__main__":
    if "--check" in sys.argv:
        verificar_estrutura()
    else:
        main()