# CLI Visual Unicode Library - Quick Reference

## Color Variables

```bash
NC              # No Color / Reset
BOLD            # Bold text
DIM             # Dimmed text
UNDERLINE       # Underlined text
COLOR_BLUE      # Information (38;5;39)
COLOR_BLUE_BRIGHT  # Bright blue
COLOR_GREEN     # Success (38;5;46)
COLOR_YELLOW    # Warning (38;5;226)
COLOR_ORANGE    # Caution (38;5;208)
COLOR_RED       # Error (38;5;196)
COLOR_RED_DARK  # Fatal (38;5;124)
COLOR_GRAY      # Notes (38;5;250)
COLOR_CYAN      # Highlight (38;5;51)
COLOR_PURPLE    # Special (38;5;141)
COLOR_WHITE     # Primary text (38;5;231)
```

## Unicode Icons

```bash
ICON_OK="✔"         # Success
ICON_ERR="✖"        # Error
ICON_WARN="⚠"       # Warning
ICON_INFO="ℹ"       # Information
ICON_FATAL="‼"      # Fatal error
ICON_NOTE="🛈"      # Note
ICON_ROCKET="🚀"    # Launch
ICON_GEAR="⚙"       # Configuration
ICON_DOWNLOAD="⬇"   # Download
ICON_UPLOAD="⬆"     # Upload
ICON_COMPILE="🔨"   # Compile
ICON_SSL="🔒"       # Security
ICON_FOLDER="📁"    # Directory
ICON_FILE="📄"      # File
ICON_PACKAGE="📦"   # Package
ICON_CLOCK="⏱"     # Time
```

## Line Separators

```bash
line_single()      # ─────────────────────
line_double()      # ═════════════════════
line_heavy()       # ━━━━━━━━━━━━━━━━━━━━━
line_dashed()      # ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄
line_dotted()      # ·····················
line_underscore()  # _____________________
line_equals()      # =====================
line_hyphen()      # ---------------------
line_colon()       # :::::::::::::::::::::
line_section()     # Main section (double line)
line_subsection()  # Subsection (single line)
line_minor()       # Minor section (dashed)
```

## Semantic Messages

### Basic Messages
```bash
msg_info "Information message"
# Output: ℹ Information message (in blue)

msg_success "Operation successful"
# Output: ✔ Operation successful (in green)

msg_warn "Warning message"
# Output: ⚠ Warning message (in yellow)

msg_caution "Caution message"
# Output: ⚠ Caution message (in orange)

msg_error "Error occurred"
# Output: ✖ Error occurred (in red)

msg_fatal "Fatal error - exits script"
# Output: ‼ Fatal error (in dark red, then exits)

msg_note "Additional note"
# Output: 🛈 Additional note (in gray)

msg_highlight "Highlighted text"
# Output: Highlighted text (in cyan)
```

### Task Messages
```bash
show_step 1 "Description of step"
# Output: → Step 1: Description of step

show_task "Installing package"
# Output: ⚙ Installing package...

show_download "file.tar.gz"
# Output: ⬇ Downloading file.tar.gz...

show_compile "application"
# Output: 🔨 Compiling application...

show_config "service"
# Output: ⚙ Configuring service...
```

## Text Formatting

```bash
# Wrap text to terminal width
wrap_text "Long text that needs to be wrapped"

# Center text
center_text "Centered text"

# Center wrapped text
center_wrap_text "Long centered text that will be wrapped"

# Center with color
center_wrap_color "$COLOR_GREEN" "Colored centered text"

# Print with color and optional icon
print_colored "$COLOR_BLUE" "Message" "$ICON_INFO"
```

## Headers and Banners

```bash
# Main header with lines
show_header "MAIN SECTION TITLE"
# Output:
# ════════════════════════════
#     MAIN SECTION TITLE
# ════════════════════════════

# Subheader
show_subheader "Subsection Title"
# Output:
# ────────────────────────────
#     Subsection Title
# ────────────────────────────

# Section title
show_section "Section Name"
# Output:
# ┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄┄
#     Section Name

# Box around text
show_box "Important Message" "$COLOR_RED"
# Output:
# ╔════════════════════════╗
# ║ Important Message      ║
# ╚════════════════════════╝
```

## User Input

```bash
# Prompt for input
prompt_user "Enter your name" "John Doe"
# Output: 
# Enter your name
# Default: John Doe | Press [ENTER] to accept
# >

# Confirmation prompt
prompt_confirm "Do you want to continue?" "Y"
# Output:
# Do you want to continue? (Y/N)
# Default: Y | Press [ENTER] to accept
# >
```

## Progress Indicators

```bash
# Countdown timer
countdown 10 "Waiting" "$COLOR_YELLOW"
# Output: Waiting 10 seconds (counts down)

# Spinner (for background processes)
spinner $pid "Processing"
# Output: [rotating spinner] Processing

# Check status
check_status $? "Success message" "Error message"
# Shows success or error based on exit code
```

