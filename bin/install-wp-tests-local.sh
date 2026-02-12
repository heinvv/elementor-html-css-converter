#!/usr/bin/env bash

WORKING_DIR=$(cd "$(dirname "$0")/.." && pwd)
PLUGIN_SLUG="elementor-html-css-converter"

check_prerequisites() {
	local missing=()
	
	command -v svn >/dev/null 2>&1 || missing+=("svn")
	command -v unzip >/dev/null 2>&1 || missing+=("unzip")
	command -v mysql >/dev/null 2>&1 || missing+=("mysql")
	command -v mysqladmin >/dev/null 2>&1 || missing+=("mysqladmin")
	
	if [[ ${#missing[@]} -gt 0 ]]; then
		echo "Error: Missing required tools: ${missing[*]}"
		echo ""
		echo "Install instructions:"
		[[ " ${missing[*]} " =~ " svn " ]] && echo "  - svn: brew install svn (macOS) or sudo apt-get install subversion (Linux)"
		[[ " ${missing[*]} " =~ " unzip " ]] && echo "  - unzip: brew install unzip or sudo apt-get install unzip"
		[[ " ${missing[*]} " =~ " mysql " ]] && echo "  - mysql: brew install mysql-client or sudo apt-get install mysql-client"
		[[ " ${missing[*]} " =~ " mysqladmin " ]] && echo "  - mysqladmin: brew install mysql-client or sudo apt-get install mysql-client"
		exit 1
	fi
}

echo "================================================"
echo "PHPUnit Test Environment Setup"
echo "================================================"
echo ""

check_prerequisites

echo "Database name for tests [elementor-tests]:"
read -r DB_NAME
echo "Database username (Local uses 'root') [admin]:"
read -r DB_USER
echo "Database password (Local uses 'root') [admin]:"
read -r DB_PASS
echo ""
echo "=== Database Host Configuration ==="
echo ""
echo "If using Local by Flywheel:"
echo "  1. Open your site in Local"
echo "  2. Click 'Database' tab to see connection details"
echo "  3. Common values: host=127.0.0.1, port varies by site (e.g. 10003)"
echo "  4. Use format: 127.0.0.1:PORT (example: 127.0.0.1:10003)"
echo ""
echo "Database host [127.0.0.1]:"
read -r DB_HOST
echo ""
echo "WordPress version [latest]:"
read -r WP_VERSION

DB_NAME=${DB_NAME:-"elementor-tests"}
DB_USER=${DB_USER:-"admin"}
DB_PASS=${DB_PASS:-"admin"}
DB_HOST=${DB_HOST:-"127.0.0.1"}
WP_VERSION=${WP_VERSION:-"latest"}

WP_TESTS_DIR=${WP_TESTS_DIR:-"$WORKING_DIR/tmp/wordpress-tests-lib"}
WP_CORE_DIR=${WP_CORE_DIR:-"$WORKING_DIR/tmp/wordpress"}
WP_PLUGINS_DIR="$WP_CORE_DIR/wp-content/plugins"

rm -rf "$WORKING_DIR/tmp"

download() {
	local url="$1"
	local output="$2"
	if command -v curl >/dev/null 2>&1; then
		curl -sL "$url" -o "$output"
	elif command -v wget >/dev/null 2>&1; then
		wget -q -O "$output" "$url"
	else
		echo "Install curl or wget"
		exit 1
	fi
}

if [[ $WP_VERSION =~ [0-9]+\.[0-9]+(\.[0-9]+)? ]]; then
	WP_TESTS_TAG="tags/$WP_VERSION"
else
	download "https://api.wordpress.org/core/version-check/1.7/" /tmp/wp-latest.json
	LATEST_VERSION=$(grep -o '"version":"[^"]*' /tmp/wp-latest.json | head -1 | sed 's/"version":"//')
	[[ -z "$LATEST_VERSION" ]] && { echo "Could not get latest WP version"; exit 1; }
	WP_TESTS_TAG="tags/$LATEST_VERSION"
fi

set -e

install_wp() {
	[[ -d "$WP_CORE_DIR" ]] && return
	mkdir -p "$WP_CORE_DIR"
	local archive="latest"
	[[ "$WP_VERSION" != "latest" ]] && archive="wordpress-$WP_VERSION"
	download "https://wordpress.org/${archive}.tar.gz" /tmp/wordpress.tar.gz
	tar --strip-components=1 -xzf /tmp/wordpress.tar.gz -C "$WP_CORE_DIR"
	download "https://raw.githubusercontent.com/markoheijnen/wp-mysqli/master/db.php" "$WP_CORE_DIR/wp-content/db.php"
}

install_test_suite() {
	if [[ ! -d "$WP_TESTS_DIR" ]]; then
		mkdir -p "$WP_TESTS_DIR/includes"
		command -v svn >/dev/null 2>&1 || { echo "Install svn: brew install svn"; exit 1; }
		svn co --quiet "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/" "$WP_TESTS_DIR/includes/"
	fi
	cd "$WP_TESTS_DIR"
	if [[ ! -f wp-tests-config.php ]]; then
		download "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"
		local iopt="-i"
		[[ "$(uname -s)" == "Darwin" ]] && iopt="-i.bak"
		sed $iopt "s|dirname( __FILE__ ) . '/src/'|'${WP_CORE_DIR%/}/'|" wp-tests-config.php
		sed $iopt "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
		sed $iopt "s/yourusernamehere/$DB_USER/" wp-tests-config.php
		sed $iopt "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
		sed $iopt "s|localhost|${DB_HOST}|" wp-tests-config.php
	fi
}

install_elementor() {
	local zip_path="$WORKING_DIR/tmp/elementor.zip"
	rm -f "$zip_path"
	download "https://downloads.wordpress.org/plugin/elementor.latest-stable.zip" "$zip_path"
	unzip -q -o "$zip_path" -d "$WP_PLUGINS_DIR"
	rm -f "$zip_path"
}

install_plugin_symlink() {
	mkdir -p "$WP_PLUGINS_DIR"
	if [[ -L "$WP_PLUGINS_DIR/$PLUGIN_SLUG" ]] || [[ -d "$WP_PLUGINS_DIR/$PLUGIN_SLUG" ]]; then
		rm -rf "$WP_PLUGINS_DIR/$PLUGIN_SLUG"
	fi
	ln -s "$WORKING_DIR" "$WP_PLUGINS_DIR/$PLUGIN_SLUG"
}

install_db() {
	local parts=(${DB_HOST//:/ })
	local hostname="${parts[0]}"
	local port_socket="${parts[1]}"
	local extra=""
	if [[ -n "$hostname" ]]; then
		if [[ "$port_socket" =~ ^[0-9]+$ ]]; then
			extra=" --host=$hostname --port=$port_socket --protocol=tcp"
		elif [[ -n "$port_socket" ]]; then
			extra=" --socket=$port_socket"
		else
			extra=" --host=$hostname --protocol=tcp"
		fi
	fi
	
	if ! mysqladmin ping --user="$DB_USER" --password="$DB_PASS" $extra >/dev/null 2>&1; then
		echo ""
		echo "Error: Cannot connect to MySQL at $DB_HOST"
		echo ""
		echo "Troubleshooting:"
		echo "  - Verify MySQL is running"
		echo "  - Check credentials are correct"
		echo "  - For Local by Flywheel, ensure port is correct (check Local > Database tab)"
		echo "  - Try: mysql -h $hostname -P ${port_socket:-3306} -u $DB_USER -p$DB_PASS -e 'SELECT 1'"
		exit 1
	fi
	
	mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS" $extra 2>/dev/null || true
}

echo ""
echo "==> Step 1/5: Installing WordPress ${WP_VERSION}..."
install_wp
echo "✓ WordPress installed"

echo ""
echo "==> Step 2/5: Installing WordPress test suite..."
install_test_suite
echo "✓ Test suite installed"

echo ""
echo "==> Step 3/5: Downloading Elementor plugin..."
install_elementor
echo "✓ Elementor installed"

echo ""
echo "==> Step 4/5: Creating plugin symlink..."
install_plugin_symlink
echo "✓ Plugin symlinked"

echo ""
echo "==> Step 5/5: Creating test database..."
install_db
echo "✓ Database created"

echo ""
echo "================================================"
echo "✓ PHPUnit test environment setup complete!"
echo "================================================"
echo ""
echo "Test environment details:"
echo "  - WordPress: $WP_CORE_DIR"
echo "  - Test suite: $WP_TESTS_DIR"
echo "  - Database: $DB_NAME @ $DB_HOST"
echo ""
echo "Run tests with:"
echo "  WP_TESTS_DIR=$WP_TESTS_DIR composer run test:integration"
echo ""
echo "Or set environment variable and run:"
echo "  export WP_TESTS_DIR=$WP_TESTS_DIR"
echo "  composer run test:integration"
echo ""
echo "For unit tests (no WordPress):"
echo "  composer test"
echo ""
