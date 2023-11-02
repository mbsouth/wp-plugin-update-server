<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Nonce {
	const DEFAULT_EXPIRY_LENGTH = MINUTE_IN_SECONDS / 2;
	const NONCE_ONLY            = 1;
	const NONCE_INFO_ARRAY      = 2;

	protected static $true_nonce;
	protected static $expiry_length;
	protected static $doing_update_api_request = null;
	protected static $private_auth_key;
	protected static $auth_header_name;

	public static function activate() {
		$result = self::maybe_create_or_upgrade_db();

		if ( ! $result ) {
			$error_message = __( 'Failed to create the necessary database table(s).', 'wppus' );

			die( $error_message ); // @codingStandardsIgnoreLine
		}
	}

	public static function deactivate() {}

	public static function uninstall() {}

	public static function maybe_create_or_upgrade_db() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		$table = $wpdb->prefix . 'wppus_nonce';
		$sql   =
			'CREATE TABLE ' . $table . " (
				id int(12) NOT NULL auto_increment,
				nonce varchar(255) NOT NULL,
				true_nonce tinyint(2) NOT NULL DEFAULT '1',
				expiry int(12) NOT NULL,
				data longtext NOT NULL,
				PRIMARY KEY (id),
				KEY nonce (nonce)
			)" . $charset_collate . ';';

		dbDelta( $sql );

		$table = $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . 'wppus_nonce' . "'" ); // @codingStandardsIgnoreLine

		if ( $wpdb->prefix . 'wppus_nonce' !== $table ) {

			return false;
		}

		return true;
	}

	public static function register() {

		if ( ! self::is_doing_api_request() ) {
			add_action( 'init', array( get_class(), 'add_endpoints' ), 10, 0 );
			add_action( 'wp', array( get_class(), 'register_nonce_cleanup' ) );
			add_action( 'wppus_nonce_cleanup', array( get_class(), 'clear_nonces' ) );
		}

		add_action( 'parse_request', array( get_class(), 'parse_request' ), -99, 0 );

		add_filter( 'query_vars', array( get_class(), 'query_vars' ), -99, 1 );
	}

	public static function init_auth( $private_auth_key, $auth_header_name = null ) {
		self::$private_auth_key = $private_auth_key;
		self::$auth_header_name = $auth_header_name;
	}

	public static function register_nonce_cleanup() {
		$d = new DateTime( 'now', new DateTimeZone( wp_timezone_string() ) );

		if ( ! wp_next_scheduled( 'wppus_nonce_cleanup' ) ) {
			$d->setTime( 0, 0, 0 );
			wp_schedule_event( $d->getTimestamp(), 'daily', 'wppus_nonce_cleanup' );
		}
	}

	public static function is_doing_api_request() {

		if ( null === self::$doing_update_api_request ) {
			self::$doing_update_api_request =
				false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-token' ) &&
				false !== strpos( $_SERVER['REQUEST_URI'], 'wppus-nonce' );
		}

		return self::$doing_update_api_request;
	}

	public static function add_endpoints() {
		add_rewrite_rule(
			'^wppus-token/*?$',
			'index.php?$matches[1]&action=token&__wppus_nonce_api=1&',
			'top'
		);
		add_rewrite_rule(
			'^wppus-nonce/*?$',
			'index.php?$matches[1]&action=nonce&__wppus_nonce_api=1&',
			'top'
		);
	}

	public static function parse_request() {
		global $wp;

		if ( isset( $wp->query_vars['__wppus_nonce_api'] ) ) {
			$response = __( 'Malformed request', 'wppus' );
			$code     = 400;

			if ( ! self::authorize() ) {
				$response = __( 'Unauthorized access', 'wppus' );
				$code     = 403;
			} elseif ( isset( $wp->query_vars['action'] ) ) {
				$method = $wp->query_vars['action'];

				if (
					is_string( $wp->query_vars['action'] ) &&
					method_exists(
						get_class(),
						'generate_' . $wp->query_vars['action'] . '_api_response'
					)
				) {
					$method = 'generate_' . $wp->query_vars['action'] . '_api_response';

					unset( $wp->query_vars['action'] );

					$response = self::$method( $wp->query_vars );
					$code     = 200;
				}
			}

			// @todo doc
			$code     = apply_filters( 'wppus_nonce_api_code', $code, $wp->query_vars );
			$response = apply_filters( 'wppus_nonce_api_response', $response, $code, $wp->query_vars );

			wp_send_json( $response, $code );
		}
	}

	public static function query_vars( $query_vars ) {
		$query_vars = array_merge(
			$query_vars,
			array(
				'__wppus_nonce_api',
				'api_auth_key',
				'action',
				'expiry_length',
				'data',
			)
		);

		return $query_vars;
	}

	public static function create_nonce(
		$true_nonce = true,
		$expiry_length = self::DEFAULT_EXPIRY_LENGTH,
		$data = array(),
		$return_type = self::NONCE_ONLY,
		$store = true,
		$delegate = false,
		$delegate_args = array()
	) {

		if ( $delegate && is_array( $delegate_args ) && is_callable( $delegate ) ) {
			$delegate_args['true_nonce']    = $true_nonce;
			$delegate_args['expiry_length'] = $expiry_length;
			$nonce                          = call_user_func_array( $delegate, $delegate_args );
		} else {
			$id    = self::generate_id();
			$nonce = md5( wp_salt( 'nonce' ) . $id . microtime( true ) );
		}

		$data   = is_array( $data ) ? filter_var_array( $data, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) : false;
		$expiry = isset( $data['permanent'] ) && $data['permanent'] ? 0 : time() + abs( intval( $expiry_length ) );
		$data   = $data ? wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : '{}';

		if ( $store ) {
			$result = self::store_nonce( $nonce, $true_nonce, $expiry, $data );
		} else {
			$result = array(
				'nonce'      => $nonce,
				'true_nonce' => (bool) $true_nonce,
				'expiry'     => $expiry,
				'data'       => $data,
			);
		}

		if ( self::NONCE_INFO_ARRAY === $return_type ) {

			if ( is_array( $result ) ) {
				$result['data'] = json_decode( $result['data'], true );
			}

			$return = $result;
		} else {
			$return = ( $result ) ? $result['nonce'] : $result;
		}

		return $return;
	}

	public static function get_nonce_expiry( $nonce ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wppus_nonce';
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE nonce = %s;", // @codingStandardsIgnoreLine
				$nonce
			)
		);

		if ( ! $row ) {
			$nonce_expiry = 0;
		} else {
			$nonce_expiry = $row->expiry;
		}

		return $nonce_expiry;
	}

	public static function validate_nonce( $value ) {

		if ( empty( $value ) ) {

			return false;
		}

		$nonce = self::fetch_nonce( $value );
		$valid = ( $nonce === $value );

		return $valid;
	}


	public static function delete_nonce( $value ) {
		global $wpdb;

		$table  = $wpdb->prefix . 'wppus_nonce';
		$where  = array( 'nonce' => $value );
		$result = $wpdb->delete( $table, $where ); // @codingStandardsIgnoreLine

		return (bool) $result;
	}

	public static function clear_nonces() {

		if ( defined( 'WP_SETUP_CONFIG' ) || defined( 'WP_INSTALLING' ) ) {

			return;
		}

		global $wpdb;

		$sql      = "DELETE FROM {$wpdb->prefix}wppus_nonce
			WHERE expiry < %d
			AND (
				JSON_VALID(`data`) = 1
				AND (
					JSON_EXTRACT(`data` , '$.permanent') IS NULL
					OR JSON_EXTRACT(`data` , '$.permanent') = 0
					OR JSON_EXTRACT(`data` , '$.permanent') = '0'
					OR JSON_EXTRACT(`data` , '$.permanent') = false
				)
			) OR
			JSON_VALID(`data`) = 0;";
		$sql_args = array( time() - self::DEFAULT_EXPIRY_LENGTH );
		// @todo doc
		$sql      = apply_filters( 'wppus_clear_nonces_query', $sql, $sql_args );
		$sql_args = apply_filters( 'wppus_clear_nonces_query_args', $sql_args, $sql );
		$result   = $wpdb->query( $wpdb->prepare( $sql, $sql_args ) ); // @codingStandardsIgnoreLine

		return (bool) $result;
	}

	protected static function generate_token_api_response( $payload ) {
		$token = self::create_nonce(
			false,
			isset( $payload['expiry_length'] ) && is_numeric( $payload['expiry_length'] ) ?
				$payload['expiry_length'] :
				self::DEFAULT_EXPIRY_LENGTH,
			isset( $payload['data'] ) ? $payload['data'] : array(),
			self::NONCE_INFO_ARRAY,
		);

		return $token;
	}

	protected static function generate_nonce_api_response( $payload ) {
		$nonce = self::create_nonce(
			true,
			isset( $payload['expiry_length'] ) && is_numeric( $payload['expiry_length'] ) ?
				$payload['expiry_length'] :
				self::DEFAULT_EXPIRY_LENGTH,
			isset( $payload['data'] ) ? $payload['data'] : array(),
			self::NONCE_INFO_ARRAY,
		);

		return $nonce;
	}

	protected static function fetch_nonce( $value ) {
		global $wpdb;

		$table = $wpdb->prefix . 'wppus_nonce';
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE nonce = %s;", // @codingStandardsIgnoreLine
				$value
			)
		);
		$nonce = null;

		if ( $row ) {
			$data = is_string( $row->data ) ? json_decode( $row->data, true ) : array();

			if ( ! is_array( $data ) ) {
				$data = array();
			}

			if (
				$row->expiry < time() &&
				! (
					isset( $data['permanent'] ) &&
					$data['permanent']
				)
			) {
				// @todo doc
				$row->nonce = apply_filters(
					'wppus_expire_nonce',
					null,
					$row->nonce,
					$row->true_nonce,
					$row->expiry,
					$data,
					$row
				);
			}
			// @todo doc
			$delete_nonce = apply_filters(
				'wppus_delete_nonce',
				$row->true_nonce || null === $row->nonce,
				$row->true_nonce,
				$row->expiry,
				$data,
				$row
			);

			if ( $delete_nonce ) {
				self::delete_nonce( $value );
			}

			// @todo doc
			$nonce = apply_filters(
				'wppus_fetch_nonce',
				$row->nonce,
				$row->true_nonce,
				$row->expiry,
				$data,
				$row
			);
		}

		return $nonce;
	}

	protected static function store_nonce( $nonce, $true_nonce, $expiry, $data ) {
		global $wpdb;

		$table  = $wpdb->prefix . 'wppus_nonce';
		$data   = array(
			'nonce'      => $nonce,
			'true_nonce' => (bool) $true_nonce,
			'expiry'     => $expiry,
			'data'       => $data,
		);
		$result = $wpdb->insert( $table, $data ); // @codingStandardsIgnoreLine

		if ( (bool) $result ) {

			return $data;
		}

		return false;
	}

	protected static function generate_id() {
		require_once ABSPATH . 'wp-includes/class-phpass.php';

		$hasher = new PasswordHash( 8, false );

		return md5( $hasher->get_random_bytes( 100, false ) );
	}

	protected static function authorize() {
		$key = false;

		if (
			self::$auth_header_name &&
			isset( $_SERVER[ self::$auth_header_name ] ) &&
			! empty( $_SERVER[ self::$auth_header_name ] )
		) {
			$key = $_SERVER[ self::$auth_header_name ];
		} else {
			global $wp;

			if (
				isset( $wp->query_vars['api_auth_key'] ) &&
				is_string( $wp->query_vars['api_auth_key'] ) &&
				! empty( $wp->query_vars['api_auth_key'] )
			) {
				$key = $wp->query_vars['api_auth_key'];
			}
		}

		return apply_filters(
			'wppus_nonce_authorize',
			self::$private_auth_key === $key,
			$key,
			self::$private_auth_key
		);
	}

}
