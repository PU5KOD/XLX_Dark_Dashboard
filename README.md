# XLX_Dark_Dashboard
Custom dashboard with improvements and dark theme for XLX reflectors

The dashboard here is a fork of the default one that is installed together with XLXD Reflector but with a series of changes that improve the user experience.
In addition to the standard information, the following improvements were made:

---

## Core Features

1. **Dark theme** — along with changes to the page layout, the dark theme makes it easier to read information on screen;
2. **User database integration** — adds operator name and city to the *Recent Activity* and *Connected Stations* tabs, fetched from a local SQLite database;
3. **Improved layout** — columns were reordered for better readability, and users connected to each module are now displayed below the main table to optimize screen utilization;
4. **Hidden tabs support** — the *Links* tab can now be hidden the same way as *Network* and *Traffic*; the adjustment is made in `config.inc.php`;
5. **Redesigned Network tab** — now displays more information in a readable format, including a 30-day date history and additional node details;
6. **Dual-network Traffic tab** — now shows both networks: legacy ircddb and the main QuadNet, which is currently the most widely used and registered as default in Pi-Star;
7. **Minor customizations** — various data points are entered during installation and can be updated later in the settings file.

---

## Recent Improvements

### Interface & Layout
- **Responsive layout** — page content and menu bar are now centrally aligned and properly bounded on wide screens and mobile devices;
- **jQuery upgraded** to version 3.7.1 via CDN;
- **Browser tab badge** — the page title now shows the number of connected stations in parentheses, e.g. `(18) XLX123 by PU5KOD`, updating automatically on every refresh;
- **Interface in English** — all labels, buttons and filter controls across all tabs are in English.

### Connected Stations Tab
- **Live connection duration counter** — the connection time for each node in the *Connected Stations* tab now updates every second in real time, without requiring a page reload.

### Recent Activity Tab — Live TX Detection
- **Active transmission detection via log** — instead of relying on the 10-second heartbeat, the system now reads `/var/log/xlx.log` in real time to detect when a transmission starts (`Opening stream`) and ends (`Closing stream`), providing accurate start timestamps;
- **TX row highlight** — the row of the station currently transmitting is highlighted with a pulsing amber background animation;
- **Live TX timer** — the *Last Activity* field for a transmitting station is replaced by a live counter in the format `TXing 02:34s`, counting up from the exact moment the transmission started;
- **Simultaneous multi-module TX support** — the system detects active transmissions across different modules simultaneously; each module's first station in the list receives its own independent counter;
- **TX displayed in browser tab** — while a transmission is active, the browser tab title changes to show the transmitting operator's callsign and elapsed time, e.g. `(18) PU5KOD TXing 02:34s...`; when multiple modules are transmitting simultaneously, the most recent one is shown in the tab;
- **TX timeout protection** — any transmission detected in the log that exceeds 5 minutes without a closing entry is automatically discarded, preventing phantom TX indicators caused by log corruption or missed closing events;
- **Interval leak prevention** — the JavaScript timer is properly destroyed before each AJAX page reload, ensuring no background timers accumulate over time.

### Page Refresh & Filters
- **Filter-aware auto-refresh** — the automatic page refresh is paused whenever a callsign or module filter is active, preserving the filtered view; the refresh resumes automatically after the filter is cleared;
- **Active modules excluded from auto-refresh** — the *Active Modules* tab is excluded from the page auto-refresh cycle to avoid interference with the chart's independent update timer.

### Module Activity Chart
- **Transmission history chart** — a stacked bar chart is displayed below the module table in the *Active Modules* tab, showing the number of transmissions per module over the last 24 hours, aggregated by hour;
- **Data sourced from log** — the chart parses `/var/log/xlx.log` directly on the server side, with no external dependencies;
- **Colorblind-safe palette** — module colors follow the Paul Tol / IBM accessibility standard to ensure readability for users with color vision deficiencies;
- **Independent 60-second refresh** — the chart updates itself every 60 seconds via AJAX, independently of the main page refresh cycle; a status line shows the last update time and countdown to the next update.

---

## File Structure (modified files)

| File | Description |
|---|---|
| `index.php` | Main page controller — AJAX reload, tab title, session, filter-aware refresh |
| `css/layout.css` | Dark theme styles, TX pulse animation, responsive layout |
| `pgs/users.php` | Recent Activity tab — TX detection, live timers, tab title, filters |
| `pgs/repeaters.php` | Connected Stations tab — live duration counter |
| `pgs/modules.php` | Active Modules tab — includes the activity chart |
| `pgs/chart.php` | Module activity chart — log parsing, Chart.js rendering, AJAX endpoint |

---

## Dependencies

- **Chart.js 4.4.1** — loaded via CDN, used for the module activity chart
- **jQuery 3.7.1** — loaded via CDN, used for AJAX page refresh
- **SQLite3** — local database for operator name and city lookup
- **PHP** — server-side log parsing and data rendering
