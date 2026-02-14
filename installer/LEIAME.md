# Instalador do Refletor XLX - Versão Otimizada

Esta é uma versão otimizada do instalador do Refletor de Rádio Amador Multiprotocolo XLX com organização aprimorada, registro avançado e design modular.

## Visão Geral

O instalador foi completamente refatorado para fornecer:

1. **Design Modular**: Separação de responsabilidades com funções visuais/UI em uma biblioteca dedicada
2. **Registro Avançado**: Registro detalhado de todas as operações com timestamps e rastreamento de status
3. **Permissões Otimizadas**: Permissões de arquivo aplicadas com base no tipo de arquivo, não configurações genéricas
4. **Organização de Código Aprimorada**: Funções estruturadas para melhor legibilidade e manutenção
5. **Padrões Padronizados**: Padrões de codificação consistentes em todo o script

## Componentes Principais

### 1. cli_visual_unicode.sh

Uma biblioteca visual abrangente que fornece:

- **Paleta de Cores**: Definições de cores estendidas para mensagens semânticas
- **Ícones Unicode**: Conjunto rico de ícones para diferentes tipos de mensagem
- **Separadores de Linha**: Vários estilos de linha para organização visual
- **Formatação de Texto**: Funções para quebra, centralização e formatação de texto
- **Mensagens Semânticas**: Funções padronizadas para mensagens de informação, sucesso, aviso, erro
- **Indicadores de Progresso**: Temporizadores de contagem regressiva e spinners
- **Permissões de Arquivo**: Configuração inteligente de permissões baseada em tipos de arquivo
- **Funções de Validação**: Validação de entrada para email, domínio, IP, porta
- **Verificações de Sistema**: Funções para verificar acesso root, conectividade à internet, etc.
- **Funções de Registro**: Registro abrangente com múltiplos níveis

### 2. installer.sh

O script principal de instalação apresentando:

- **Inicialização Estruturada**: Configuração clara de caminhos, constantes e registro
- **Validação do Sistema**: Verificação de root, conectividade à internet, verificação de distribuição
- **Prompts Interativos**: Coleta de entrada amigável ao usuário com validação
- **Instalação Modular**: Funções separadas para cada fase de instalação
- **Tratamento de Erros**: Detecção e relatório adequado de erros
- **Gerenciamento de Serviços**: Inicialização e configuração automatizada de serviços

### 3. Diretório Templates

Contém todos os templates de configuração necessários:

- `apache.tbd.conf` - Configuração de virtual host Apache
- `xlx_log.service` - Serviço systemd para registro XLX
- `xlx_log.sh` - Script de gerenciamento de log XLX
- `xlx_logrotate.conf` - Configuração de rotação de logs
- `update_XLX_db.service` - Serviço de atualização do banco de dados
- `update_XLX_db.timer` - Timer de atualização do banco de dados

## Melhorias

### Organização do Código

**Antes:**
- Responsabilidades misturadas (UI, lógica, configuração) em arquivo único
- Convenções de nomenclatura inconsistentes
- Valores fixos espalhados por todo o código
- Configurações genéricas de permissões

**Depois:**
- Funções UI/visual separadas em biblioteca
- Convenções de nomenclatura e padrões consistentes
- Constantes definidas no início do script
- Permissões específicas por tipo de arquivo

### Aprimoramentos de Registro

**Antes:**
- Redirecionamento básico de saída para arquivo de log
- Contexto limitado nas entradas de log
- Sem registro estruturado

**Depois:**
- Registro detalhado com timestamps
- Rastreamento de sucesso/falha para cada operação
- Formato de log estruturado com níveis (INFO, SUCCESS, WARNING, ERROR)
- Arquivo de log separado para cada execução de instalação

### Gerenciamento de Permissões

**Antes:**
```bash
find /xlxd -type d -exec chmod 755 {} \;
find /xlxd -type f -exec chmod 755 {} \;
find "$WEBDIR" -type d -exec chmod 755 {} \;
find "$WEBDIR" -type f -exec chmod 755 {} \;
```

**Depois:**
```bash
# Diretórios: 755 (rwxr-xr-x)
find "$path" -type d -exec chmod 755 {} \;

# Scripts executáveis: 755 (rwxr-xr-x)
find "$path" -type f \( -name "*.sh" -o -name "*.py" -o -name "*.pl" \) -exec chmod 755 {} \;

# Arquivos de configuração: 644 (rw-r--r--)
find "$path" -type f \( -name "*.conf" -o -name "*.config" -o -name "*.cfg" ... \) -exec chmod 644 {} \;

# Arquivos de serviço/timer: 644 (rw-r--r--)
find "$path" -type f \( -name "*.service" -o -name "*.timer" \) -exec chmod 644 {} \;

# Arquivos web (PHP, HTML, CSS, JS): 644 (rw-r--r--)
find "$path" -type f \( -name "*.php" -o -name "*.html" ... \) -exec chmod 644 {} \;

# Arquivos de banco de dados: 644 (rw-r--r--)
find "$path" -type f \( -name "*.db" -o -name "*.sqlite" -o -name "*.dat" \) -exec chmod 644 {} \;
```

