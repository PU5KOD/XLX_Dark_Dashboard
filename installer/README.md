# XLX Reflector Installer - Optimized Version

This is an optimized version of the XLX Multiprotocol Amateur Radio Reflector Installer with improved organization, enhanced logging, and modular design.

## Overview

The installer has been completely refactored to provide:

1. **Modular Design**: Separation of concerns with visual/UI functions in a dedicated library
2. **Enhanced Logging**: Detailed logging of all operations with timestamps and status tracking
3. **Optimized Permissions**: File permissions applied based on file type rather than generic settings
4. **Improved Code Organization**: Structured functions for better readability and maintenance
5. **Standardized Patterns**: Consistent coding standards throughout the script

## Key Components

### 1. cli_visual_unicode.sh

A comprehensive visual library that provides:

- **Color Palette**: Extended color definitions for semantic messaging
- **Unicode Icons**: Rich set of icons for different message types
- **Line Separators**: Various line styles for visual organization
- **Text Formatting**: Functions for wrapping, centering, and formatting text
- **Semantic Messages**: Standardized functions for info, success, warning, error messages
- **Progress Indicators**: Countdown timers and spinners
- **File Permissions**: Smart permission setting based on file types
- **Validation Functions**: Input validation for email, domain, IP, port
- **System Checks**: Functions to verify root access, internet connectivity, etc.
- **Logging Functions**: Comprehensive logging with multiple levels

### 2. installer.sh

The main installation script featuring:

- **Structured Initialization**: Clear setup of paths, constants, and logging
- **System Validation**: Root check, internet connectivity, distribution verification
- **Interactive Prompts**: User-friendly input collection with validation
- **Modular Installation**: Separate functions for each installation phase
- **Error Handling**: Proper error detection and reporting
- **Service Management**: Automated service startup and configuration

### 3. Templates Directory

Contains all necessary configuration templates:

- `apache.tbd.conf` - Apache virtual host configuration
- `xlx_log.service` - Systemd service for XLX logging
- `xlx_log.sh` - XLX log management script
- `xlx_logrotate.conf` - Log rotation configuration
- `update_XLX_db.service` - Database update service
- `update_XLX_db.timer` - Database update timer

## Improvements

### Code Organization

**Before:**
- Mixed concerns (UI, logic, configuration) in single file
- Inconsistent naming conventions
- Hardcoded values scattered throughout
- Generic permission settings

**After:**
- Separated UI/visual functions into library
- Consistent naming conventions and patterns
- Constants defined at script start
- File-type-specific permissions

### Logging Enhancements

**Before:**
- Basic output redirection to log file
- Limited context in log entries
- No structured logging

**After:**
- Detailed logging with timestamps
- Success/failure tracking for each operation
- Structured log format with levels (INFO, SUCCESS, WARNING, ERROR)
- Separate log file for each installation run

### Permission Management

**Before:**
```bash
find /xlxd -type d -exec chmod 755 {} \;
find /xlxd -type f -exec chmod 755 {} \;
find "$WEBDIR" -type d -exec chmod 755 {} \;
find "$WEBDIR" -type f -exec chmod 755 {} \;
```

**After:**
```bash
# Directories: 755 (rwxr-xr-x)
find "$path" -type d -exec chmod 755 {} \;

# Executable scripts: 755 (rwxr-xr-x)
find "$path" -type f \( -name "*.sh" -o -name "*.py" -o -name "*.pl" \) -exec chmod 755 {} \;

# Configuration files: 644 (rw-r--r--)
find "$path" -type f \( -name "*.conf" -o -name "*.config" -o -name "*.cfg" ... \) -exec chmod 644 {} \;

# Service/timer files: 644 (rw-r--r--)
find "$path" -type f \( -name "*.service" -o -name "*.timer" \) -exec chmod 644 {} \;

# Web files (PHP, HTML, CSS, JS): 644 (rw-r--r--)
find "$path" -type f \( -name "*.php" -o -name "*.html" ... \) -exec chmod 644 {} \;

# Database files: 644 (rw-r--r--)
find "$path" -type f \( -name "*.db" -o -name "*.sqlite" -o -name "*.dat" \) -exec chmod 644 {} \;
```

