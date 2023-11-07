<?php

use PhpS3\PhpS3;
use PhpS3\PhpS3Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPPUS_Cloud_Storage_Manager {
	protected static $instance;
	protected static $config;
	protected static $cloud_storage;

	protected $doing_redirect = false;

	public const DOWNLOAD_URL_LIFETIME = MINUTE_IN_SECONDS;

	public function __construct( $init_hooks = false ) {
		require_once WPPUS_PLUGIN_PATH . 'lib/PhpS3/PhpS3.php';

		$config = self::get_config();

		if ( ! self::$cloud_storage instanceof PhpS3 ) {
			self::$cloud_storage = new PhpS3(
				$config['access_key'],
				$config['secret_key'],
				true,
				$config['endpoint'],
				$config['region'],
			);

			self::$cloud_storage->setExceptions();
		}

		if ( $init_hooks ) {

			if ( ! wppus_is_doing_api_request() ) {
				add_action( 'wp_ajax_wppus_cloud_storage_test', array( $this, 'cloud_storage_test' ), 10, 0 );
				add_action( 'wppus_remote_sources_options_updated', array( $this, 'wppus_remote_sources_options_updated' ), 10, 0 );
			}

			if ( get_option( 'wppus_use_cloud_storage' ) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
				add_action( 'wppus_saved_remote_package_to_local', array( $this, 'wppus_saved_remote_package_to_local' ), 10, 3 );
				add_action( 'wppus_find_package_no_cache', array( $this, 'wppus_find_package_no_cache' ), 10, 3 );
				add_action( 'wppus_update_server_action_download', array( $this, 'wppus_update_server_action_download' ), 10, 1 );
				add_action( 'wppus_template_remote_source_manager_option_after_recurring_check', array( $this, 'wppus_template_remote_source_manager_option_after_recurring_check' ), 10, 0 );

				// do_action( 'wppus_before_packages_download', $archive_name, $archive_path, $package_slugs );
				// do_action( 'wppus_before_packages_download_repack', $archive_name, $archive_path, $package_slugs );
				//	do_action(
				// 	'wppus_after_packages_download',
				// 	$archive_name,
				// 	$archive_path,
				// 	! empty( $archive_path ) && ! empty( $archive_name )
				// );
				// do_action( 'wppus_deleted_package', $result, $type, $slug );

				add_filter( 'wppus_save_remote_to_local', array( $this, 'wppus_save_remote_to_local' ), 10, 4 );
				add_filter( 'wppus_check_remote_package_update_local_meta', array( $this, 'wppus_check_remote_package_update_local_meta' ), 10, 3 );
				add_filter( 'wpup_zip_metadata_parser_extended_cache_key', array( $this, 'wpup_zip_metadata_parser_extended_cache_key' ), 10, 3 );
				add_filter( 'wppus_update_manager_batch_package_info', array( $this, 'wppus_update_manager_batch_package_info' ), 10, 2 );
				add_filter( 'wppus_package_info', array( $this, 'wppus_package_info' ), 10, 2 );
				add_filter( 'wppus_update_server_action_download_handled', array( $this, 'wppus_update_server_action_download_handled' ), 10, 2 );
				add_filter( 'wppus_submitted_remote_sources_config', array( $this, 'wppus_submitted_remote_sources_config' ), 10, 1 );
				add_filter( 'wppus_remote_source_option_update', array( $this, 'wppus_remote_source_option_update' ), 10, 4 );
			}
		}
	}

	public static function get_config() {

		if ( ! self::$config ) {
			$config = array(
				'use_cloud_storage' => get_option( 'wppus_use_cloud_storage' ),
				'access_key'        => get_option( 'wppus_cloud_storage_access_key' ),
				'secret_key'        => get_option( 'wppus_cloud_storage_secret_key' ),
				'endpoint'          => get_option( 'wppus_cloud_storage_endpoint' ),
				'storage_unit'      => get_option( 'wppus_cloud_storage_unit' ),
				'region'            => get_option( 'wppus_cloud_storage_region' ),
			);

			self::$config = $config;
		}

		return apply_filters( 'wppus_license_api_config', self::$config );
	}

	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function admin_enqueue_scripts( $hook ) {
		$debug = (bool) ( constant( 'WP_DEBUG' ) );

		if ( false !== strpos( $hook, 'page_wppus' ) ) {
			$js_ext = ( $debug ) ? '.js' : '.min.js';
			$ver_js = filemtime( WPPUS_PLUGIN_PATH . 'js/admin/cloud-storage' . $js_ext );

			wp_enqueue_script(
				'wppus-cloud-storage-script',
				WPPUS_PLUGIN_URL . 'js/admin/cloud-storage' . $js_ext,
				array( 'jquery' ),
				$ver_js,
				true
			);
		}
	}

	public function wppus_submitted_remote_sources_config( $config ) {
		$config = array_merge(
			$config,
			array(
				'wppus_use_cloud_storage'                 => array(
					'value'        => filter_input( INPUT_POST, 'wppus_use_cloud_storage', FILTER_VALIDATE_BOOLEAN ),
					'display_name' => __( 'Use Cloud Storage', 'wppus' ),
					'condition'    => 'boolean',
				),
				'wppus_cloud_storage_access_key'          => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_cloud_storage_access_key', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Cloud Storage Access Key', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
					'condition'               => 'use-cloud-storage',
				),
				'wppus_cloud_storage_secret_key'          => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_cloud_storage_secret_key', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Cloud Storage Secret Key', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
					'condition'               => 'use-cloud-storage',
				),
				'wppus_cloud_storage_endpoint'            => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_cloud_storage_endpoint', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Cloud Storage Endpoint', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
					'condition'               => 'use-cloud-storage',
				),
				'wppus_cloud_storage_unit'                => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_cloud_storage_unit', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Cloud Storage Unit', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
					'condition'               => 'use-cloud-storage',
				),
				'wppus_cloud_storage_region'              => array(
					'value'                   => filter_input( INPUT_POST, 'wppus_cloud_storage_region', FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
					'display_name'            => __( 'Cloud Storage Region', 'wppus' ),
					'failure_display_message' => __( 'Not a valid string', 'wppus' ),
					'condition'               => 'use-cloud-storage',
				),
			)
		);

		return $config;
	}

	public function wppus_remote_source_option_update( $condition, $option_name, $option_info, $options ) {

		if ( 'use-cloud-storage' === $option_info['condition'] ) {

			if ( $options['wppus_use_cloud_storage']['value'] ) {
				$condition = ! empty( $option_info['value'] );
			} else {

				if ( 'wppus_cloud_storage_endpoint' === $option_name ) {
					$condition = filter_var( 'http://' . $option_info['value'], FILTER_SANITIZE_URL );
				} else {
					$condition = true;
				}

				$option_info['value'] = '';
			}

			if ( ! $condition ) {
				update_option( 'wppus_use_cloud_storage', false );
			}
		}

		return $condition;
	}

	public function wppus_template_remote_source_manager_option_after_recurring_check() {
		wppus_get_admin_template(
			'cloud-storage-options.php',
			array(
				'use_cloud_storage' => get_option( 'wppus_use_cloud_storage' ),
			)
		);
	}

	public function cloud_storage_test() {
		$result = array();

		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'wppus_plugin_options' ) ) {
			$data = filter_input( INPUT_POST, 'data', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY );

			if ( $data ) {
				$access_key   = $data['wppus_cloud_storage_access_key'];
				$secret_key   = $data['wppus_cloud_storage_secret_key'];
				$endpoint     = $data['wppus_cloud_storage_endpoint'];
				$storage_unit = $data['wppus_cloud_storage_unit'];
				$region       = $data['wppus_cloud_storage_region'];

				self::$cloud_storage->setAuth( $access_key, $secret_key );
				self::$cloud_storage->setEndpoint( $endpoint );
				self::$cloud_storage->setRegion( $region );

				try {
					$storage_units = self::$cloud_storage->listBuckets();

					if ( ! in_array( $storage_unit, $storage_units, true ) ) {
						$result = new WP_Error(
							__METHOD__,
							__( 'Error - Storage Unit not found', 'wppus' )
						);
					} else {
						$result[] = __( 'Cloud Storage service was reached sucessfully.', 'wppus' );

						if ( ! $this->virtual_folder_exists( 'wppus-packages' ) ) {
							$created  = $this->create_virtual_folder( 'wppus-packages' );
							$result[] = $created ?
								sprintf(
									// translators: %s is the virtual folder
									esc_html__( 'Virtual folder "%s" was created successfully.', 'wppus' ),
									'wppus-packages',
								) :
								sprintf(
									// translators: %s is the virtual folder
									esc_html__( 'WARNING: Unable to create Virtual folder "%s". The Cloud Storage feature may not work as expected. Try to create it manually and test again.', 'wppus' ),
									'wppus-packages',
								);
						} else {
							$result[] = sprintf(
								// translators: %s is the virtual folder
								esc_html__( 'Virtual folder "%s" found.', 'wppus' ),
								'wppus-packages',
							);
						}
					}
				} catch ( PhpS3Exception $e ) {
					$result = new WP_Error(
						__METHOD__ . ' => PhpS3Exception',
						$e->getMessage()
					);

					$result->add( __METHOD__ . ' => LF', '' );
					$result->add( __METHOD__, __( 'An error occured when attempting to communicate with the Cloud Storage service provider. Please check all the settings and try again.', 'wppus' ) );
				}
			} else {
				$result = new WP_Error(
					__METHOD__,
					__( 'Error - Received invalid data ; please reload the page and try again.', 'wppus' )
				);
			}
		}

		if ( ! is_wp_error( $result ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	public function wppus_remote_sources_options_updated() {
		$config = self::get_config();

		if ( ! $config['use_cloud_storage'] ) {

			return;
		}

		try {

			if ( ! $this->virtual_folder_exists( 'wppus-packages' ) ) {

				if ( ! $this->create_virtual_folder( 'wppus-packages' ) ) {
					php_log(
						sprintf(
							// translators: %s is the virtual folder
							esc_html__( 'WARNING: Unable to create Virtual folder "%s". The Cloud Storage feature may not work as expected. Try to create it manually and test again.', 'wppus' ),
							'wppus-packages',
						)
					);
				}
			}
		} catch ( PhpS3Exception $e ) {
			php_log( $e );
		}
	}

	public function wppus_check_remote_package_update_local_meta( $local_meta, $local_package, $slug ) {
		// If we do not have local_meta, we want to download the package, get the local_meta, then delete it in any case
		WP_Filesystem();

		global $wp_filesystem;

		if ( ! $local_meta ) {
			$config = self::get_config();

			try {
				$filename = $local_package->getFileName();
				$result   = self::$cloud_storage->getObject(
					$config['storage_unit'],
					'wppus-packages/' . $slug . '.zip',
					$local_package->getFileName()
				);

				if (
					$result &&
					$wp_filesystem->is_file( $filename ) &&
					$wp_filesystem->is_readable( $filename )
				) {
					$local_meta = WshWordPressPackageParser::parsePackage( $filename, true );
				}
			} catch ( PhpS3Exception $e ) {
				php_log( $e );
			}
		}

		if ( $wp_filesystem->is_file( $local_package->getFileName() ) ) {
			wp_delete_file( $local_package->getFileName() );
		}

		return $local_meta;
	}

	public function wppus_saved_remote_package_to_local( $local_ready, $type, $slug ) {
		// We want to upload the package, and delete it
		WP_Filesystem();

		global $wp_filesystem;

		$config            = self::get_config();
		$package_directory = WPPUS_Data_Manager::get_data_dir( 'packages' );
		$filename          = trailingslashit( $package_directory ) . $slug . '.zip';

		try {

			if ( $local_ready ) {
				self::$cloud_storage->putObjectFile(
					$filename,
					$config['storage_unit'],
					'wppus-packages/' . $slug . '.zip'
				);
			}
		} catch ( PhpS3Exception $e ) {
			php_log( $e );
		}

		if ( $wp_filesystem->is_file( $filename ) ) {
			wp_delete_file( $filename );
		}
	}

	public function wppus_save_remote_to_local( $save, $slug, $filename, $check_remote ) {
		// We want to set save to true if the package is not found in cloud storage
		$config = self::get_config();

		try {

			if ( $check_remote ) {
				$info = wp_cache_get( $slug . '-getObjectInfo', 'wppus' );

				if ( false === $info ) {
					$info = self::$cloud_storage->getObjectInfo(
						$config['storage_unit'],
						'wppus-packages/' . $slug . '.zip',
					);

					wp_cache_set( $slug . '-getObjectInfo', $info, 'wppus' );
				}

				$save = false === $info;
			}
		} catch ( PhpS3Exception $e ) {
			php_log( $e );
		}

		return $save;
	}

	public function wppus_find_package_no_cache( $slug, $filename, $cache ) {
		// We want to create a local cache, using the file in cloud storage, so we need to download it
		WP_Filesystem();

		global $wp_filesystem;

		$config = self::get_config();

		try {
			$info = wp_cache_get( $slug . '-getObjectInfo', 'wppus' );

			if ( false === $info ) {
				$info = self::$cloud_storage->getObjectInfo(
					$config['storage_unit'],
					'wppus-packages/' . $slug . '.zip',
				);

				wp_cache_set( $slug . '-getObjectInfo', $info, 'wppus' );
			}

			if ( $info ) {
				$cache_key = 'metadata-b64-' . $slug . '-'
						. md5( $filename . '|' . $info['size'] . '|' . $info['time'] );

				if ( ! $cache->get( $cache_key ) ) {
					$result = self::$cloud_storage->getObject(
						$config['storage_unit'],
						'wppus-packages/' . $slug . '.zip',
						$filename
					);

					if ( $result ) {
						$package     = Wpup_Package_Extended::fromArchive( $filename, $slug, $cache );
						$cache_value = $package->getMetadata();

						$cache->set( $cache_key, $cache_value, Wpup_ZipMetadataParser::$cacheTime ); // @codingStandardsIgnoreLine
					}
				}
			}
		} catch ( PhpS3Exception $e ) {
			php_log( $e );
		}

		if ( $wp_filesystem->is_file( $filename ) ) {
			wp_delete_file( $filename );
		}
	}

	public function wpup_zip_metadata_parser_extended_cache_key( $cache_key, $slug, $filename ) {
		$config = self::get_config();
		$info   = wp_cache_get( $slug . '-getObjectInfo', 'wppus' );

		if ( false === $info ) {
			$info = self::$cloud_storage->getObjectInfo(
				$config['storage_unit'],
				'wppus-packages/' . $slug . '.zip',
			);

			wp_cache_set( $slug . '-getObjectInfo', $info, 'wppus' );
		}

		if ( $info ) {
			$cache_key = 'metadata-b64-' . $slug . '-'
						. md5( $filename . '|' . $info['size'] . '|' . $info['time'] );
		}

		return $cache_key;
	}

	public function wppus_package_info( $package_info, $slug ) {

		if ( ! $package_info ) {
			WP_Filesystem();

			global $wp_filesystem;

			$cache             = new Wpup_FileCache( WPPUS_Data_Manager::get_data_dir( 'cache' ) );
			$config            = self::get_config();
			$package_directory = WPPUS_Data_Manager::get_data_dir( 'packages' );
			$filename          = $package_directory . $slug . '.zip';

			try {
				$info = wp_cache_get( $slug . '-getObjectInfo', 'wppus' );

				if ( false === $info ) {
					$info = self::$cloud_storage->getObjectInfo(
						$config['storage_unit'],
						'wppus-packages/' . $slug . '.zip',
					);

					wp_cache_set( $slug . '-getObjectInfo', $info, 'wppus' );
				}

				if ( $info ) {
					$cache_key = 'metadata-b64-' . $slug . '-'
							. md5( $filename . '|' . $info['size'] . '|' . $info['time'] );

					if ( ! $cache->get( $cache_key ) ) {
						$result = self::$cloud_storage->getObject(
							$config['storage_unit'],
							'wppus-packages/' . $slug . '.zip',
							$filename
						);

						if ( $result ) {
							$package     = Wpup_Package_Extended::fromArchive( $filename, $slug, $cache );
							$cache_value = $package->getMetadata();

							$cache->set( $cache_key, $cache_value, Wpup_ZipMetadataParser::$cacheTime ); // @codingStandardsIgnoreLine

							if ( $package ) {
								$package_info = $cache_value;
							}
						}
					} else {
						$package_info = $cache->get( $cache_key );
					}

					if ( $package_info ) {
						$package_info['type']               = isset( $package_info['details_url'] ) ? 'theme' : 'plugin';
						$package_info['file_name']          = $package_info['slug'] . '.zip';
						$package_info['file_path']          = $package_directory . $slug . '.zip';
						$package_info['file_size']          = $info['size'];
						$package_info['file_last_modified'] = $info['time'];
					}
				}
			} catch ( PhpS3Exception $e ) {

				if ( $e instanceof PhpS3Exception ) {
					php_log( $e );
				} else {
					php_log( 'Corrupt archive ' . $filename . ' ; will not be displayed or delivered' );

					$log  = 'Exception caught: ' . $e->getMessage() . "\n";
					$log .= 'File: ' . $e->getFile() . "\n";
					$log .= 'Line: ' . $e->getLine() . "\n";

					php_log( $log );
				}
			}

			if ( $wp_filesystem->is_file( $filename ) ) {
				wp_delete_file( $filename );
			}
		}

		return $package_info;
	}

	public function wppus_update_manager_batch_package_info( $packages, $search ) {
		$config = self::get_config();

		try {
			$contents = self::$cloud_storage->getBucket( $config['storage_unit'], 'wppus-packages/' );

			unset( $contents['wppus-packages/'] );

			if ( ! empty( $contents ) ) {
				$update_manager = WPPUS_Update_Manager::get_instance();

				foreach ( $contents as $item ) {
					$slug = str_replace( array( 'wppus-packages/', '.zip' ), array( '', '' ), $item['name'] );
					$info = $update_manager->get_package_info( $slug );

					if ( $info ) {
						$include = true;

						if ( $search ) {

							if (
								false === strpos( strtolower( $info['name'] ), strtolower( $search ) ) ||
								false === strpos( strtolower( $info['slug'] ) . '.zip', strtolower( $search ) )
							) {
								$include = false;
							}
						}

						$include = apply_filters( 'wppus_batch_package_info_include', $include, $info, $search );

						if ( $include ) {
							$packages[ $info['slug'] ] = $info;
						}
					}
				}
			}
		} catch ( PhpS3Exception $e ) {
			php_log( $e );
		}

		return $packages;
	}

	public function wppus_update_server_action_download( $request ) {
		$config = self::get_config();
		$url    = self::$cloud_storage->getAuthenticatedURL(
			$config['storage_unit'],
			'wppus-packages/' . $request->slug . '.zip',
			self::DOWNLOAD_URL_LIFETIME,
		);

		$this->doing_redirect = wp_redirect( $url );
	}

	public function wppus_update_server_action_download_handled( $handled, $request ) {
		return $this->doing_redirect;
	}

	protected function virtual_folder_exists( $name ) {
		$config = self::get_config();

		return self::$cloud_storage->getObjectInfo(
			$config['storage_unit'],
			trailingslashit( $name )
		);
	}

	protected function create_virtual_folder( $name ) {
		$config = self::get_config();

		return self::$cloud_storage->putObject(
			trailingslashit( $name ),
			$config['storage_unit'],
			trailingslashit( $name )
		);
	}
}
