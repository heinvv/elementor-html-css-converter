# PHPUnit Setup Guide

Complete guide for setting up and running PHPUnit tests for the Elementor HTML CSS Converter plugin.

---

## Prerequisites

Before running PHPUnit tests, ensure you have:

- **PHP 7.4+** with extensions: `mbstring`, `xml`, `mysqli`, `pdo_mysql`
- **Composer** - [Install Composer](https://getcomposer.org/)
- **SVN (Subversion)** - For downloading WordPress test suite
  - macOS: `brew install svn`
  - Ubuntu/Debian: `sudo apt-get install subversion`
- **MySQL** - Local database server
  - [Local by Flywheel](https://localwp.com/) (recommended for WordPress dev)
  - Or MySQL/MariaDB standalone
- **unzip** - For extracting archives
  - Usually pre-installed; if not: `brew install unzip` or `sudo apt-get install unzip`

---

## Setup with Local by Flywheel (Recommended)

Local by Flywheel provides an isolated MySQL database per site, making it ideal for WordPress plugin development.

### 1. Get Database Credentials

1. Open your site in Local
2. Click the **Database** tab
3. Note the connection details:
   - **Host**: typically `127.0.0.1`
   - **Port**: varies per site (e.g., `10003`, `10004`)
   - **Username**: `root`
   - **Password**: `root`
   - **Database name**: varies (e.g., `local`)

### 2. Run Installation Script

```bash
cd /path/to/elementor-html-css-converter
composer run test:install
```

When prompted, enter:
- **Database name**: `elementor-tests` (creates new DB, keeps your site DB untouched)
- **Username**: `root`
- **Password**: `root`
- **Host**: `127.0.0.1:PORT` (e.g., `127.0.0.1:10003` - use the port from Local)
- **WordPress version**: `latest` (or specific version like `6.7`)

The script will:
- Check for required tools (svn, unzip, mysql, mysqladmin)
- Download and install WordPress
- Install the WordPress test suite
- Download Elementor plugin
- Create a test database
- Symlink your plugin for testing

### 3. Run Tests

**Integration tests (with WordPress + Elementor):**

```bash
WP_TESTS_DIR=./tmp/wordpress-tests-lib composer run test:integration
```

**Unit tests (fast, no WordPress):**

```bash
composer test
```

---

## Setup with Standalone MySQL

If you're using MySQL or MariaDB directly (not via Local):

### 1. Ensure MySQL is Running

**macOS (Homebrew):**
```bash
brew services start mysql
```

**Linux:**
```bash
sudo systemctl start mysql
```

### 2. Create Test User (Optional)

```bash
mysql -u root -p
```

Then in MySQL:
```sql
CREATE USER 'admin'@'localhost' IDENTIFIED BY 'admin';
GRANT ALL PRIVILEGES ON *.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Run Installation Script

```bash
composer run test:install
```

When prompted:
- **Database name**: `elementor-tests`
- **Username**: `admin` (or `root`)
- **Password**: `admin` (or your root password)
- **Host**: `127.0.0.1` (or just press Enter for default)
- **WordPress version**: `latest`

### 4. Run Tests

```bash
WP_TESTS_DIR=./tmp/wordpress-tests-lib composer run test:integration
```

---

## Troubleshooting

### "Connection refused" Error

**Symptoms:**
```
Error establishing a database connection
mysqli_real_connect(): (HY000/2002): Connection refused
```

**Solutions:**

1. **Check MySQL is running**
   - For Local: Ensure site is started in Local app
   - For standalone MySQL: `brew services list` or `systemctl status mysql`

2. **Verify port**
   - Local assigns unique ports per site (check Database tab)
   - Use format: `127.0.0.1:PORT` when prompted for host
   - Test connection: 
     ```bash
     mysql -h 127.0.0.1 -P 10003 -u root -proot -e "SELECT 1"
     ```

3. **Check credentials**
   - Local uses `root`/`root` by default
   - Verify in Local > Database tab

### "wordpresswp-settings.php not found"

**Symptoms:**
```
Failed opening required '.../tmp/wordpresswp-settings.php'
```

**Solution:**
This was a trailing slash bug in the ABSPATH configuration, now fixed. Re-run the installation:

```bash
rm -rf ./tmp
composer run test:install
```

### "mysqladmin: command not found"

**Symptoms:**
```
bash: mysqladmin: command not found
```

**Solution:**
Install MySQL client tools:
- macOS: `brew install mysql-client`
- Ubuntu/Debian: `sudo apt-get install mysql-client`

After installation, you may need to add it to your PATH:
```bash
# macOS
echo 'export PATH="/opt/homebrew/opt/mysql-client/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

### "svn: command not found"

**Symptoms:**
```
Error: Missing required tools: svn
```

**Solution:**
Install Subversion:
- macOS: `brew install svn`
- Ubuntu/Debian: `sudo apt-get install subversion`

### Tests Skip with "Elementor required"

**Expected behavior**: Some integration tests require Elementor to be loaded. Approximately 16 tests may skip if Elementor fails to load. This is normal for unit tests.

If integration tests are failing with Elementor-related errors:

1. Verify Elementor was downloaded correctly:
   ```bash
   ls -la ./tmp/wordpress/wp-content/plugins/elementor/
   ```

2. Re-run the installation if needed:
   ```bash
   rm -rf ./tmp
   composer run test:install
   ```

### Database Already Exists Error

**Symptoms:**
```
mysqladmin: CREATE DATABASE failed; error: 'Can't create database 'elementor-tests'; database exists'
```

**Solution:**
This is expected behavior if you re-run the install script. The script attempts to create the database but continues if it already exists (non-fatal error).

To start fresh:
```bash
# Drop the existing test database
mysql -h 127.0.0.1 -P <PORT> -u root -proot -e "DROP DATABASE IF EXISTS elementor_tests"

# Re-run installation
rm -rf ./tmp
composer run test:install
```

### Permission Denied Errors

**Symptoms:**
```
Permission denied: ./tmp/wordpress
```

**Solution:**
Ensure you have write permissions in the plugin directory:
```bash
chmod -R u+w ./tmp
```

Or remove and recreate:
```bash
rm -rf ./tmp
composer run test:install
```

---

## Quick Reference

### Commands

| Command | Description |
|---------|-------------|
| `composer test` | Run unit tests (no WordPress) |
| `composer test:integration` | Run integration tests (needs setup) |
| `composer test:install` | Install test environment |
| `composer run coverage` | Generate coverage report |

### Environment Variables

Set these before running integration tests:

```bash
# Required for integration tests
export WP_TESTS_DIR=./tmp/wordpress-tests-lib

# Optional: Enable multisite testing
export WP_MULTISITE=1

# Then run tests
composer run test:integration
```

### File Locations

After installation, you'll find:

- **Install script**: `bin/install-wp-tests-local.sh`
- **WordPress core**: `./tmp/wordpress/`
- **Test suite**: `./tmp/wordpress-tests-lib/`
- **Test config**: `./tmp/wordpress-tests-lib/wp-tests-config.php`
- **Elementor**: `./tmp/wordpress/wp-content/plugins/elementor/`
- **Plugin symlink**: `./tmp/wordpress/wp-content/plugins/elementor-html-css-converter/` → `../.../` (your plugin directory)

### Common Workflows

**First-time setup:**
```bash
composer install
composer run test:install
WP_TESTS_DIR=./tmp/wordpress-tests-lib composer run test:integration
```

**Quick test run (after setup):**
```bash
WP_TESTS_DIR=./tmp/wordpress-tests-lib composer run test:integration
```

**Run specific test:**
```bash
WP_TESTS_DIR=./tmp/wordpress-tests-lib composer run test:integration -- --filter test_variable_extractor
```

**Clean and reinstall:**
```bash
rm -rf ./tmp
composer run test:install
```

---

## Test Types

### Unit Tests

Fast tests that don't require WordPress or Elementor:

```bash
composer test
```

- Run in seconds
- Test individual classes and functions
- Mock external dependencies
- No database required

### Integration Tests

Tests that require WordPress, Elementor, and MySQL:

```bash
WP_TESTS_DIR=./tmp/wordpress-tests-lib composer run test:integration
```

- Test plugin integration with WordPress/Elementor
- Require full test environment
- Use real database
- Test REST API endpoints

---

## CI/CD

The GitHub Actions workflow automatically runs both unit and integration tests on every push. See `.github/workflows/phpunit.yml` for the CI configuration.

**Key differences from local setup:**
- Uses MySQL Docker container
- Installs WordPress to `/tmp` instead of `./tmp`
- Skips database creation (pre-created by MySQL service)
- Tests multiple PHP versions (7.4, 8.0, 8.1, 8.2)

---

## Additional Resources

- [PHPUnit Test Plan](phpunit-test-plan.md) - Comprehensive test plan and coverage goals
- [WordPress Plugin Testing Guide](https://make.wordpress.org/cli/handbook/misc/plugin-unit-tests/)
- [Elementor Development](https://developers.elementor.com/)

---

## Getting Help

If you encounter issues not covered in this guide:

1. Check the [phpunit-test-plan.md](phpunit-test-plan.md) for test-specific documentation
2. Review the install script: `bin/install-wp-tests-local.sh`
3. Check GitHub Actions logs for CI test results
4. Verify your Local by Flywheel site is running (if using Local)
