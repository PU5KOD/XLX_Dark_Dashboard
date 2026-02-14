#!/bin/bash
################################################################################
# XLX Multiprotocol Amateur Radio Reflector Installer
################################################################################
# A tool to install XLXD, your own D-Star Reflector.
# For more information, please visit https://xlxbbs.epf.lu/
#
# Customized by Daniel K., PU5KOD
# Optimized version with modular design and enhanced logging
################################################################################

set -euo pipefail  # Exit on error, undefined variables, and pipe failures

################################################################################
# SCRIPT INITIALIZATION
################################################################################

# Get script directory
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Source the visual library
if [ -f "$SCRIPT_DIR/cli_visual_unicode.sh" ]; then
    source "$SCRIPT_DIR/cli_visual_unicode.sh"
else
    echo "ERROR: cli_visual_unicode.sh library not found!"
    exit 1
fi

# Setup logging
readonly LOG_DIR="${SCRIPT_DIR}/log"
mkdir -p "$LOG_DIR"
readonly LOGFILE="$LOG_DIR/xlx_install_$(date +%F_%H-%M-%S).log"

# Initialize log
init_log "$LOGFILE" "XLX Reflector Installation Log"

# Redirect all output to log while keeping it on terminal
exec > >(tee -a "$LOGFILE") 2>&1

log_info "$LOGFILE" "Installation script started"
log_info "$LOGFILE" "Script directory: $SCRIPT_DIR"
log_info "$LOGFILE" "Log file: $LOGFILE"

################################################################################
# CONFIGURATION CONSTANTS
################################################################################

# Installation paths
readonly XLXINSTDIR="/usr/src"
readonly XLXDIR="/xlxd"
readonly WEBDIR="/var/www/html/xlxd"

# GitHub repositories (PU5KOD customized versions)
readonly XLXDREPO="https://github.com/PU5KOD/xlxd.git"
readonly XLXECHO="https://github.com/PU5KOD/XLXEcho.git"
readonly XLXDASH="https://github.com/PU5KOD/XLX_Dark_Dashboard.git"

# External URLs
readonly DMRIDURL="http://xlxapi.rlx.lu/api/exportdmr.php"
readonly INFREF="https://xlxbbs.epf.lu/"

# Required packages
readonly REQUIRED_PACKAGES=(
    "git"
    "git-core"
    "make"
    "gcc"
    "g++"
    "pv"
    "sqlite3"
    "apache2"
    "php"
    "libapache2-mod-php"
    "php-cli"
    "php-xml"
    "php-mbstring"
    "php-curl"
    "php-sqlite3"
    "build-essential"
    "vnstat"
    "certbot"
    "python3-certbot-apache"
)

################################################################################
# SYSTEM VALIDATION FUNCTIONS
################################################################################

# Ensure script is run as root
ensure_root() {
    log_info "$LOGFILE" "Checking root privileges"
    
    if ! check_root; then
        msg_warn "This script must be run as root."
        prompt_confirm "Do you want to relaunch with sudo?" "Y"
        read -r answer
        answer=$(echo "${answer:-Y}" | tr '[:lower:]' '[:upper:]')
        
        if [[ "$answer" == "Y" ]]; then
            log_info "$LOGFILE" "Relaunching with sudo"
            exec sudo "$0" "$@"
        else
            log_error "$LOGFILE" "User cancelled root elevation"
            msg_error "Installation cancelled by user."
            exit 1
        fi
    fi
    
    log_success "$LOGFILE" "Running with root privileges"
}

# Check internet connectivity
ensure_internet() {
    log_info "$LOGFILE" "Checking internet connectivity"
    
    if ! check_internet; then
        log_error "$LOGFILE" "No internet connection detected"
        msg_fatal "Unable to proceed, no internet connection detected."
        msg_error "Please check your network connection and try again."
        exit 1
    fi
    
    log_success "$LOGFILE" "Internet connection verified"
}

# Verify Debian-based distribution
verify_distribution() {
    log_info "$LOGFILE" "Checking distribution"
    
    if [ ! -e "/etc/debian_version" ]; then
        log_warning "$LOGFILE" "Not a Debian-based distribution"
        msg_warn "This script has been tested only on Debian-based distributions."
        prompt_confirm "Do you want to continue anyway?" "N"
        read -r answer
        answer=$(echo "${answer:-N}" | tr '[:lower:]' '[:upper:]')
        
        if [[ "$answer" != "Y" ]]; then
            log_error "$LOGFILE" "User cancelled on non-Debian system"
            msg_error "Installation cancelled by user."
            exit 1
        fi
        log_warning "$LOGFILE" "User chose to continue on non-Debian system"
    else
        log_success "$LOGFILE" "Debian-based distribution detected"
    fi
}

# Check for existing installation
check_existing_installation() {
    log_info "$LOGFILE" "Checking for existing XLXD installation"
    
    if [ -e "$XLXDIR/xlxd" ]; then
        log_error "$LOGFILE" "Existing XLXD installation found at $XLXDIR/xlxd"
        echo ""
        show_box "XLXD ALREADY INSTALLED!!!\n\nAn existing installation was detected.\nPlease run the 'uninstaller.sh' first." "$COLOR_RED"
        echo ""
        exit 1
    fi
    
    log_success "$LOGFILE" "No existing installation found"
}

################################################################################
# SYSTEM INFORMATION GATHERING
################################################################################

gather_system_info() {
    log_info "$LOGFILE" "Gathering system information"
    
    LOCAL_IP=$(hostname -I | awk '{print $1}')
    PUBLIC_IP=$(curl -s v4.ident.me)
    NETACT=$(ip -o addr show up | awk '{print $2}' | grep -v lo | head -n1)
    PHPVER=$(php -v | head -n1 | awk '{print $2}' | cut -d. -f1,2)
    
    log_info "$LOGFILE" "Local IP: $LOCAL_IP"
    log_info "$LOGFILE" "Public IP: $PUBLIC_IP"
    log_info "$LOGFILE" "Network interface: $NETACT"
    log_info "$LOGFILE" "PHP version: $PHPVER"
    
    msg_info "System information gathered successfully"
}

