#!/usr/bin/env bash

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DB_CREATE=${6-false}

WORKING_DIR=$(cd "$(dirname "$0")/.." && pwd)
PLUGIN_SLUG="elementor-html-css-converter"
WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress/}
WP_PLUGINS_DIR="$WP_CORE_DIR/wp-content/plugins"

download() {
	local url="$1"
	local output="$2"
	if command -v curl >/dev/null 2>&1; then
		curl --location --fail --show-error --silent --output "$output" "$url"
	elif command -v wget >/dev/null 2>&1; then
		wget -nv -O "$output" "$url"
	else
		echo "Error: Neither curl nor wget found."
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
	if [[ $WP_VERSION == 'nightly' || $WP_VERSION == 'trunk' ]]; then
		mkdir -p /tmp/wordpress-nightly
		download "https://wordpress.org/nightly-builds/wordpress-latest.zip" /tmp/wordpress-nightly/wordpress-nightly.zip
		unzip -q /tmp/wordpress-nightly/wordpress-nightly.zip -d /tmp/wordpress-nightly/
		mv /tmp/wordpress-nightly/wordpress/* "$WP_CORE_DIR"
	else
		local archive="latest"
		[[ "$WP_VERSION" != "latest" ]] && archive="wordpress-$WP_VERSION"
		download "https://wordpress.org/${archive}.tar.gz" /tmp/wordpress.tar.gz
		tar --strip-components=1 -xzf /tmp/wordpress.tar.gz -C "$WP_CORE_DIR"
	fi
	if [[ -z "$(ls -A $WP_CORE_DIR/wp-content/themes/twentytwentyone 2>/dev/null)" ]]; then
		mkdir -p /tmp/twentytwentyone
		download "https://downloads.wordpress.org/theme/twentytwentyone.2.0.zip" /tmp/twentytwentyone/twentytwentyone.zip
		unzip -q /tmp/twentytwentyone/twentytwentyone.zip -d /tmp/twentytwentyone/
		mv /tmp/twentytwentyone/twentytwentyone "$WP_CORE_DIR/wp-content/themes"
	fi
	download "https://raw.githubusercontent.com/markoheijnen/wp-mysqli/master/db.php" "$WP_CORE_DIR/wp-content/db.php"
}

install_test_suite() {
	if [[ $(uname -s) == 'Darwin' ]]; then
		local iopt='-i.bak'
	else
		local iopt='-i'
	fi
	if [[ ! -d "$WP_TESTS_DIR" ]]; then
		mkdir -p "$WP_TESTS_DIR"
		svn co --quiet "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/" "$WP_TESTS_DIR/includes"
	fi
	cd "$WP_TESTS_DIR"
	if [[ ! -f wp-tests-config.php ]]; then
		download "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"
		sed $iopt "s|dirname( __FILE__ ) . '/src/'|'${WP_CORE_DIR%/}/'|" "$WP_TESTS_DIR/wp-tests-config.php"
		sed $iopt "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
		sed $iopt "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
		sed $iopt "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
		sed $iopt "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR/wp-tests-config.php"
	fi
}

install_elementor() {
	local zip_path="/tmp/elementor.zip"
	rm -f "$zip_path"
	download "https://downloads.wordpress.org/plugin/elementor.latest-stable.zip" "$zip_path"
	mkdir -p "$WP_PLUGINS_DIR"
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
	[[ "$SKIP_DB_CREATE" == "true" ]] && return 0
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
	mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS" $extra 2>/dev/null || true
}

install_wp
install_test_suite
install_elementor
install_plugin_symlink
install_db