### Visual Improvements

- **Progress Indicators**: Visual countdown timers for service initialization
- **Status Messages**: Clear, color-coded messages for different types of information
- **Section Headers**: Well-formatted section dividers for better readability
- **Confirmation Displays**: Formatted summary of all settings before installation
- **Completion Summary**: Comprehensive final message with important information

## Usage

### Installation

```bash
cd installer
chmod +x installer.sh
sudo ./installer.sh
```

The script will:

1. Verify system requirements (root access, internet, distribution)
2. Check for existing installations
3. Gather system information
4. Collect user configuration interactively
5. Display settings for confirmation
6. Update system packages
7. Install dependencies
8. Download and compile XLX
9. Configure XLX services
10. Install Echo Test (optional)
11. Install and configure dashboard
12. Install SSL certificate (optional)
13. Start all services
14. Display completion information

### Logs

Installation logs are saved to:
```
installer/log/xlx_install_YYYY-MM-DD_HH-MM-SS.log
```

Each log contains:
- Timestamp for each operation
- Success/failure status
- Command output (for failed operations)
- System information
- Configuration settings

## GitHub Repositories

The installer uses the following PU5KOD customized repositories:

- **XLX Reflector**: https://github.com/PU5KOD/xlxd.git
- **Echo Test Server**: https://github.com/PU5KOD/XLXEcho.git
- **Dashboard**: https://github.com/PU5KOD/XLX_Dark_Dashboard.git

These repositories contain customizations and templates not present in the original versions.

## File Permissions Reference

| File Type | Permission | Octal | Description |
|-----------|------------|-------|-------------|
| Directories | rwxr-xr-x | 755 | Readable and executable by all, writable by owner |
| Scripts (*.sh, *.py, *.pl) | rwxr-xr-x | 755 | Executable scripts |
| Binary executables | rwxr-xr-x | 755 | Compiled binaries |
| Config files (*.conf, *.cfg, etc) | rw-r--r-- | 644 | Readable by all, writable by owner |
| Service files (*.service, *.timer) | rw-r--r-- | 644 | Systemd unit files |
| Web files (*.php, *.html, *.css, *.js) | rw-r--r-- | 644 | Web content files |
| Database files (*.db, *.sqlite, *.dat) | rw-r--r-- | 644 | Database files |
| Log files (*.log, *.txt) | rw-r--r-- | 644 | Log files |

## Features

### Input Validation

- **Email**: RFC-compliant email format validation
- **Domain**: FQDN format validation
- **Callsign**: 3-8 alphanumeric characters
- **Timezone**: System timezone list validation with GMT±X support
- **Port Numbers**: Range validation (1-65535)
- **Frequency**: 9-digit numeric validation

### Error Handling

- Comprehensive error checking after each operation
- Graceful failure with informative messages
- Exit codes for scripted usage
- Detailed error logging

### Security

- Root privilege requirement
- Proper file ownership (www-data for web files)
- Secure permission settings
- SSL certificate support via Let's Encrypt

## Troubleshooting

### Check Installation Log

```bash
tail -f installer/log/xlx_install_*.log
```

### Verify Services

```bash
systemctl status xlxd.service
systemctl status xlxecho.service  # if Echo Test installed
systemctl status xlx_log.service
```

### Check Permissions

```bash
ls -la /xlxd/
ls -la /var/www/html/xlxd/
```

### View Dashboard

- HTTP: http://your-domain.com
- HTTPS: https://your-domain.com (if SSL installed)

## Contributing

Contributions are welcome! Please ensure:

1. Code follows existing patterns and conventions
2. Functions are properly documented
3. Logging is comprehensive
4. Error handling is robust
5. Permissions are appropriate for file types

## Author

Customized by Daniel K., PU5KOD

## License

This project follows the same license as the original XLX project.

For more information about XLX Reflectors, visit: https://xlxbbs.epf.lu/