################################################################################
# USER INPUT FUNCTIONS
################################################################################

# Prompt for reflector ID
prompt_reflector_id() {
    log_info "$LOGFILE" "Prompting for reflector ID"
    
    while true; do
        msg_info "01. XLX Reflector ID (3 alphanumeric characters)"
        msg_note "Examples: 300, US1, BRA"
        printf "> "
        read -r XRFDIGIT
        XRFDIGIT=$(echo "$XRFDIGIT" | tr '[:lower:]' '[:upper:]')
        
        if [[ "$XRFDIGIT" =~ ^[A-Z0-9]{3}$ ]]; then
            XRFNUM="XLX$XRFDIGIT"
            log_success "$LOGFILE" "Reflector ID set to: $XRFNUM"
            msg_success "Using: $XRFNUM"
            break
        fi
        
        log_warning "$LOGFILE" "Invalid reflector ID entered: $XRFDIGIT"
        msg_warn "Invalid ID. Must be exactly 3 characters (A-Z and/or 0-9)."
    done
    echo ""
}

# Prompt for domain name
prompt_domain() {
    log_info "$LOGFILE" "Prompting for domain name"
    
    while true; do
        msg_info "02. Dashboard FQDN (Fully Qualified Domain Name)"
        msg_note "Example: xlxbra.net"
        printf "> "
        read -r XLXDOMAIN
        XLXDOMAIN=$(echo "$XLXDOMAIN" | tr '[:upper:]' '[:lower:]')
        
        if validate_domain "$XLXDOMAIN"; then
            log_success "$LOGFILE" "Domain set to: $XLXDOMAIN"
            msg_success "Using: $XLXDOMAIN"
            break
        fi
        
        log_warning "$LOGFILE" "Invalid domain entered: $XLXDOMAIN"
        msg_warn "Invalid domain. Must be a valid FQDN (e.g., xlx.example.com)."
    done
    echo ""
}

# Prompt for email
prompt_email() {
    log_info "$LOGFILE" "Prompting for sysop email"
    
    while true; do
        msg_info "03. Sysop e-mail address"
        printf "> "
        read -r EMAIL
        EMAIL=$(echo "$EMAIL" | tr '[:upper:]' '[:lower:]')
        
        if validate_email "$EMAIL"; then
            log_success "$LOGFILE" "Email set to: $EMAIL"
            msg_success "Using: $EMAIL"
            break
        fi
        
        log_warning "$LOGFILE" "Invalid email entered: $EMAIL"
        msg_warn "Invalid email format (e.g., user@domain.com)."
    done
    echo ""
}

# Prompt for callsign
prompt_callsign() {
    log_info "$LOGFILE" "Prompting for callsign"
    
    while true; do
        msg_info "04. Sysop callsign (3-8 alphanumeric characters)"
        printf "> "
        read -r CALLSIGN
        CALLSIGN=$(echo "$CALLSIGN" | tr '[:lower:]' '[:upper:]')
        
        if [[ "$CALLSIGN" =~ ^[A-Z0-9]{3,8}$ ]]; then
            log_success "$LOGFILE" "Callsign set to: $CALLSIGN"
            msg_success "Using: $CALLSIGN"
            break
        fi
        
        log_warning "$LOGFILE" "Invalid callsign entered: $CALLSIGN"
        msg_warn "Invalid callsign. Use only letters and numbers, 3-8 characters."
    done
    echo ""
}

# Prompt for country
prompt_country() {
    log_info "$LOGFILE" "Prompting for country"
    
    while true; do
        msg_info "05. Reflector country name"
        printf "> "
        read -r COUNTRY
        
        if [ -n "$COUNTRY" ]; then
            log_success "$LOGFILE" "Country set to: $COUNTRY"
            msg_success "Using: $COUNTRY"
            break
        fi
        
        log_warning "$LOGFILE" "Empty country name entered"
        msg_warn "This field is mandatory and cannot be empty."
    done
    echo ""
}

# Prompt for timezone
prompt_timezone() {
    log_info "$LOGFILE" "Prompting for timezone"
    
    # Detect current server timezone
    AUTO_TZ=$(timedatectl show --property=Timezone --value 2>/dev/null)
    OFFSET=$(date +%z)
    SIGN=${OFFSET:0:1}
    HH=${OFFSET:1:2}
    MM=${OFFSET:3:2}
    FRIENDLY_OFFSET="UTC${SIGN}${HH}:${MM}"
    
    if [[ -n "$AUTO_TZ" ]]; then
        msg_info "06. Local timezone"
        msg_note "Detected: $AUTO_TZ ($FRIENDLY_OFFSET)"
        msg_gray "Press ENTER to keep it or type another timezone"
    else
        msg_info "06. Local timezone (e.g., America/Sao_Paulo, UTC, GMT-3)"
    fi
    
    while true; do
        printf "> "
        read -r USER_TZ
        
        # Keep detected timezone if user pressed ENTER
        if [[ -z "$USER_TZ" && -n "$AUTO_TZ" ]]; then
            TIMEZONE="$AUTO_TZ"
            TIMEZONE_USE_SYSTEM=1
            log_success "$LOGFILE" "Using detected timezone: $TIMEZONE"
            msg_success "Using: $TIMEZONE ($FRIENDLY_OFFSET)"
            break
        fi
        
        # Validate custom timezone
        TZ_RESOLVED=$(resolve_timezone "$USER_TZ")
        
        if [[ -z "$TZ_RESOLVED" ]]; then
            log_warning "$LOGFILE" "Invalid timezone entered: $USER_TZ"
            msg_warn "Invalid timezone. Please try again."
            continue
        fi
        
        TIMEZONE="$TZ_RESOLVED"
        TIMEZONE_USE_SYSTEM=0
        
        # Get timezone offset
        ZONEFILE=$(readlink -f "/usr/share/zoneinfo/$TIMEZONE")
        REAL_OFFSET=$(TZ="$ZONEFILE" date +%z)
        SIGN=${REAL_OFFSET:0:1}
        HH=${REAL_OFFSET:1:2}
        MM=${REAL_OFFSET:3:2}
        DISPLAY_OFFSET="UTC${SIGN}${HH}:${MM}"
        
        if [[ "$REAL_OFFSET" == "+0000" ]]; then
            FINAL_DISPLAY="$TIMEZONE"
        else
            FINAL_DISPLAY="$TIMEZONE ($DISPLAY_OFFSET)"
        fi
        
        log_info "$LOGFILE" "Custom timezone selected: $FINAL_DISPLAY"
        msg_highlight "Selected timezone: $FINAL_DISPLAY"
        
        # Warn about inverted GMT notation
        if [[ "$TIMEZONE" =~ ^Etc/GMT ]]; then
            msg_caution "Linux POSIX GMT zones use inverted sign notation"
            msg_caution "$TIMEZONE (inverted) = $DISPLAY_OFFSET (real)"
        fi
        
        prompt_confirm "Confirm this timezone?" "Y"
        read -r CONFIRM_TZ
        CONFIRM_TZ=$(echo "${CONFIRM_TZ:-Y}" | tr '[:lower:]' '[:upper:]')
        
        if [[ "$CONFIRM_TZ" == "Y" ]]; then
            log_success "$LOGFILE" "Timezone confirmed: $FINAL_DISPLAY"
            msg_success "Using: $FINAL_DISPLAY"
            break
        fi
    done
    echo ""
}