## Logging Functions

```bash
# Initialize log file
init_log "/path/to/logfile.log" "Log Title"

# Log messages
log_info "$LOGFILE" "Information message"
log_success "$LOGFILE" "Success message"
log_warning "$LOGFILE" "Warning message"
log_error "$LOGFILE" "Error message"

# Log command execution
log_command "$LOGFILE" "Update packages" "apt update"
# Logs command, output, and result
```

## Permission Functions

```bash
# Set permissions based on file type
set_file_permissions "/path/to/directory" "$LOGFILE"
# Automatically handles:
# - Directories (755)
# - Scripts (755)
# - Config files (644)
# - Service files (644)
# - Web files (644)
# - Database files (644)

# Set web permissions
set_web_permissions "/var/www/html" "www-data" "$LOGFILE"
# Sets ownership AND permissions
```

## Validation Functions

```bash
# Validate email
if validate_email "user@example.com"; then
    echo "Valid email"
fi

# Validate domain
if validate_domain "example.com"; then
    echo "Valid domain"
fi

# Validate IP address
if validate_ip "192.168.1.1"; then
    echo "Valid IP"
fi

# Validate port number
if validate_port "8080"; then
    echo "Valid port"
fi
```

## System Check Functions

```bash
# Check if running as root
if check_root; then
    echo "Running as root"
fi

# Check internet connectivity
if check_internet; then
    echo "Internet available"
fi

# Check if command exists
if command_exists "git"; then
    echo "Git is installed"
fi

# Check disk space (in GB)
if check_disk_space 10; then
    echo "At least 10GB available"
fi
```

## Usage Examples

### Example 1: Installation Script Header
```bash
#!/bin/bash
source "cli_visual_unicode.sh"

show_header "MY APPLICATION INSTALLER"
msg_info "Starting installation process..."
echo ""
```

### Example 2: User Prompt with Validation
```bash
while true; do
    prompt_user "Enter your email address"
    read -r email
    
    if validate_email "$email"; then
        msg_success "Email validated: $email"
        break
    fi
    
    msg_error "Invalid email format"
done
```

### Example 3: Installation Step
```bash
show_subheader "INSTALLING PACKAGES"

show_task "Updating package lists"
if apt update >> "$LOGFILE" 2>&1; then
    log_success "$LOGFILE" "Package lists updated"
    msg_success "Package lists updated"
else
    log_error "$LOGFILE" "Failed to update packages"
    msg_error "Update failed"
    exit 1
fi
```

### Example 4: Progress with Countdown
```bash
show_task "Starting service"
systemctl start myservice &
pid=$!

countdown 5 "Initializing service"
wait $pid

if systemctl is-active myservice >/dev/null; then
    msg_success "Service started successfully"
else
    msg_error "Service failed to start"
fi
```

### Example 5: Configuration Summary
```bash
show_header "CONFIGURATION SUMMARY"

msg_highlight "Server Settings:"
msg_note "  Domain: example.com"
msg_note "  Port: 8080"
msg_note "  SSL: Enabled"

echo ""
prompt_confirm "Proceed with installation?" "Y"
read -r answer

if [[ "$answer" =~ ^[Yy] ]]; then
    msg_success "Configuration confirmed"
else
    msg_error "Installation cancelled"
    exit 0
fi
```

## Best Practices

1. **Always source the library first**
   ```bash
   source "cli_visual_unicode.sh"
   ```

2. **Use semantic functions for consistent messaging**
   ```bash
   msg_info "Starting"    # Not: echo "Starting"
   msg_success "Done"      # Not: echo "Done"
   ```

3. **Log important operations**
   ```bash
   log_info "$LOGFILE" "Starting operation"
   # ... do operation ...
   log_success "$LOGFILE" "Operation completed"
   ```

4. **Validate user input**
   ```bash
   if validate_email "$email"; then
       # Use email
   fi
   ```

5. **Set appropriate permissions**
   ```bash
   set_file_permissions "/path" "$LOGFILE"
   # Don't use generic chmod commands
   ```

6. **Show progress for long operations**
   ```bash
   countdown 10 "Processing"
   # Or for background tasks:
   spinner $pid "Processing"
   ```

7. **Use headers to organize output**
   ```bash
   show_header "MAIN SECTION"
   show_subheader "Subsection"
   show_section "Detail"
   ```

## Terminal Width

The library automatically detects terminal width and caps at 100 characters:
```bash
TERM_WIDTH=$(get_terminal_width)
# Returns current terminal width, max 100
```

All text wrapping and centering functions respect this width.

## Color Support

The library uses 256-color palette. If terminal doesn't support it:
- Colors may appear differently
- Basic 16-color terminals will show approximate colors
- No-color terminals will show text without formatting

To disable colors:
```bash
NC='\033[0m'
COLOR_BLUE="$NC"
COLOR_GREEN="$NC"
# ... set all colors to NC
```
