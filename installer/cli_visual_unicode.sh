#!/bin/bash
# ================================================================================
# CLI Visual Unicode Library
# ================================================================================
# A comprehensive library for creating beautiful, colorful terminal interfaces
# with Unicode support, semantic functions, and logging capabilities.
#
# Author: PU5KOD
# Version: 1.0.0
# ================================================================================

# ================================================================================
# TERMINAL SETTINGS
# ================================================================================

# Set the fixed character limit for display
readonly MAX_WIDTH=100

# Get terminal width, capped at MAX_WIDTH
get_terminal_width() {
    local cols
    cols=$(tput cols 2>/dev/null || echo "$MAX_WIDTH")
    echo $(( cols < MAX_WIDTH ? cols : MAX_WIDTH ))
}

# Initialize width
TERM_WIDTH=$(get_terminal_width)

# ================================================================================
# COLOR PALETTE
# ================================================================================

# Basic colors
readonly NC='\033[0m'              # No Color / Reset
readonly BOLD='\033[1m'            # Bold
readonly DIM='\033[2m'             # Dim
readonly UNDERLINE='\033[4m'       # Underline
readonly BLINK='\033[5m'           # Blink
readonly REVERSE='\033[7m'         # Reverse
readonly HIDDEN='\033[8m'          # Hidden

# Standard colors (256-color palette)
readonly COLOR_BLUE='\033[38;5;39m'          # Information
readonly COLOR_BLUE_BRIGHT='\033[1;34m'      # Bright blue
readonly COLOR_GREEN='\033[38;5;46m'         # Success
readonly COLOR_YELLOW='\033[38;5;226m'       # Warning
readonly COLOR_ORANGE='\033[38;5;208m'       # Caution
readonly COLOR_RED='\033[38;5;196m'          # Error
readonly COLOR_RED_DARK='\033[38;5;124m'     # Fatal/Critical
readonly COLOR_GRAY='\033[38;5;250m'         # Notes/Secondary
readonly COLOR_CYAN='\033[38;5;51m'          # Highlight
readonly COLOR_PURPLE='\033[38;5;141m'       # Special
readonly COLOR_WHITE='\033[38;5;231m'        # Primary text

# ================================================================================
# UNICODE ICONS
# ================================================================================

readonly ICON_OK="✔"                # Success/Completed
readonly ICON_ERR="✖"               # Error/Failed
readonly ICON_WARN="⚠"              # Warning
readonly ICON_INFO="ℹ"              # Information
readonly ICON_FATAL="‼"             # Fatal error
readonly ICON_NOTE="🛈"             # Note/Documentation
readonly ICON_ROCKET="🚀"           # Launch/Start
readonly ICON_GEAR="⚙"              # Configuration/Settings
readonly ICON_DOWNLOAD="⬇"          # Download
readonly ICON_UPLOAD="⬆"            # Upload
readonly ICON_COMPILE="🔨"          # Build/Compile
readonly ICON_SSL="🔒"              # Security/SSL
readonly ICON_FOLDER="📁"           # Directory
readonly ICON_FILE="📄"             # File
readonly ICON_LINK="🔗"             # Link/Connection
readonly ICON_CHECKMARK="✓"        # Check
readonly ICON_CROSS="✗"            # Cross/Cancel
readonly ICON_ARROW_RIGHT="→"      # Arrow right
readonly ICON_ARROW_LEFT="←"       # Arrow left
readonly ICON_ARROW_UP="↑"         # Arrow up
readonly ICON_ARROW_DOWN="↓"       # Arrow down
readonly ICON_BULLET="•"           # Bullet point
readonly ICON_STAR="★"             # Star
readonly ICON_CLOCK="⏱"            # Time/Duration
readonly ICON_PACKAGE="📦"         # Package

# ================================================================================
# LINE SEPARATOR FUNCTIONS
# ================================================================================

# Draw a line of specified character across the terminal width
draw_line() {
    local char="${1:-─}"
    printf "%${TERM_WIDTH}s\n" | tr ' ' "$char"
}