# Resolve timezone from user input
resolve_timezone() {
    local input="$1"
    local match
    
    # Case-insensitive match against system timezone list
    match=$(timedatectl list-timezones | grep -iFx "$input" 2>/dev/null || true)
    [[ -n "$match" ]] && { echo "$match"; return 0; }
    
    # Handle GMT±X format
    if [[ "$input" =~ ^[Gg][Mm][Tt]([+-]?)([0-9]{1,2})$ ]]; then
        local sign="${BASH_REMATCH[1]}"
        local num="${BASH_REMATCH[2]}"
        local candidate
        
        # POSIX inverted GMT logic
        if [[ "$sign" == "-" ]]; then
            candidate="Etc/GMT+${num}"
        elif [[ "$sign" == "+" ]]; then
            candidate="Etc/GMT-${num}"
        else
            candidate="Etc/GMT"
        fi
        
        if [[ -f "/usr/share/zoneinfo/$candidate" ]]; then
            echo "$candidate"
            return 0
        fi
    fi
    
    return 1
}

# Prompt for XLX list comment
prompt_comment() {
    log_info "$LOGFILE" "Prompting for XLX list comment"
    
    local default="$XRFNUM Multiprotocol Reflector by $CALLSIGN, info: $EMAIL"
    
    while true; do
        msg_info "07. Comment for XLX Reflectors list (max 100 characters)"
        msg_note "Default: $default"
        msg_gray "Press ENTER to accept"
        printf "> "
        read -r COMMENT
        COMMENT=${COMMENT:-"$default"}
        
        if [ ${#COMMENT} -le 100 ]; then
            log_success "$LOGFILE" "Comment set to: $COMMENT"
            msg_success "Using: $COMMENT"
            break
        fi
        
        log_warning "$LOGFILE" "Comment too long: ${#COMMENT} characters"
        msg_warn "Comment must be max 100 characters."
    done
    echo ""
}

# Prompt for dashboard header
prompt_header() {
    log_info "$LOGFILE" "Prompting for dashboard header"
    
    local default="$XRFNUM by $CALLSIGN"
    
    msg_info "08. Custom text for dashboard tab (preferably short)"
    msg_note "Default: $default"
    msg_gray "Press ENTER to accept"
    printf "> "
    read -r HEADER
    HEADER=${HEADER:-"$default"}
    
    log_success "$LOGFILE" "Header set to: $HEADER"
    msg_success "Using: $HEADER"
    echo ""
}

# Prompt for dashboard footer
prompt_footer() {
    log_info "$LOGFILE" "Prompting for dashboard footer"
    
    local default="Provided by $CALLSIGN, info: $EMAIL"
    
    while true; do
        msg_info "09. Custom text for dashboard footer (max 100 characters)"
        msg_note "Default: $default"
        msg_gray "Press ENTER to accept"
        printf "> "
        read -r FOOTER
        FOOTER=${FOOTER:-"$default"}
        
        if [ ${#FOOTER} -le 100 ]; then
            log_success "$LOGFILE" "Footer set to: $FOOTER"
            msg_success "Using: $FOOTER"
            break
        fi
        
        log_warning "$LOGFILE" "Footer too long: ${#FOOTER} characters"
        msg_warn "Footer must be max 100 characters."
    done
    echo ""
}

# Prompt for SSL installation
prompt_ssl() {
    log_info "$LOGFILE" "Prompting for SSL certificate"
    
    while true; do
        msg_info "10. Create SSL certificate (HTTPS) for dashboard?"
        msg_note "Default: Y"
        msg_gray "Press ENTER to accept"
        printf "> "
        read -r INSTALL_SSL
        INSTALL_SSL=$(echo "${INSTALL_SSL:-Y}" | tr '[:lower:]' '[:upper:]')
        
        if [[ "$INSTALL_SSL" == "Y" || "$INSTALL_SSL" == "N" ]]; then
            log_success "$LOGFILE" "SSL installation: $INSTALL_SSL"
            msg_success "Using: $INSTALL_SSL"
            break
        fi
        
        msg_warn "Please enter 'Y' or 'N'."
    done
    echo ""
}

# Prompt for Echo Test installation
prompt_echo() {
    log_info "$LOGFILE" "Prompting for Echo Test"
    
    while true; do
        msg_info "11. Install Echo Test on module E?"
        msg_note "Default: Y"
        msg_gray "Press ENTER to accept"
        printf "> "
        read -r INSTALL_ECHO
        INSTALL_ECHO=$(echo "${INSTALL_ECHO:-Y}" | tr '[:lower:]' '[:upper:]')
        
        if [[ "$INSTALL_ECHO" == "Y" || "$INSTALL_ECHO" == "N" ]]; then
            log_success "$LOGFILE" "Echo Test installation: $INSTALL_ECHO"
            msg_success "Using: $INSTALL_ECHO"
            break
        fi
        
        msg_warn "Please enter 'Y' or 'N'."
    done
    echo ""
}

# Prompt for number of modules
prompt_modules() {
    log_info "$LOGFILE" "Prompting for number of modules"
    
    local min_modules=1
    if [ "$INSTALL_ECHO" == "Y" ]; then
        min_modules=5
    fi
    
    while true; do
        msg_info "12. Number of active modules for DStar Reflector ($min_modules - 26)"
        msg_note "Default: 5"
        msg_gray "Press ENTER to accept"
        printf "> "
        read -r MODQTD
        MODQTD=${MODQTD:-5}
        
        if [[ "$MODQTD" =~ ^[0-9]+$ && "$MODQTD" -ge "$min_modules" && "$MODQTD" -le 26 ]]; then
            # Generate module list (A-Z up to MODQTD)
            MODLIST=$(echo {A..Z} | tr -d ' ' | head -c "$MODQTD")
            log_success "$LOGFILE" "Modules set to: $MODQTD ($MODLIST)"
            msg_success "Using: $MODQTD modules ($MODLIST)"
            break
        fi
        
        log_warning "$LOGFILE" "Invalid module count: $MODQTD"
        msg_warn "Must be a number between $min_modules and 26."
    done
    echo ""
}

# Prompt for YSF port
prompt_ysf_port() {
    log_info "$LOGFILE" "Prompting for YSF port"
    
    while true; do
        msg_info "13. YSF Reflector UDP port number (1-65535)"
        msg_note "Default: 42000"
        msg_gray "Press ENTER to accept"
        printf "> "
        read -r YSFPORT
        YSFPORT=${YSFPORT:-42000}
        
        if validate_port "$YSFPORT"; then
            log_success "$LOGFILE" "YSF port set to: $YSFPORT"
            msg_success "Using: $YSFPORT"
            break
        fi
        
        log_warning "$LOGFILE" "Invalid port: $YSFPORT"
        msg_warn "Must be a number between 1 and 65535."
    done
    echo ""
}

# Prompt for YSF frequency
prompt_ysf_frequency() {
    log_info "$LOGFILE" "Prompting for YSF frequency"
    
    while true; do
        msg_info "14. YSF Wires-X frequency in Hertz (9 digits)"
        msg_note "Default: 433125000"
        msg_gray "Press ENTER to accept"
        printf "> "
        read -r YSFFREQ
        YSFFREQ=${YSFFREQ:-433125000}
        
        if [[ "$YSFFREQ" =~ ^[0-9]{9}$ ]]; then
            log_success "$LOGFILE" "YSF frequency set to: $YSFFREQ"
            msg_success "Using: $YSFFREQ"
            break
        fi
        
        log_warning "$LOGFILE" "Invalid frequency: $YSFFREQ"
        msg_warn "Must be exactly 9 numeric digits (e.g., 433125000)."
    done
    echo ""
}

# Prompt for YSF auto-link
prompt_ysf_autolink() {
    log_info "$LOGFILE" "Prompting for YSF auto-link"
    
    while true; do
        msg_info "15. Auto-link YSF to a module?"
        msg_note "Default: Y"
        msg_gray "Press ENTER to accept"
        printf "> "
        read -r AUTOLINK_USER
        AUTOLINK_USER=$(echo "${AUTOLINK_USER:-Y}" | tr '[:lower:]' '[:upper:]')
        
        if [[ "$AUTOLINK_USER" == "Y" || "$AUTOLINK_USER" == "N" ]]; then
            if [ "$AUTOLINK_USER" == "Y" ]; then
                AUTOLINK=1
            else
                AUTOLINK=0
            fi
            log_success "$LOGFILE" "YSF auto-link: $AUTOLINK_USER"
            msg_success "Using: $AUTOLINK_USER"
            break
        fi
        
        msg_warn "Please enter 'Y' or 'N'."
    done
    echo ""
}

# Prompt for YSF auto-link module
prompt_ysf_module() {
    log_info "$LOGFILE" "Prompting for YSF auto-link module"
    
    if [[ "$AUTOLINK" -ne 1 ]]; then
        return
    fi
    
    # Determine smart suggestion
    local suggested="C"
    if (( MODQTD < 3 )); then
        if (( MODQTD == 2 )); then
            suggested="B"
        else
            suggested="A"
        fi
    fi
    
    # Build valid modules array
    local -a valid_modules=()
    for ((i=0; i<MODQTD; i++)); do
        valid_modules+=("$(printf "\\$(printf '%03o' $((65 + i)))")")
    done
    
    local last_letter="${valid_modules[-1]}"
    
    msg_info "16. Module to auto-link YSF (A to $last_letter)"
    msg_note "Default: $suggested"
    msg_gray "Press ENTER to accept"
    
    while true; do
        printf "> "
        read -r MODAUTO
        MODAUTO=${MODAUTO:-$suggested}
        MODAUTO=$(echo "$MODAUTO" | tr '[:lower:]' '[:upper:]')
        
        if [[ " ${valid_modules[@]} " =~ " $MODAUTO " ]]; then
            log_success "$LOGFILE" "YSF auto-link module: $MODAUTO"
            msg_success "Using: $MODAUTO"
            break
        fi
        
        log_warning "$LOGFILE" "Invalid module: $MODAUTO"
        msg_warn "Invalid module. Choose from A to $last_letter."
    done
    echo ""
}

# Display all settings for confirmation
confirm_settings() {
    log_info "$LOGFILE" "Displaying settings for user confirmation"
    
    show_header "PLEASE REVIEW YOUR SETTINGS"
    
    msg_highlight "01. Reflector ID:        $XRFNUM"
    msg_highlight "02. FQDN:                $XLXDOMAIN"
    msg_highlight "03. E-mail:              $EMAIL"
    msg_highlight "04. Callsign:            $CALLSIGN"
    msg_highlight "05. Country:             $COUNTRY"
    msg_highlight "06. Time Zone:           $TIMEZONE"
    msg_highlight "07. XLX list comment:    $COMMENT"
    msg_highlight "08. Tab page text:       $HEADER"
    msg_highlight "09. Dashboard footnote:  $FOOTER"
    msg_highlight "10. SSL certification:   $INSTALL_SSL"
    msg_highlight "11. Echo Test:           $INSTALL_ECHO"
    msg_highlight "12. Modules:             $MODQTD ($MODLIST)"
    msg_highlight "13. YSF UDP Port:        $YSFPORT"
    msg_highlight "14. YSF frequency:       $YSFFREQ"
    msg_highlight "15. YSF Auto-link:       $AUTOLINK_USER"
    if [ "$AUTOLINK" -eq 1 ]; then
        msg_highlight "16. YSF module:          $MODAUTO"
    fi
    
    echo ""
    line_minor
    echo ""
    
    while true; do
        prompt_confirm "Are these settings correct?" "YES"
        read -r CONFIRM
        CONFIRM=${CONFIRM:-YES}
        CONFIRM=$(echo "$CONFIRM" | tr '[:lower:]' '[:upper:]')
        
        if [[ "$CONFIRM" == "YES" || "$CONFIRM" == "NO" ]]; then
            break
        fi
        
        msg_warn "Please enter 'YES' or 'NO'."
    done
    
    if [ "$CONFIRM" != "YES" ]; then
        log_error "$LOGFILE" "User rejected settings"
        msg_error "Installation aborted by user."
        exit 1
    fi
    
    log_success "$LOGFILE" "User confirmed all settings"
    msg_success "Settings confirmed! Starting installation..."
    echo ""
}

################################################################################
# INSTALLATION FUNCTIONS
################################################################################

# Update system packages
update_system() {
    show_subheader "UPDATING OPERATING SYSTEM"
    log_info "$LOGFILE" "Starting system update"
    
    show_task "Updating package lists"
    if apt update >> "$LOGFILE" 2>&1; then
        log_success "$LOGFILE" "Package lists updated"
        msg_success "Package lists updated"
    else
        log_error "$LOGFILE" "Failed to update package lists"
        msg_fatal "Failed to update package lists"
        exit 1
    fi
    
    show_task "Upgrading system packages"
    if apt full-upgrade -y >> "$LOGFILE" 2>&1; then
        log_success "$LOGFILE" "System upgraded"
        msg_success "System upgraded successfully"
    else
        log_error "$LOGFILE" "Failed to upgrade system"
        msg_fatal "Failed to upgrade system"
        exit 1
    fi
    
    # Apply timezone if not using system default
    if [[ "${TIMEZONE_USE_SYSTEM:-0}" -eq 0 ]]; then
        show_task "Applying timezone: $TIMEZONE"
        if timedatectl set-timezone "$TIMEZONE" >> "$LOGFILE" 2>&1; then
            log_success "$LOGFILE" "Timezone set to: $TIMEZONE"
            msg_success "Timezone applied"
        else
            log_error "$LOGFILE" "Failed to set timezone"
            msg_error "Failed to apply timezone"
        fi
    else
        log_info "$LOGFILE" "Using system timezone: $TIMEZONE"
        msg_info "Using system timezone: $TIMEZONE"
    fi
    
    echo ""
}

# Install required dependencies
install_dependencies() {
    show_subheader "INSTALLING DEPENDENCIES"
    log_info "$LOGFILE" "Starting dependency installation"
    
    # Create installation directory
    mkdir -p "$XLXINSTDIR"
    log_info "$LOGFILE" "Created installation directory: $XLXINSTDIR"
    
    show_task "Installing required packages"
    log_info "$LOGFILE" "Installing: ${REQUIRED_PACKAGES[*]}"
    
    if apt install -y "${REQUIRED_PACKAGES[@]}" >> "$LOGFILE" 2>&1; then
        log_success "$LOGFILE" "All dependencies installed"
        msg_success "Dependencies installed successfully"
    else
        log_error "$LOGFILE" "Failed to install dependencies"
        msg_fatal "Failed to install dependencies"
        exit 1
    fi
    
    echo ""
}

# Download and compile XLX
install_xlx() {
    show_subheader "DOWNLOADING AND COMPILING XLX"
    log_info "$LOGFILE" "Starting XLX installation"
    
    cd "$XLXINSTDIR"
    
    # Clone repository
    show_download "XLX repository from $XLXDREPO"
    log_info "$LOGFILE" "Cloning XLX repository"
    
    if git clone "$XLXDREPO" >> "$LOGFILE" 2>&1; then
        log_success "$LOGFILE" "XLX repository cloned"
        msg_success "Repository cloned"
    else
        log_error "$LOGFILE" "Failed to clone XLX repository"
        msg_fatal "Failed to clone repository"
        exit 1
    fi
    
    cd "$XLXINSTDIR/xlxd/src"
    
    # Clean previous builds
    show_task "Cleaning previous builds"
    make clean >> "$LOGFILE" 2>&1
    log_info "$LOGFILE" "Previous builds cleaned"
    
    # Apply customizations
    show_config "XLX settings"
    log_info "$LOGFILE" "Applying customizations to main.h"
    
    local mainconfig="$XLXINSTDIR/xlxd/src/main.h"
    
    sed -i "s|\(NB_OF_MODULES\s*\)\([0-9]*\)|\1$MODQTD|g" "$mainconfig"
    log_info "$LOGFILE" "Set NB_OF_MODULES to $MODQTD"
    
    sed -i "s|\(YSF_PORT\s*\)\([0-9]*\)|\1$YSFPORT|g" "$mainconfig"
    log_info "$LOGFILE" "Set YSF_PORT to $YSFPORT"
    
    sed -i "s|\(YSF_DEFAULT_NODE_TX_FREQ\s*\)\([0-9]*\)|\1$YSFFREQ|g" "$mainconfig"
    sed -i "s|\(YSF_DEFAULT_NODE_RX_FREQ\s*\)\([0-9]*\)|\1$YSFFREQ|g" "$mainconfig"
    log_info "$LOGFILE" "Set YSF frequencies to $YSFFREQ"
    
    sed -i "s|\(YSF_AUTOLINK_ENABLE\s*\)\([0-9]*\)|\1$AUTOLINK|g" "$mainconfig"
    log_info "$LOGFILE" "Set YSF_AUTOLINK_ENABLE to $AUTOLINK"
    
    if [ "$AUTOLINK" -eq 1 ]; then
        sed -i "s|\(YSF_AUTOLINK_MODULE\s*\)'\([A-Z]*\)'|\1'$MODAUTO'|g" "$mainconfig"
        log_info "$LOGFILE" "Set YSF_AUTOLINK_MODULE to $MODAUTO"
    fi
    
    msg_success "Customizations applied"
    
    # Compile
    show_compile "XLX"
    log_info "$LOGFILE" "Starting compilation"
    
    if make >> "$LOGFILE" 2>&1; then
        log_success "$LOGFILE" "XLX compiled successfully"
        msg_success "Compilation successful"
    else
        log_error "$LOGFILE" "XLX compilation failed"
        msg_fatal "Compilation failed"
        exit 1
    fi
    
    # Install
    show_task "Installing XLX"
    if make install >> "$LOGFILE" 2>&1; then
        log_success "$LOGFILE" "XLX installed"
        msg_success "XLX installed"
    else
        log_error "$LOGFILE" "XLX installation failed"
        msg_fatal "Installation failed"
        exit 1
    fi
    
    # Verify compilation
    if [ -e "$XLXINSTDIR/xlxd/src/xlxd" ]; then
        log_success "$LOGFILE" "XLX binary created successfully"
        show_box "COMPILATION SUCCESSFUL!" "$COLOR_GREEN"
    else
        log_error "$LOGFILE" "XLX binary not found after compilation"
        show_box "COMPILATION FAILED!\nCheck the log for errors." "$COLOR_RED"
        exit 1
    fi
    
    echo ""
}

# Setup XLX configuration files
setup_xlx_files() {
    show_subheader "CONFIGURING XLX COMPONENTS"
    log_info "$LOGFILE" "Setting up XLX configuration files"
    
    # Create directories
    mkdir -p "$XLXDIR"
    mkdir -p "$WEBDIR"
    log_info "$LOGFILE" "Created directories: $XLXDIR, $WEBDIR"
    
    # Create log file
    touch /var/log/xlxd.xml
    log_info "$LOGFILE" "Created log file: /var/log/xlxd.xml"
    
    # Download DMR ID file
    show_download "DMR ID database"
    log_info "$LOGFILE" "Downloading DMR ID file from $DMRIDURL"
    
    local file_size
    file_size=$(wget --spider --server-response "$DMRIDURL" 2>&1 | grep -i Content-Length | awk '{print $2}')
    
    if [ -n "$file_size" ]; then
        log_info "$LOGFILE" "DMR ID file size: $file_size bytes"
        wget -q -O - "$DMRIDURL" | pv -p -t -r -b -s "$file_size" > /xlxd/dmrid.dat 2>> "$LOGFILE"
    else
        log_info "$LOGFILE" "DMR ID file size unknown, downloading without progress"
        wget -q -O - "$DMRIDURL" | pv -p -t -r -b > /xlxd/dmrid.dat 2>> "$LOGFILE"
    fi
    
    if [ -s /xlxd/dmrid.dat ]; then
        log_success "$LOGFILE" "DMR ID file downloaded"
        msg_success "DMR ID database downloaded"
    else
        log_error "$LOGFILE" "DMR ID file empty or failed to download"
        msg_error "Failed to download DMR ID file"
    fi
    
    # Setup custom XLX logging
    if [ -d "$SCRIPT_DIR/templates" ]; then
        show_config "Custom XLX logging"
        log_info "$LOGFILE" "Setting up custom XLX logging"
        
        cp "$SCRIPT_DIR/templates/xlx_log.service" /etc/systemd/system/
        cp "$SCRIPT_DIR/templates/xlx_log.sh" /usr/local/bin/
        cp "$SCRIPT_DIR/templates/xlx_logrotate.conf" /etc/logrotate.d/
        
        set_systemd_permissions /etc/systemd/system/xlx_log.service "$LOGFILE"
        chmod 755 /usr/local/bin/xlx_log.sh
        chmod 644 /etc/logrotate.d/xlx_logrotate.conf
        
        log_success "$LOGFILE" "Custom logging configured"
        msg_success "Custom logging configured"
    else
        log_warning "$LOGFILE" "Templates directory not found, skipping custom logging"
        msg_warn "Templates directory not found, skipping custom logging"
    fi
    
    # Configure terminal settings
    show_config "XLX terminal settings"
    log_info "$LOGFILE" "Configuring xlxd.terminal"
    
    local termxlx="/xlxd/xlxd.terminal"
    sed -i "s|#address|address $PUBLIC_IP|g" "$termxlx"
    sed -i "s|#modules|modules $MODLIST|g" "$termxlx"
    log_success "$LOGFILE" "Terminal settings configured"
    msg_success "Terminal settings configured"
    
    # Setup systemd service
    show_config "XLX systemd service"
    log_info "$LOGFILE" "Configuring xlxd.service"
    
    cp "$XLXINSTDIR/xlxd/scripts/xlxd.service" /etc/systemd/system/
    set_systemd_permissions /etc/systemd/system/xlxd.service "$LOGFILE"
    sed -i "s|XLXXXX 172.23.127.100 127.0.0.1|$XRFNUM $LOCAL_IP 127.0.0.1|g" /etc/systemd/system/xlxd.service
    log_success "$LOGFILE" "Systemd service configured"
    msg_success "Systemd service configured"
    
    # Configure Echo Test interlink
    if [ "$INSTALL_ECHO" == "N" ]; then
        log_info "$LOGFILE" "Echo Test not installed, commenting out interlink"
        sed -i 's|^ECHO 127.0.0.1 E|#ECHO 127.0.0.1 E|' /xlxd/xlxd.interlink
        msg_info "Echo Test interlink disabled"
    fi
    
    # Setup database updates
    show_config "Database update schedule"
    log_info "$LOGFILE" "Setting up database update schedule"
    
    if command_exists crontab; then
        log_info "$LOGFILE" "Using crontab for database updates"
        (crontab -l 2>/dev/null; echo "0 3 * * * wget -O /xlxd/users_db/user.csv https://radioid.net/static/user.csv && php /xlxd/users_db/create_user_db.php") | crontab -
        msg_success "Database updates scheduled via crontab"
    else
        log_info "$LOGFILE" "Crontab not found, using systemd timers"
        
        if [ -d "$SCRIPT_DIR/templates" ]; then
            cp "$SCRIPT_DIR/templates/update_XLX_db.service" /etc/systemd/system/
            cp "$SCRIPT_DIR/templates/update_XLX_db.timer" /etc/systemd/system/
            set_systemd_permissions /etc/systemd/system/update_XLX_db.service "$LOGFILE"
            set_systemd_permissions /etc/systemd/system/update_XLX_db.timer "$LOGFILE"
            systemctl daemon-reload
            systemctl enable --now update_XLX_db.timer
            msg_success "Database updates scheduled via systemd"
        else
            log_warning "$LOGFILE" "Templates not found, database updates not scheduled"
            msg_warn "Database updates not scheduled"
        fi
    fi
    
    echo ""
}

# Install Echo Test server
install_echo_test() {
    if [ "$INSTALL_ECHO" != "Y" ]; then
        log_info "$LOGFILE" "Echo Test installation skipped"
        return
    fi
    
    show_subheader "INSTALLING ECHO TEST SERVER"
    log_info "$LOGFILE" "Starting Echo Test installation"
    
    cd "$XLXINSTDIR"
    
    show_download "Echo Test repository"
    if git clone "$XLXECHO" >> "$LOGFILE" 2>&1; then
        log_success "$LOGFILE" "Echo Test repository cloned"
        msg_success "Repository cloned"
    else
        log_error "$LOGFILE" "Failed to clone Echo Test repository"
        msg_error "Failed to clone repository"
        return
    fi
    
    cd XLXEcho/
    
    show_compile "Echo Test"
    if gcc -o xlxecho xlxecho.c >> "$LOGFILE" 2>&1; then
        log_success "$LOGFILE" "Echo Test compiled"
        msg_success "Compilation successful"
    else
        log_error "$LOGFILE" "Echo Test compilation failed"
        msg_error "Compilation failed"
        return
    fi
    
    cp xlxecho /xlxd/
    cp "$XLXINSTDIR/xlxd/scripts/xlxecho.service" /etc/systemd/system/
    set_systemd_permissions /etc/systemd/system/xlxecho.service "$LOGFILE"
    
    log_success "$LOGFILE" "Echo Test server installed"
    msg_success "Echo Test server installed"
    echo ""
}

# Install and configure dashboard
install_dashboard() {
    show_subheader "INSTALLING DASHBOARD"
    log_info "$LOGFILE" "Starting dashboard installation"
    
    cd "$XLXINSTDIR"
    
    show_download "Dashboard from $XLXDASH"
    if git clone "$XLXDASH" >> "$LOGFILE" 2>&1; then
        log_success "$LOGFILE" "Dashboard repository cloned"
        msg_success "Repository cloned"
    else
        log_error "$LOGFILE" "Failed to clone dashboard repository"
        msg_fatal "Failed to clone repository"
        exit 1
    fi
    
    show_task "Copying dashboard files"
    cp -R "$XLXINSTDIR/XLX_Dark_Dashboard/"* "$WEBDIR/"
    log_success "$LOGFILE" "Dashboard files copied to $WEBDIR"
    msg_success "Files copied"
    
    show_config "Dashboard settings"
    log_info "$LOGFILE" "Applying dashboard customizations"
    
    local xlxconfig="$WEBDIR/pgs/config.inc.php"
    
    sed -i "s|your_email|$EMAIL|g" "$xlxconfig"
    sed -i "s|LX1IQ|$CALLSIGN|g" "$xlxconfig"
    sed -i "s|MODQTD|$MODQTD|g" "$xlxconfig"
    sed -i "s|custom_header|$HEADER|g" "$xlxconfig"
    sed -i "s|custom_footnote|$FOOTER|g" "$xlxconfig"
    sed -i "s#http://your_dashboard#http://$XLXDOMAIN#g" "$xlxconfig"
    sed -i "s|your_country|$COUNTRY|g" "$xlxconfig"
    sed -i "s|your_comment|$COMMENT|g" "$xlxconfig"
    sed -i "s|netact|$NETACT|g" "$xlxconfig"
    
    log_success "$LOGFILE" "Dashboard customizations applied"
    msg_success "Customizations applied"
    
    # Configure Apache
    show_config "Apache web server"
    log_info "$LOGFILE" "Configuring Apache"
    
    if [ -f "$SCRIPT_DIR/templates/apache.tbd.conf" ]; then
        cp "$SCRIPT_DIR/templates/apache.tbd.conf" /etc/apache2/sites-available/"$XLXDOMAIN".conf
        sed -i "s|apache.tbd|$XLXDOMAIN|g" /etc/apache2/sites-available/"$XLXDOMAIN".conf
        sed -i "s#ysf-xlxd#html/xlxd#g" /etc/apache2/sites-available/"$XLXDOMAIN".conf
        log_success "$LOGFILE" "Apache site configuration created"
    else
        log_warning "$LOGFILE" "Apache template not found"
        msg_warn "Apache template not found, manual configuration may be needed"
    fi
    
    # Configure PHP timezone
    sed -i "s|^;\?date\.timezone\s*=.*|date.timezone = \"$TIMEZONE\"|" /etc/php/"$PHPVER"/apache2/php.ini
    log_success "$LOGFILE" "PHP timezone configured"
    
    # Determine Apache user
    local apache_user
    apache_user=$(ps aux | grep -E '[a]pache|[h]ttpd' | grep -v root | head -1 | awk '{print $1}')
    if [ -z "$apache_user" ]; then
        apache_user="www-data"
    fi
    log_info "$LOGFILE" "Apache user detected: $apache_user"
    
    # Move users_db and set up permissions
    show_task "Setting up user database"
    mv "$WEBDIR/users_db/" /xlxd/
    log_info "$LOGFILE" "User database moved to /xlxd/users_db"
    
    show_task "Setting file permissions"
    log_info "$LOGFILE" "Setting ownership and permissions"
    
    chown -R "$apache_user:$apache_user" /var/log/xlxd.xml
    chown -R "$apache_user:$apache_user" "$WEBDIR/"
    chown -R "$apache_user:$apache_user" /xlxd/
    
    set_file_permissions "$XLXDIR" "$LOGFILE"
    set_web_permissions "$WEBDIR" "$apache_user" "$LOGFILE"
    
    msg_success "Permissions configured"
    
    # Initialize user database
    show_task "Initializing user database"
    if /bin/bash /xlxd/users_db/update_db.sh >> "$LOGFILE" 2>&1; then
        log_success "$LOGFILE" "User database initialized"
        msg_success "User database initialized"
    else
        log_error "$LOGFILE" "Failed to initialize user database"
        msg_error "Failed to initialize user database"
    fi
    
    # Enable Apache site
    show_task "Enabling Apache site"
    /usr/sbin/a2ensite "$XLXDOMAIN".conf >> "$LOGFILE" 2>&1
    /usr/sbin/a2dissite 000-default >> "$LOGFILE" 2>&1
    
    systemctl stop apache2 >> "$LOGFILE" 2>&1
    systemctl start apache2 >> "$LOGFILE" 2>&1
    systemctl daemon-reload
    
    log_success "$LOGFILE" "Apache configured and restarted"
    msg_success "Apache web server configured"
    
    echo ""
}

# Install SSL certificate
install_ssl() {
    if [ "$INSTALL_SSL" != "Y" ]; then
        log_info "$LOGFILE" "SSL installation skipped"
        return
    fi
    
    show_subheader "CONFIGURING SSL CERTIFICATE"
    log_info "$LOGFILE" "Starting SSL certificate installation"
    
    show_task "Requesting SSL certificate from Let's Encrypt"
    if certbot --apache -d "$XLXDOMAIN" -n --agree-tos -m "$EMAIL" >> "$LOGFILE" 2>&1; then
        log_success "$LOGFILE" "SSL certificate installed"
        msg_success "SSL certificate installed successfully"
    else
        log_error "$LOGFILE" "SSL certificate installation failed"
        msg_error "SSL certificate installation failed"
        msg_warn "You may need to configure SSL manually"
    fi
    
    echo ""
}

# Start XLX services
start_services() {
    show_subheader "STARTING $XRFNUM REFLECTOR"
    log_info "$LOGFILE" "Starting XLX services"
    
    show_task "Starting XLXD service"
    systemctl enable --now xlxd.service >> "$LOGFILE" 2>&1 &
    local pid=$!
    countdown 10 "Initializing $XRFNUM"
    wait $pid
    log_success "$LOGFILE" "XLXD service started"
    msg_success "XLXD service started"
    
    if [ -f /etc/systemd/system/xlx_log.service ]; then
        show_task "Starting XLX logging service"
        systemctl enable --now xlx_log.service >> "$LOGFILE" 2>&1 &
        pid=$!
        countdown 5 "Initializing logging"
        wait $pid
        log_success "$LOGFILE" "XLX logging service started"
        msg_success "Logging service started"
    fi
    
    if [ "$INSTALL_ECHO" == "Y" ]; then
        show_task "Starting Echo Test service"
        systemctl enable --now xlxecho.service >> "$LOGFILE" 2>&1 &
        pid=$!
        countdown 5 "Initializing Echo Test"
        wait $pid
        log_success "$LOGFILE" "Echo Test service started"
        msg_success "Echo Test service started"
    fi
    
    echo ""
    msg_success "All services started successfully"
    echo ""
}

# Display final information
show_completion() {
    log_success "$LOGFILE" "Installation completed successfully"
    
    show_box "REFLECTOR INSTALLED SUCCESSFULLY!" "$COLOR_GREEN"
    
    echo ""
    msg_success "Your Reflector $XRFNUM is now installed and running!"
    echo ""
    
    msg_info "Important Information:"
    echo ""
    msg_highlight "• For Public Reflectors:"
    msg_note "  If your XLX number is available, it should be listed on the"
    msg_note "  public list within an hour. To keep it private, set"
    msg_note "  'callinghome' to [false] in the configuration."
    echo ""
    msg_highlight "• Configuration:"
    msg_note "  Many settings can be changed in: $WEBDIR/pgs/config.inc.php"
    echo ""
    msg_highlight "• Dashboard Access:"
    if [ "$INSTALL_SSL" == "Y" ]; then
        msg_note "  Your $XRFNUM dashboard: https://$XLXDOMAIN"
    else
        msg_note "  Your $XRFNUM dashboard: http://$XLXDOMAIN"
    fi
    echo ""
    msg_highlight "• More Information:"
    msg_note "  Visit: $INFREF"
    echo ""
    
    line_section
    
    log_info "$LOGFILE" "Installation log saved to: $LOGFILE"
}

################################################################################
# MAIN EXECUTION
################################################################################

main() {
    # Clear screen and show welcome
    clear
    show_header "XLX MULTIPROTOCOL AMATEUR RADIO REFLECTOR INSTALLER"
    
    msg_info "Welcome to the XLX Reflector installation wizard!"
    msg_note "This script will guide you through the installation process."
    echo ""
    line_minor
    echo ""
    
    # System validation
    ensure_root "$@"
    ensure_internet
    verify_distribution
    check_existing_installation
    gather_system_info
    
    # User input
    show_header "REFLECTOR CONFIGURATION"
    msg_info "Please provide the following information."
    msg_note "Press ENTER to accept default values where applicable."
    echo ""
    line_minor
    echo ""
    
    prompt_reflector_id
    prompt_domain
    prompt_email
    prompt_callsign
    prompt_country
    prompt_timezone
    prompt_comment
    prompt_header
    prompt_footer
    prompt_ssl
    prompt_echo
    prompt_modules
    prompt_ysf_port
    prompt_ysf_frequency
    prompt_ysf_autolink
    prompt_ysf_module
    
    # Confirmation
    confirm_settings
    
    # Installation
    update_system
    install_dependencies
    install_xlx
    setup_xlx_files
    install_echo_test
    install_dashboard
    install_ssl
    start_services
    
    # Completion
    show_completion
}

# Execute main function
main "$@"