## Uso

### Instalação

```bash
cd installer
chmod +x installer.sh
sudo ./installer.sh
```

O script irá:

1. Verificar requisitos do sistema (acesso root, internet, distribuição)
2. Verificar instalações existentes
3. Coletar informações do sistema
4. Coletar configuração do usuário interativamente
5. Exibir configurações para confirmação
6. Atualizar pacotes do sistema
7. Instalar dependências
8. Baixar e compilar XLX
9. Configurar serviços XLX
10. Instalar Echo Test (opcional)
11. Instalar e configurar dashboard
12. Instalar certificado SSL (opcional)
13. Iniciar todos os serviços
14. Exibir informações de conclusão

### Logs

Os logs de instalação são salvos em:
```
installer/log/xlx_install_YYYY-MM-DD_HH-MM-SS.log
```

Cada log contém:
- Timestamp para cada operação
- Status de sucesso/falha
- Saída do comando (para operações com falha)
- Informações do sistema
- Configurações

## Repositórios do GitHub

O instalador usa os seguintes repositórios personalizados do PU5KOD:

- **Refletor XLX**: https://github.com/PU5KOD/xlxd.git
- **Servidor Echo Test**: https://github.com/PU5KOD/XLXEcho.git
- **Dashboard**: https://github.com/PU5KOD/XLX_Dark_Dashboard.git

Estes repositórios contêm personalizações e templates que não estão presentes nas versões originais.

## Referência de Permissões de Arquivo

| Tipo de Arquivo | Permissão | Octal | Descrição |
|-----------------|-----------|-------|-----------|
| Diretórios | rwxr-xr-x | 755 | Legível e executável por todos, gravável pelo proprietário |
| Scripts (*.sh, *.py, *.pl) | rwxr-xr-x | 755 | Scripts executáveis |
| Executáveis binários | rwxr-xr-x | 755 | Binários compilados |
| Arquivos de config (*.conf, *.cfg, etc) | rw-r--r-- | 644 | Legível por todos, gravável pelo proprietário |
| Arquivos de serviço (*.service, *.timer) | rw-r--r-- | 644 | Arquivos de unidade systemd |
| Arquivos web (*.php, *.html, *.css, *.js) | rw-r--r-- | 644 | Arquivos de conteúdo web |
| Arquivos de banco de dados (*.db, *.sqlite, *.dat) | rw-r--r-- | 644 | Arquivos de banco de dados |
| Arquivos de log (*.log, *.txt) | rw-r--r-- | 644 | Arquivos de log |

## Recursos

### Validação de Entrada

- **Email**: Validação de formato de email compatível com RFC
- **Domínio**: Validação de formato FQDN
- **Callsign**: 3-8 caracteres alfanuméricos
- **Fuso Horário**: Validação de lista de fuso horário do sistema com suporte GMT±X
- **Números de Porta**: Validação de intervalo (1-65535)
- **Frequência**: Validação numérica de 9 dígitos

### Tratamento de Erros

- Verificação abrangente de erros após cada operação
- Falha graciosa com mensagens informativas
- Códigos de saída para uso em scripts
- Registro detalhado de erros

### Segurança

- Requisito de privilégio root
- Propriedade adequada de arquivos (www-data para arquivos web)
- Configurações seguras de permissões
- Suporte a certificado SSL via Let's Encrypt

## Solução de Problemas

### Verificar Log de Instalação

```bash
tail -f installer/log/xlx_install_*.log
```

### Verificar Serviços

```bash
systemctl status xlxd.service
systemctl status xlxecho.service  # se Echo Test instalado
systemctl status xlx_log.service
```

### Verificar Permissões

```bash
ls -la /xlxd/
ls -la /var/www/html/xlxd/
```

### Visualizar Dashboard

- HTTP: http://seu-dominio.com
- HTTPS: https://seu-dominio.com (se SSL instalado)

## Contribuindo

Contribuições são bem-vindas! Por favor, certifique-se de que:

1. O código segue os padrões e convenções existentes
2. As funções estão adequadamente documentadas
3. O registro é abrangente
4. O tratamento de erros é robusto
5. As permissões são apropriadas para os tipos de arquivo

## Autor

Personalizado por Daniel K., PU5KOD

## Licença

Este projeto segue a mesma licença do projeto XLX original.

Para mais informações sobre Refletores XLX, visite: https://xlxbbs.epf.lu/
