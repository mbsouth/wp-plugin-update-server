<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

### EXAMPLE INTEGRATION WITH WP PACKAGES UPDATE SERVER ###

# DO NOT USE THIS FILE AS IT IS IN PRODUCTION !!!
# It is just a collection of basic functions and snippets, and they do not
# perform the necessary checks to ensure data integrity ; they assume that all
# the requests are successful, and do not check paths or permissions.
# They also assume that the package necessitates a license key.

# replace https://server.domain.tld/ with the URL of the server where
# WP Packages Update Server is installed in wppus.json

class WPPUS_API {
	private static $config;
	private static $url;
	private static $package_name;
	private static $package_script;
	private static $zip_name;
	private static $version;
	private static $license_key;
	private static $license_signature;
	private static $domain;

	public static function init() {
		# load the configuration file
		self::$config = json_decode( file_get_contents( __DIR__ . '/wppus.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		# define the url of the server
		self::$url = self::$config['server'];
		# define the package name
		self::$package_name = basename( __DIR__ );
		# define the package script
		self::$package_script = __DIR__ . '/' . basename( __DIR__ ) . '.php';
		# define the update zip archive name
		self::$zip_name = self::$package_name . '.zip';
		# define the current version of the package from the wppus.json file
		self::$version = self::$config['packageData']['Version'];
		# define license_key from the wppus.json file
		self::$license_key = isset( self::$config['licenseKey'] ) ? self::$config['licenseKey'] : '';
		# define license_signature from the wppus.json file
		self::$license_signature = isset( self::$config['licenseSignature'] ) ? self::$config['licenseSignature'] : '';

		# define the domain
		if ( 'Darwin' === PHP_OS ) {
			# macOS
			self::$domain = exec( 'ioreg -rd1 -c IOPlatformExpertDevice | awk -F\'"\' \'/IOPlatformUUID/{print $4}\'' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec, WordPress.WP.GlobalVariablesOverride.Prohibited
		} elseif ( 'Linux' === PHP_OS ) {
			# Ubuntu
			self::$domain = exec( 'cat /var/lib/dbus/machine-id' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec, WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	### INSTALLING THE PACKAGE ###

	public static function install( $license_key ) {
		# add the license key to wppus.json
		self::$config['licenseKey'] = $license_key;
		file_put_contents( __DIR__ . '/wppus.json', json_encode( self::$config, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.json_encode_json_encode

		# add a file '.installed' in current directory
		// touch "$(cd "$(dirname "$0")"; pwd -P)/.installed" (convert to php)
		file_put_contents( __DIR__ . '/.installed', '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	### UNINSTALLING THE PACKAGE ###

	public static function uninstall() {
		# remove the license key from wppus.json
		unset( self::$config['licenseKey'] );
		file_put_contents( __DIR__ . '/wppus.json', json_encode( self::$config, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.json_encode_json_encode

		# remove the file '.installed' from current directory
		unlink( __DIR__ . '/.installed' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
	}

	### CHECKING IF THE PACKAGE IS INSTALLED ###

	public static function is_installed() {
		# check if the file '.installed' exists in current directory
		return file_exists( __DIR__ . '/.installed' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_exists
	}

	### SENDING AN API REQUEST ###

	private static function send_api_request( $action, $args = array() ) {
		# build the request url
		$full_url = self::$url . '/' . $action . '/?' . http_build_query( $args );

		# initialize cURL
		$ch = curl_init(); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init

		# set the options
		curl_setopt( $ch, CURLOPT_URL, $full_url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $ch, CURLOPT_TIMEOUT, 20 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt

		# make the request
		$response = curl_exec( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec

		# close the cURL resource
		curl_close( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close

		# return the response
		return $response;
	}

	### CHECKING FOR UPDATES ###

	private static function check_for_updates() {
		# build the request url
		$endpoint = 'wppus-update-api';
		$args     = array(
			'action'            => 'get_metadata',
			'package_id'        => rawurlencode( self::$package_name ),
			'installed_version' => rawurlencode( self::$version ),
			'license_key'       => rawurlencode( self::$license_key ),
			'license_signature' => rawurlencode( self::$license_signature ),
			'update_type'       => 'Generic',
		);
		# make the request
		$response = self::send_api_request( $endpoint, $args );
		# return the response
		return $response;
	}

	### ACTIVATING A LICENSE ###

	public static function activate() {
		$endpoint = 'wppus-license-api';
		$args     = array(
			'action'          => 'activate',
			'license_key'     => rawurlencode( self::$license_key ),
			'allowed_domains' => rawurlencode( self::$domain ),
			'package_slug'    => rawurlencode( self::$package_name ),
		);
		# make the request
		$response = self::send_api_request( $endpoint, $args );
		# get the signature from the response
		$signature = rawurldecode( json_decode( $response, true )['license_signature'] );
		# add the license signature to wppus.json
		self::$config['licenseSignature'] = $signature;
		file_put_contents( __DIR__ . '/wppus.json', json_encode( self::$config, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.json_encode_json_encode
		self::$license_signature = $signature;
	}

	### DEACTIVATING A LICENSE ###

	public static function deactivate() {
		# build the request url
		$endpoint = 'wppus-license-api';
		$args     = array(
			'action'          => 'deactivate',
			'license_key'     => rawurlencode( self::$license_key ),
			'allowed_domains' => rawurlencode( self::$domain ),
			'package_slug'    => rawurlencode( self::$package_name ),
		);
		# make the request
		self::send_api_request( $endpoint, $args );
		# remove the license signature from wppus.json
		unset( self::$config['licenseSignature'] );
		file_put_contents( __DIR__ . '/wppus.json', json_encode( self::$config, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.json_encode_json_encode
		self::$license_signature = '';
	}

	### DOWNLOADING THE PACKAGE ###

	private static function download_update( $response ) {
		# get the download url from the response
		$url = rawurldecode( json_decode( $response, true )['download_url'] );
		# set the path to the downloaded file
		$output_file = '/tmp/' . self::$zip_name;
		# make the request
		$ch = curl_init(); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
		curl_setopt( $ch, CURLOPT_URL, $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $ch, CURLOPT_TIMEOUT, 20 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt

		$response = curl_exec( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec

		curl_close( $ch ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
		file_put_contents( $output_file, $response ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		# return the path to the downloaded file
		return $output_file;
	}

	### GETTING THE PACKAGE VERSION ###

	public static function get_version() {
		# return the current version of the package
		return self::$version;
	}

	### UPDATING THE PACKAGE ###

	public static function update() {
		# check for updates
		$response = self::check_for_updates();
		# get the version from the response
		$new_version = json_decode( $response, true )['version'];

		if ( version_compare( $new_version, self::$version, '>' ) ) {
			# download the update
			$output_file = self::download_update( $response );

			# extract the zip in /tmp/$(package_name)
			$zip = new ZipArchive();

			$zip->open( $output_file );
			$zip->extractTo( '/tmp/' );
			$zip->close();

			# get the permissions of the old file
			$octal_mode = substr( sprintf( '%o', fileperms( self::$package_script ) ), -4 );
			# set the permissions of the new file to the permissions of the old file
			chmod( $octal_mode, '/tmp/' . self::$package_name . '/' . self::$package_name . '.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod

			# move the updated main script to the current directory ;
			# the updated main script is in charge of overriding the update
			# scripts by moving files around after update
			foreach ( glob( '/tmp/' . self::$package_name . '/*' ) as $file ) {

				if ( ! is_dir( $file ) && pathinfo( $file, PATHINFO_FILENAME ) !== pathinfo( self::$package_script, PATHINFO_FILENAME ) ) {
					rename( $file, dirname( self::$package_script ) . '/' . basename( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
				}
			}

			# add the license key to wppus.json
			self::$config['licenseKey'] = self::$license_key;
			file_put_contents( __DIR__ . '/wppus.json', json_encode( self::$config, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.json_encode_json_encode
			# add the license signature to wppus.json
			self::$config['licenseSignature'] = self::$license_signature;
			file_put_contents( __DIR__ . '/wppus.json', json_encode( self::$config, JSON_PRETTY_PRINT ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.json_encode_json_encode

			# remove the directory
			foreach ( glob( '/tmp/' . self::$package_name . '/*' ) as $file ) {
				unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			}

			rmdir( '/tmp/' . self::$package_name ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			# remove the zip
			unlink( $output_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink

		}
	}

	### GETTING THE PACKAGE INFO ###

	public static function get_update_info() {
		# get the update information
		return self::check_for_updates();
	}
}