# Various pre-defined line types
line_single() { draw_line "─"; }
line_double() { draw_line "═"; }
line_heavy() { draw_line "━"; }
line_dashed() { draw_line "┄"; }
line_dotted() { draw_line "·"; }
line_underscore() { draw_line "_"; }
line_equals() { draw_line "="; }
line_hyphen() { draw_line "-"; }
line_colon() { draw_line ":"; }
line_tilde() { draw_line "~"; }
line_asterisk() { draw_line "*"; }
line_hash() { draw_line "#"; }

# Semantic line separators
line_section() { line_double; }
line_subsection() { line_single; }
line_minor() { line_dashed; }

# ================================================================================
# TEXT FORMATTING FUNCTIONS
# ================================================================================

# Wrap text to fit terminal width
wrap_text() {
    echo "$1" | fold -s -w "$TERM_WIDTH"
}

# Center text within terminal width
center_text() {
    local text="$1"
    local text_length=${#text}
    local padding=$(( (TERM_WIDTH - text_length) / 2 ))
    
    if [ $padding -gt 0 ]; then
        printf "%*s%s\n" "$padding" "" "$text"
    else
        echo "$text"
    fi
}

# Center wrapped text with color support
center_wrap_text() {
    local text="$1"
    local wrapped_lines
    
    IFS=$'\n' read -rd '' -a wrapped_lines <<<"$(wrap_text "$text")"
    
    for line in "${wrapped_lines[@]}"; do
        center_text "$line"
    done
}

# Center wrapped text with color
center_wrap_color() {
    local color="$1"
    local text="$2"
    local wrapped_lines
    
    IFS=$'\n' read -rd '' -a wrapped_lines <<<"$(wrap_text "$text")"
    
    for line in "${wrapped_lines[@]}"; do
        local line_length=${#line}
        local padding=$(( (TERM_WIDTH - line_length) / 2 ))
        printf "%b%*s%s%b\n" "$color" "$padding" "" "$line" "$NC"
    done
}

# Print text with specific color and optional icon
print_colored() {
    local color="$1"
    local text="$2"
    local icon="${3:-}"
    
    if [ -n "$icon" ]; then
        echo -e "${color}${icon} $(wrap_text "$text")${NC}"
    else
        echo -e "${color}$(wrap_text "$text")${NC}"
    fi
}

# ================================================================================
# SEMANTIC MESSAGE FUNCTIONS
# ================================================================================

# Information message (blue)
msg_info() {
    print_colored "$COLOR_BLUE" "$1" "$ICON_INFO"
}

# Success message (green)
msg_success() {
    print_colored "$COLOR_GREEN" "$1" "$ICON_OK"
}

# Warning message (yellow)
msg_warn() {
    print_colored "$COLOR_YELLOW" "$1" "$ICON_WARN"
}

# Caution message (orange)
msg_caution() {
    print_colored "$COLOR_ORANGE" "$1" "$ICON_WARN"
}

# Error message (red)
msg_error() {
    print_colored "$COLOR_RED" "$1" "$ICON_ERR"
}

# Fatal error message (dark red)
msg_fatal() {
    print_colored "$COLOR_RED_DARK" "$1" "$ICON_FATAL"
}

# Note message (gray)
msg_note() {
    print_colored "$COLOR_GRAY" "$1" "$ICON_NOTE"
}

# Highlight message (cyan)
msg_highlight() {
    print_colored "$COLOR_CYAN" "$1"
}

# ================================================================================
# PROGRESS AND STATUS FUNCTIONS
# ================================================================================

# Display a step with number and description
show_step() {
    local step_num="$1"
    local description="$2"
    echo -e "${COLOR_BLUE_BRIGHT}${ICON_ARROW_RIGHT} Step ${step_num}:${NC} ${description}"
}

# Display a task being performed
show_task() {
    local task="$1"
    echo -e "${COLOR_CYAN}${ICON_GEAR} ${task}...${NC}"
}

# Display a download operation
show_download() {
    local item="$1"
    echo -e "${COLOR_BLUE}${ICON_DOWNLOAD} Downloading ${item}...${NC}"
}

# Display a compilation operation
show_compile() {
    local item="$1"
    echo -e "${COLOR_ORANGE}${ICON_COMPILE} Compiling ${item}...${NC}"
}

# Display a configuration operation
show_config() {
    local item="$1"
    echo -e "${COLOR_PURPLE}${ICON_GEAR} Configuring ${item}...${NC}"
}

# ================================================================================
# INPUT AND PROMPT FUNCTIONS
# ================================================================================

# Display a prompt for user input
prompt_user() {
    local prompt_text="$1"
    local default_value="${2:-}"
    
    if [ -n "$default_value" ]; then
        echo -e "${COLOR_YELLOW}${prompt_text}${NC}"
        echo -e "${COLOR_GRAY}Default: ${default_value} | Press [ENTER] to accept${NC}"
    else
        echo -e "${COLOR_YELLOW}${prompt_text}${NC}"
    fi
    printf "> "
}

# Display a confirmation prompt
prompt_confirm() {
    local question="$1"
    local default="${2:-Y}"
    
    echo -e "${COLOR_ORANGE}${question} (Y/N)${NC}"
    if [ "$default" = "Y" ]; then
        echo -e "${COLOR_GRAY}Default: Y | Press [ENTER] to accept${NC}"
    fi
    printf "> "
}

# ================================================================================
# HEADER AND BANNER FUNCTIONS
# ================================================================================

# Display a main header
show_header() {
    local title="$1"
    echo ""
    line_section
    echo ""
    center_wrap_color "$COLOR_GREEN" "$title"
    echo ""
    line_section
    echo ""
}

# Display a subheader
show_subheader() {
    local title="$1"
    echo ""
    line_subsection
    echo ""
    center_wrap_color "$COLOR_BLUE_BRIGHT" "$title"
    echo ""
    line_subsection
    echo ""
}

# Display a section title
show_section() {
    local title="$1"
    echo ""
    line_minor
    echo ""
    center_wrap_color "$COLOR_CYAN" "$title"
    echo ""
}

# Display a box around text
show_box() {
    local text="$1"
    local color="${2:-$COLOR_WHITE}"
    local width=$((TERM_WIDTH - 4))
    
    echo -e "${color}"
    printf "╔%${width}s╗\n" | tr ' ' '═'
    
    IFS=$'\n' read -rd '' -a lines <<<"$(echo "$text" | fold -s -w $((width - 2)))"
    for line in "${lines[@]}"; do
        local padding=$((width - ${#line} - 2))
        printf "║ %s%*s ║\n" "$line" "$padding" ""
    done
    
    printf "╚%${width}s╝\n" | tr ' ' '═'
    echo -e "${NC}"
}

# ================================================================================
# LOGGING FUNCTIONS
# ================================================================================

# Initialize log file with header
init_log() {
    local log_file="$1"
    local title="${2:-Installation Log}"
    
    cat > "$log_file" <<EOF
================================================================================
$title
================================================================================
Started: $(date '+%Y-%m-%d %H:%M:%S')
User: $(whoami)
Hostname: $(hostname)
================================================================================

EOF
}

# Log a message to file
log_write() {
    local log_file="$1"
    local level="$2"
    local message="$3"
    local timestamp
    timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    echo "[$timestamp] [$level] $message" >> "$log_file"
}

# Log info message
log_info() {
    log_write "$1" "INFO" "$2"
}

# Log success message
log_success() {
    log_write "$1" "SUCCESS" "$2"
}

# Log warning message
log_warning() {
    log_write "$1" "WARNING" "$2"
}

# Log error message
log_error() {
    log_write "$1" "ERROR" "$2"
}

# Log with command output
log_command() {
    local log_file="$1"
    local description="$2"
    local command="$3"
    
    log_info "$log_file" "Executing: $description"
    log_info "$log_file" "Command: $command"
    
    local output
    local exit_code
    
    output=$($command 2>&1)
    exit_code=$?
    
    if [ $exit_code -eq 0 ]; then
        log_success "$log_file" "$description completed successfully"
    else
        log_error "$log_file" "$description failed with exit code $exit_code"
        log_error "$log_file" "Output: $output"
    fi
    
    return $exit_code
}

# ================================================================================
# UTILITY FUNCTIONS
# ================================================================================

# Display a countdown timer
countdown() {
    local seconds="$1"
    local message="${2:-Waiting}"
    local color="${3:-$COLOR_YELLOW}"
    
    for ((i=seconds; i>0; i--)); do
        printf "\r%b%s %2d seconds%b" "$color" "$message" "$i" "$NC"
        sleep 1
    done
    echo ""
}

# Display a spinner while command runs
spinner() {
    local pid=$1
    local message="${2:-Processing}"
    local delay=0.1
    local spinstr='⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏'
    
    echo -n "$message "
    while kill -0 $pid 2>/dev/null; do
        local temp=${spinstr#?}
        printf "[%c]" "$spinstr"
        spinstr=$temp${spinstr%"$temp"}
        sleep $delay
        printf "\b\b\b"
    done
    printf "   \b\b\b"
    echo ""
}

# Check command exit status and display appropriate message
check_status() {
    local exit_code=$1
    local success_msg="${2:-Operation completed successfully}"
    local error_msg="${3:-Operation failed}"
    
    if [ $exit_code -eq 0 ]; then
        msg_success "$success_msg"
        return 0
    else
        msg_error "$error_msg"
        return 1
    fi
}

# ================================================================================
# FILE PERMISSION FUNCTIONS
# ================================================================================

# Set permissions based on file type
set_file_permissions() {
    local path="$1"
    local log_file="${2:-}"
    
    if [ ! -e "$path" ]; then
        [ -n "$log_file" ] && log_error "$log_file" "Path does not exist: $path"
        return 1
    fi
    
    if [ -d "$path" ]; then
        # Directories: 755 (rwxr-xr-x)
        find "$path" -type d -exec chmod 755 {} \;
        [ -n "$log_file" ] && log_info "$log_file" "Set directory permissions (755) for: $path"
    fi
    
    # Executable files: 755 (rwxr-xr-x)
    find "$path" -type f \( -name "*.sh" -o -name "*.py" -o -name "*.pl" \) -exec chmod 755 {} \;
    [ -n "$log_file" ] && log_info "$log_file" "Set executable permissions (755) for scripts in: $path"
    
    # Configuration files: 644 (rw-r--r--)
    find "$path" -type f \( -name "*.conf" -o -name "*.config" -o -name "*.cfg" -o -name "*.ini" -o -name "*.xml" -o -name "*.json" -o -name "*.yaml" -o -name "*.yml" \) -exec chmod 644 {} \;
    [ -n "$log_file" ] && log_info "$log_file" "Set config file permissions (644) for: $path"
    
    # Service files: 644 (rw-r--r--)
    find "$path" -type f -name "*.service" -exec chmod 644 {} \;
    [ -n "$log_file" ] && log_info "$log_file" "Set service file permissions (644) for: $path"
    
    # Timer files: 644 (rw-r--r--)
    find "$path" -type f -name "*.timer" -exec chmod 644 {} \;
    [ -n "$log_file" ] && log_info "$log_file" "Set timer file permissions (644) for: $path"
    
    # PHP files: 644 (rw-r--r--)
    # Note: PHP CLI scripts with shebang (e.g., #!/usr/bin/env php) that need 
    # direct execution should be set to 755 separately before calling this function
    # Example: chmod 755 /path/to/cli-script.php
    find "$path" -type f -name "*.php" -exec chmod 644 {} \;
    [ -n "$log_file" ] && log_info "$log_file" "Set PHP file permissions (644) for: $path"
    
    # HTML/CSS/JS files: 644 (rw-r--r--)
    find "$path" -type f \( -name "*.html" -o -name "*.css" -o -name "*.js" \) -exec chmod 644 {} \;
    [ -n "$log_file" ] && log_info "$log_file" "Set web file permissions (644) for: $path"
    
    # Log files: 644 (rw-r--r--)
    find "$path" -type f \( -name "*.log" -o -name "*.txt" \) -exec chmod 644 {} \;
    [ -n "$log_file" ] && log_info "$log_file" "Set log file permissions (644) for: $path"
    
    # Binary executables (no extension or specific binaries): 755
    find "$path" -type f -executable ! -name "*.sh" ! -name "*.py" ! -name "*.pl" -exec chmod 755 {} \;
    [ -n "$log_file" ] && log_info "$log_file" "Set binary permissions (755) for executables in: $path"
    
    # Database files: 644 (rw-r--r--)
    find "$path" -type f \( -name "*.db" -o -name "*.sqlite" -o -name "*.sqlite3" -o -name "*.dat" \) -exec chmod 644 {} \;
    [ -n "$log_file" ] && log_info "$log_file" "Set database file permissions (644) for: $path"
    
    return 0
}

# Set ownership and permissions for web directories
set_web_permissions() {
    local path="$1"
    local web_user="${2:-www-data}"
    local log_file="${3:-}"
    
    if [ ! -d "$path" ]; then
        [ -n "$log_file" ] && log_error "$log_file" "Directory does not exist: $path"
        return 1
    fi
    
    # Set ownership
    chown -R "$web_user:$web_user" "$path"
    [ -n "$log_file" ] && log_info "$log_file" "Set ownership ($web_user:$web_user) for: $path"
    
    # Set permissions
    set_file_permissions "$path" "$log_file"
    
    return 0
}

# Set systemd service file permissions
set_systemd_permissions() {
    local service_file="$1"
    local log_file="${2:-}"
    
    if [ ! -f "$service_file" ]; then
        [ -n "$log_file" ] && log_error "$log_file" "Service file does not exist: $service_file"
        return 1
    fi
    
    chmod 644 "$service_file"
    [ -n "$log_file" ] && log_info "$log_file" "Set systemd service permissions (644) for: $service_file"
    
    return 0
}

# ================================================================================
# VALIDATION FUNCTIONS
# ================================================================================

# Validate email format
validate_email() {
    local email="$1"
    if [[ "$email" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
        return 0
    else
        return 1
    fi
}

# Validate domain format
validate_domain() {
    local domain="$1"
    if [[ "$domain" =~ ^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$ ]]; then
        return 0
    else
        return 1
    fi
}

# Validate IP address
validate_ip() {
    local ip="$1"
    if [[ "$ip" =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
        IFS='.' read -ra ADDR <<< "$ip"
        for i in "${ADDR[@]}"; do
            if [ "$i" -gt 255 ]; then
                return 1
            fi
        done
        return 0
    else
        return 1
    fi
}

# Validate port number
validate_port() {
    local port="$1"
    if [[ "$port" =~ ^[0-9]+$ ]] && [ "$port" -ge 1 ] && [ "$port" -le 65535 ]; then
        return 0
    else
        return 1
    fi
}

# ================================================================================
# SYSTEM CHECK FUNCTIONS
# ================================================================================

# Check if running as root
check_root() {
    if [ "$(id -u)" -eq 0 ]; then
        return 0
    else
        return 1
    fi
}

# Check internet connectivity
check_internet() {
    if ping -c 1 -W 2 8.8.8.8 &>/dev/null; then
        return 0
    else
        return 1
    fi
}

# Check if command exists
command_exists() {
    command -v "$1" &>/dev/null
}

# Check available disk space (in GB)
check_disk_space() {
    local required_gb="$1"
    local available_gb
    available_gb=$(df -BG / | awk 'NR==2 {print $4}' | sed 's/G//')
    
    if [ "$available_gb" -ge "$required_gb" ]; then
        return 0
    else
        return 1
    fi
}

# ================================================================================
# EXPORT FUNCTIONS (for use in other scripts)
# ================================================================================

# Export functions for use in sourcing scripts
export -f get_terminal_width
export -f draw_line line_single line_double line_heavy line_dashed line_dotted
export -f line_underscore line_equals line_hyphen line_colon line_tilde
export -f line_asterisk line_hash line_section line_subsection line_minor
export -f wrap_text center_text center_wrap_text center_wrap_color print_colored
export -f msg_info msg_success msg_warn msg_caution msg_error msg_fatal msg_note msg_highlight
export -f show_step show_task show_download show_compile show_config
export -f prompt_user prompt_confirm
export -f show_header show_subheader show_section show_box
export -f init_log log_write log_info log_success log_warning log_error log_command
export -f countdown spinner check_status
export -f set_file_permissions set_web_permissions set_systemd_permissions
export -f validate_email validate_domain validate_ip validate_port
export -f check_root check_internet command_exists check_disk_space

# ================================================================================
# END OF CLI VISUAL UNICODE LIBRARY
# ================================================================================
