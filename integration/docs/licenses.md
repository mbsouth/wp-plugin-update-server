# WP Packages Update Server - Licenses - Developer documentation
(Looking for the main documentation page instead? [See here](https://github.com/froger-me/wp-packages-update-server/blob/main/README.md))

WP Packages Update Server provides an API and offers a series of functions, actions and filters for developers to use in their own plugins and themes to modify the behavior of the plugin when managing licenses.

* [WP Packages Update Server - Licenses - Developer documentation](#wp-packages-update-server---licenses---developer-documentation)
	* [The License Query](#the-license-query)
	* [API](#api)
		* [Public API](#public-api)
			* [check](#check)
			* [activate](#activate)
			* [deactivate](#deactivate)
		* [Private API](#private-api)
			* [browse](#browse)
			* [read](#read)
			* [edit](#edit)
			* [add](#add)
			* [delete](#delete)
	* [Functions](#functions)
		* [wppus\_is\_doing\_license\_api\_request](#wppus_is_doing_license_api_request)
		* [wppus\_browse\_licenses](#wppus_browse_licenses)
		* [wppus\_read\_license](#wppus_read_license)
		* [wppus\_edit\_license](#wppus_edit_license)
		* [wppus\_add\_license](#wppus_add_license)
		* [wppus\_delete\_license](#wppus_delete_license)
		* [wppus\_check\_license](#wppus_check_license)
		* [wppus\_activate\_license](#wppus_activate_license)
		* [wppus\_deactivate\_license](#wppus_deactivate_license)
	* [Actions](#actions)
		* [wppus\_added\_license\_check](#wppus_added_license_check)
		* [wppus\_removed\_license\_check](#wppus_removed_license_check)
		* [wppus\_registered\_license\_schedule](#wppus_registered_license_schedule)
		* [wppus\_cleared\_license\_schedule](#wppus_cleared_license_schedule)
		* [wppus\_scheduled\_license\_event](#wppus_scheduled_license_event)
		* [wppus\_browse\_licenses](#wppus_browse_licenses-1)
		* [wppus\_did\_browse\_licenses](#wppus_did_browse_licenses)
		* [wppus\_did\_read\_license](#wppus_did_read_license)
		* [wppus\_did\_edit\_license](#wppus_did_edit_license)
		* [wppus\_did\_add\_license](#wppus_did_add_license)
		* [wppus\_did\_delete\_license](#wppus_did_delete_license)
		* [wppus\_did\_check\_license](#wppus_did_check_license)
		* [wppus\_pre\_activate\_license](#wppus_pre_activate_license)
		* [wppus\_did\_activate\_license](#wppus_did_activate_license)
		* [wppus\_pre\_deactivate\_license](#wppus_pre_deactivate_license)
		* [wppus\_did\_deactivate\_license](#wppus_did_deactivate_license)
		* [wppus\_license\_api\_request](#wppus_license_api_request)
	* [Filters](#filters)
		* [wppus\_licensed\_package\_slugs](#wppus_licensed_package_slugs)
		* [wppus\_license\_valid](#wppus_license_valid)
		* [wppus\_license\_server](#wppus_license_server)
		* [wppus\_license\_api\_config](#wppus_license_api_config)
		* [wppus\_submitted\_licenses\_config](#wppus_submitted_licenses_config)
		* [wppus\_check\_license\_result](#wppus_check_license_result)
		* [wppus\_activate\_license\_result](#wppus_activate_license_result)
		* [wppus\_deactivate\_license\_result](#wppus_deactivate_license_result)
		* [wppus\_check\_license\_dirty\_payload](#wppus_check_license_dirty_payload)
		* [wppus\_activate\_license\_dirty\_payload](#wppus_activate_license_dirty_payload)
		* [wppus\_deactivate\_license\_dirty\_payload](#wppus_deactivate_license_dirty_payload)
		* [wppus\_activate\_license\_payload](#wppus_activate_license_payload)
		* [wppus\_deactivate\_license\_payload](#wppus_deactivate_license_payload)
		* [wppus\_browse\_licenses\_payload](#wppus_browse_licenses_payload)
		* [wppus\_read\_license\_payload](#wppus_read_license_payload)
		* [wppus\_edit\_license\_payload](#wppus_edit_license_payload)
		* [wppus\_add\_license\_payload](#wppus_add_license_payload)
		* [wppus\_delete\_license\_payload](#wppus_delete_license_payload)
		* [wppus\_license\_public\_api\_actions](#wppus_license_public_api_actions)
		* [wppus\_license\_api\_request\_authorized](#wppus_license_api_request_authorized)
		* [wppus\_license\_bypass\_signature](#wppus_license_bypass_signature)
		* [wppus\_api\_license\_actions](#wppus_api_license_actions)
		* [wppus\_api\_license\_actions](#wppus_api_license_actions-1)

___
## The License Query

The License Query is an associative array. It is used to browse the license records either with the [Private API action](#browse) `browse` or with the `wppus_browse_licenses` [function](#wppus_browse_licenses).

```php
$license_query = array(
	'relationship' => 'AND',          // Relationship of the criteria when provided - 'AND or 'OR' - default 'AND' (optional)
	'limit'        => '10',           // Limit the number of results ; set to any negative value for no limit - default 999 (optional)
	'offset'       => '0',            // Results offset - default 0 (optional)
	'order_by'     => 'date_created', // Order of the license records returned - default 'date_created' (optional)
	'criteria'     => array(          // Criteria to filter the license records - accepts multiple values - if omitted, the result is not filtered (optional)
		array(
			'field'    => 'field',    // Field to filter by - see the list of accepted license fields below (required)
			'value'    => 'value',    // Value of the field to filter by - format depends on the operator (required)
			'operator' => 'operator'  // Comparison operator - see the list of accepted operators below (required)
		),
		array(
			'field'    => 'field',    // Field to filter by - see the list of accepted license fields below (required)
			'value'    => 'value',    // Value of the field to filter by - format depends on the operator (required)
			'operator' => 'operator'  // Comparison operator - see the list of accepted operators below (required)
		),
		...                           // More criteria...
	),
);
```

Accepted operators are: `=`, `>`, `<`, `>=`, `<=`, `BETWEEN`, `NOT BETWEEN`, `IN`, `NOT IN`, `LIKE`, `NOT LIKE`.

Accepted license fields are: `id` (only for `wppus_browse_licenses`, not through the API), `license_key`, `max_allowed_domains`, `allowed_domains`, `status`, `owner_name`, `email`, `company_name`, `txn_id`, `date_created`, `date_renewed`, `date_expiry`, `package_slug`, `package_type`.

NOTE: the field `allowed_domains` is a serialized array, therefore when used in a criteria the only operators likely to return results are `LIKE` and `NOT LIKE`.

___
## API

The License API is accessible via POST and GET requests on the `/wppus-license-api/` endpoint for both the Public and Private API, and via POST only for the Private API. It accepts form-data payloads (arrays, basically). This documentation page uses `wp_remote_post`, but `wp_remote_get` would work as well for the Public API.

In case the API is accessed with an invalid `action` parameter, the following response is returned (message's language depending on available translations), with HTTP response code set to `400`:

Response `$data` - malformed request:
```json
{
	"message": "License API action not found"
}
```

The description of the API further below is using the following code as reference, where `$params` are the parameters passed to the API (other parameters can be adjusted, they are just WordPress' default) and `$data` is the JSON response:

```php
$url      = 'https://domain.tld/wppus-license-api/'; // Replace domain.tld with the domain where WP Packages Update Server is installed.
$response = wp_remote_post(
	$url,
	array(
		'method'      => 'POST',
		'timeout'     => 45,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking'    => true,
		'headers'     => array(),
		'body'        => $params,
		'cookies'     => array(),
	);
);

if ( is_wp_error( $response ) ) {
	printf( esc_html__( 'Something went wrong: %s', 'text-domain' ), esc_html( $response->get_error_message() ) );
} else {
	$data         = wp_remote_retrieve_body( $response );
	$decoded_data = json_decode( $data );

	if ( '200' === $response['response']['code'] ) {
		// Handle success with $decoded_data
	} else {
		// Handle failure with $decoded_data
	}
}
```

___
### Public API

The public API does not require an authentication key, because for each provided operation, the License Key **IS** the authentication key for the API: by default, keys are randomly generated using `bin2hex( openssl_random_pseudo_bytes( 16 ) )` which gives back an unguessable, random string of 32 characters. Even if the keys **CAN** be edited via the admin interface, it is highly recommended to use an unguessable, random string regardless.

It provides 3 simple operations: `check`, `activate` and `deactivate`.

___
#### check

```php
$params = array(
	'action'      => 'check',        // Action to perform when calling the License API (required)
	'license_key' => 'test-license', // The key of the license to check (required)
);
```

Response `$data` - **success**:
```json
{
	"id": "99",
	"license_key": "test-license",
	"max_allowed_domains": "2",
	"allowed_domains": [
		"domain1.example.com",
	],
	"status": "activated",
	"txn_id": "#111111111",
	"date_created": "2099-12-01",
	"date_renewed": "2099-12-15",
	"date_expiry": "2099-12-31",
	"package_slug": "test-package",
	"package_type": "plugin"
}
```

Response `$data` - **failure** (in case of invalid `license_key`):
```json
{
	"license_key": "test-license"
}
```

___
#### activate

```php
$params = array(
	'action'          => 'activate',             // Action to perform when calling the License API (required)
	'license_key'     => 'test-license',         // The key of the license to activate for the provided domain (required)
	'allowed_domains' => array( 'example.com' ), // Domain name for which the license needs to be activated - can be a string (required)
);
```

Response `$data` - **success**:
```json
{
	"id": "99",
	"license_key": "test-license",
	"max_allowed_domains": "2",
	"allowed_domains": [
		"domain1.example.com",
		"example.com"
	],
	"status": "activated",
	"txn_id": "#111111111",
	"date_created": "2099-12-01",
	"date_renewed": "2099-12-15",
	"date_expiry": "2099-12-31",
	"package_slug": "test-package",
	"package_type": "plugin",
	"license_signature": "some_complex_encrypted_string-some_complex_hmac"
}
```

Response `$data` - **failure** (in case of invalid `license_key`):
```json
{
	"license_key": "test-license"
}
```

Response `$data` - **failure** (in case of illegal status - illegal statuses for activation/deactivation are `"on-hold"`, `"expired"` and `"blocked"`):
```json
{
	"status": "expired"
}
```

Response `$data` - **failure** (in case of already activated for `allowed_domains`):
```json
{
	"allowed_domains": [
		"example.com"
	]
}
```

Response `$data` - **failure** (in case of no more domains allowed for activation):
```json
{
	"max_allowed_domains": "2"
}
```

___
#### deactivate

```php
$params = array(
	'action'          => 'deactivate',           // Action to perform when calling the License API (required)
	'license_key'     => 'test-license',         // The key of the license to activate for the provided domain (required)
	'allowed_domains' => array( 'example.com' ), // Domain name for which the license needs to be deactivated - can be a string (required)
);
```

Response `$data` - **success** (in case some domains are still activated):
```json
{
	"id": "99",
	"license_key": "test-license",
	"max_allowed_domains": "2",
	"allowed_domains": [
		"domain1.example.com",
	],
	"status": "activated",
	"txn_id": "#111111111",
	"date_created": "2099-12-01",
	"date_renewed": "2099-12-15",
	"date_expiry": "2099-12-31",
	"package_slug": "test-package",
	"package_type": "plugin"
}
```

Response `$data` - **failure** (in case of invalid `license_key`):
```json
{
	"license_key": "test-license"
}
```

Response `$data` - **failure** (in case of illegal status - illegal statuses for activation/deactivation are `"on-hold"`, `"expired"` and `"blocked"`):
```json
{
	"status": "expired"
}
```

Response `$data` - **success** (in case all domains have been deactivated):
```json
{
	"id": "99",
	"license_key": "test-license",
	"max_allowed_domains": "2",
	"allowed_domains": [],
	"status": "deactivated",
	"txn_id": "#111111111",
	"date_created": "2099-12-01",
	"date_renewed": "2099-12-15",
	"date_expiry": "2099-12-31",
	"package_slug": "test-package",
	"package_type": "plugin"
}
```

Response `$data` - **failure** (in case of already deactivated for `allowed_domains`):
```json
{
	"allowed_domains": [
		"example.com"
	]
}
```

___
### Private API

The Private API, only accessible via the POST method, requires extra authentication for all its actions - `browse`, `edit`, `add`, `delete`.  
The first action, `browse`, is particular in the sense that, unlike the other actions and aside from the authentication token, it accepts a JSON License Query instead of the classic form-data payload.  
With the Private API, depending on granted privileges, developers can theoretically perform any operation on the license records - **be careful to keep the Private API Authentication Key an absolute secret!**

To access the Private API, an authentication token must first be obtained with the [Nonce API](https://github.com/froger-me/wp-packages-update-server/blob/master/misc.md#nonce-api) ; for example:

```php
// We assume the API Key is stored in environment variables
$api_key  = getenv( 'WPPUS_LICENSE_API_KEY' );
$url      = 'https://domain.tld/wppus-token/'; // Replace domain.tld with the domain where WP Packages Update Server is installed.
$response = wp_remote_post(
	$url,
	array(
		'headers'     => array(
			'X-WPPUS-Private-License-API-Key' => $api_key,
		),
		'body'        => array(
			'api_auth_key'  => 'secret',  // Only used if X-WPPUS-Private-License-API-Key is not set
			'api'           => 'license', // Only used if X-WPPUS-Private-License-API-Key is not set
		),
	);
);

if ( is_wp_error( $response ) ) {
	printf( esc_html__( 'Something went wrong: %s', 'text-domain' ), esc_html( $response->get_error_message() ) );
} else {
	$data = wp_remote_retrieve_body( $response );

	if ( '200' === $response['response']['code'] ) {
		error_log( $data );
	} else {
		// Handle failure with $data
	}
}
```

In the above example, the `$data` variable looks like:

```json
{
    "nonce": "e7466375e8c851564653c6f7de81cd8f", // the authentication token
    "true_nonce": false,                         // whether the token can be only used once before it expires
    "expiry": 9999999999,                        // when the token expires - default is +30 minutes
    "data": {                                    // the data stored with the token
        "license_api": {                         // the license API data corresponding to the API key, generated by WPPUS
            "id": "api_key_id",                  // the ID of the API key
            "access": [                          // the list of authorized access privileges - `all` means access to everything related to licenses on WPPUS
                ...
            ]
        }
    }
}
```
Once an authentication token has been obtained, it needs to be provided to API actions, either via the `api_token` parameter, or by passing a `X-WPPUS-Token` header (recommended - it is then found in `$_SERVER['HTTP_X_WPPUS_TOKEN']` in PHP).  
In case the token is invalid, all the actions of the Private API return the same response (message's language depending on available translations), with HTTP response code set to `403`:

Response `$data` - forbidden access:
```json
{
	"message": "Unauthorized access"
}
```

In case the Private API is accessed via the `GET` method, all the actions return the same response (message's language depending on available translations), with HTTP response code set to `405`:

Response `$data` - unauthorized method:
```json
{
	"message": "Unauthorized GET method"
}
```
___
#### browse

See [The License Query](#the-license-query) for more information on the `$license_query` parameter

```php
$params = array(
	'action'       => 'browse',                         // Action to perform when calling the License API (required)
	'browse_query' => wp_json_encode( $license_query ), // A JSON representation of a License Query (required)
	'api_token'    => 'token',                          // The authentication token (optional - must provided via X-WPPUS-Token header if absent)
);
```

Response `$data` - **success**:
```json
[
	{
		"id": "99",
		"license_key": "test-license",
		"max_allowed_domains": "2",
		"allowed_domains": [
			"domain1.example.com",
		],
		"status": "deactivated",
		"owner_name": "Test Owner",
		"email": "test@test.com",
		"company_name": "Test Company",
		"txn_id": "#111111111",
		"date_created": "2099-12-01",
		"date_renewed": "2099-12-15",
		"date_expiry": "2099-12-31",
		"package_slug": "test-package",
		"package_type": "plugin",
		"data": {
			"api_owner": "private_key_id"
		},
		"hmac_key":"aaaaaaaaaaaaccbb9999999999999999",
		"crypto_key":"9999999999999999ccbbaaaaaaaaaaaa"
		},
	...
]
```

Response `$data` - **failure** (in case of malformed License Query - same as no result):
```php
[]
```

___
#### read

```php
$params = array(
	'action'       => 'read',         // Action to perform when calling the License API (required)
	'license_key'  => 'test-license', // The key of the license to read
	'api_token'    => 'token',        // The authentication token (optional - must provided via X-WPPUS-Token header if absent)
);
```

Response `$data` - **success**:
```json
{
	"id": "99",
	"license_key": "test-license",
	"max_allowed_domains": "2",
	"allowed_domains": [
		"domain1.example.com",
	],
	"status": "deactivated",
	"owner_name": "Test Owner",
	"email": "test@test.com",
	"company_name": "Test Company",
	"txn_id": "#111111111",
	"date_created": "2099-12-01",
	"date_renewed": "2099-12-15",
	"date_expiry": "2099-12-31",
	"package_slug": "test-package",
	"package_type": "plugin",
	"data": {
		"api_owner": "private_key_id"
	},
	"hmac_key":"aaaaaaaaaaaaccbb9999999999999999",
	"crypto_key":"9999999999999999ccbbaaaaaaaaaaaa"
}
```

Response `$data` - **failure**:
```json
[]
```

___
#### edit

```php
$params = array(
	'action'              => 'edit',              // Action to perform when calling the License API (required)
	'license_key'         => 'test-license-new',  // The key of the license to edit (required - used to identify the record to edit)
	'max_allowed_domains' => '99',                // The maximum number of domains allowed to use the license - minimum 1 (required)
	'allowed_domains'     => array(               // Domains currently allowed to use the license (optional)
		'different1.example.com',
		'different2.example.com',
	),
	'status'              => 'blocked',           // The status of the license - one of pending, activated, deactivated, on-hold, blocked, expired (required)
	'owner_name'          => 'Another Owner',     // The full name of the owner of the license (optional)
	'email'               => 'test-new@test.com', // The email registered with the license (required)
	'company_name'        => 'New Company',       // The company of the owner of the license (optional)
	'txn_id'              => '#999999999',        // If applicable, the transaction identifier associated to the purchase of the license (optional)
	'date_created'        => '3099-12-01',        // Creation date of the license - YYYY-MM-DD  (required)
	'date_renewed'        => '3099-12-015',       // Date of the last time the license was renewed -\n YYYY-MM-DD (optional)
	'date_expiry'         => '3099-12-31',        // Expiry date of the license - YYY-MM-DD - if omitted, no expiry (optional)
	'package_slug'        => 'new-package',       // The package slug - only alphanumeric characters and dashes are allowed (required)
	'package_type'        => 'theme',             // Type of package the license is for - one of plugin, theme (required)
	'api_token'           => 'token',             // The authentication token (optional - must provided via X-WPPUS-Token header if absent)
);
```

Response `$data` - **success**:
```json
{
	"id": "99",
	"license_key": "test-license-new",
	"max_allowed_domains": "99",
	"allowed_domains": [
		"different1.example.com",
		"different2.example.com"
	],
	"status": "blocked",
	"owner_name": "Another Owner",
	"email": "test-new@test.com",
	"company_name": "New Company",
	"txn_id": "#999999999",
	"date_created": "3099-12-01",
	"date_renewed": "3099-12-15",
	"date_expiry": "3099-12-31",
	"package_slug": "new-package",
	"package_type": "theme",
	"data": {
		"api_owner": "private_key_id"
	},
	"hmac_key":"aaaaaaaaaaaaccbb9999999999999999",
	"crypto_key":"9999999999999999ccbbaaaaaaaaaaaa"
}
```

Response `$data` - **failure** (in case the fields do not match the requirements):
```json
{
	"errors": [
		"error message 1",
		"error message 2",
		...
	]
}
```

___
#### add

```php
$params = array(
	'action'              => 'add',           // Action to perform when calling the License API (required)
	'license_key'         => 'test-license',  // The key of the license to add (required)
	'max_allowed_domains' => '2',             // The maximum number of domains allowed to use the license - minimum 1 (required)
	'allowed_domains'     => array(           // Domains currently allowed to use the license (optional)
		'domain1.example.com',
		'domain2.example.com',
	),
	'status'              => 'pending',       // The status of the license - one of pending, activated, deactivated, on-hold, blocked, expired (required)
	'owner_name'          => 'Test Owner',    // The full name of the owner of the license (optional)
	'email'               => 'test@test.com', // The email registered with the license (required)
	'company_name'        => 'Test Company',  // The company of the owner of the license (optional)
	'txn_id'              => '#111111111',    // If applicable, the transaction identifier associated to the purchase of the license (optional)
	'date_created'        => '2099-12-01',    // Creation date of the license - YYYY-MM-DD  (required)
	'date_renewed'        => '2099-12-015',   // Date of the last time the license was renewed -\n YYYY-MM-DD (optional)
	'date_expiry'         => '2099-12-31',    // Expiry date of the license - YYY-MM-DD - if omitted, no expiry (optional)
	'package_slug'        => 'test-package',  // The package slug - only alphanumeric characters and dashes are allowed (required)
	'package_type'        => 'plugin',        // Type of package the license is for - one of plugin, theme (required)
	'api_token'           => 'token',         // The authentication token (optional - must provided via X-WPPUS-Token header if absent)
);
```

Response `$data` - **success**:
```json
{
	"id": "99",
	"license_key": "test-license",
	"max_allowed_domains": "2",
	"allowed_domains": [
		"domain1.example.com",
	],
	"status": "deactivated",
	"owner_name": "Test Owner",
	"email": "test@test.com",
	"company_name": "Test Company",
	"txn_id": "#111111111",
	"date_created": "2099-12-01",
	"date_renewed": "2099-12-15",
	"date_expiry": "2099-12-31",
	"package_slug": "test-package",
	"package_type": "plugin",
	"data": {
		"api_owner": "private_key_id"
	},
	"hmac_key":"aaaaaaaaaaaaccbb9999999999999999",
	"crypto_key":"9999999999999999ccbbaaaaaaaaaaaa"
}
```

Response `$data` - **failure** (in case the fields do not match the requirements):
```json
{
	"errors": [
		"error message 1",
		"error message 2",
		...
	]
}
```

___
#### delete

```php
$params = array(
	'action'       => 'delete',       // Action to perform when calling the License API (required)
	'license_key'  => 'test-license', // The key of the license to delete
	'api_token'    => 'token',        // The authentication token (optional - must provided via X-WPPUS-Token header if absent)
);
```

Response `$data` - **success**:
```json
{
	"id": "99",
	"license_key": "test-license",
	"max_allowed_domains": "2",
	"allowed_domains": [
		"domain1.example.com",
	],
	"status": "deactivated",
	"owner_name": "Test Owner",
	"email": "test@test.com",
	"company_name": "Test Company",
	"txn_id": "#111111111",
	"date_created": "2099-12-01",
	"date_renewed": "2099-12-15",
	"date_expiry": "2099-12-31",
	"package_slug": "test-package",
	"package_type": "plugin",
	"data": {
		"api_owner": "private_key_id"
	},
	"hmac_key":"aaaaaaaaaaaaccbb9999999999999999",
	"crypto_key":"9999999999999999ccbbaaaaaaaaaaaa"
}
```

Response `$data` - **failure**:
```json
[]
```

___
## Functions

The functions listed below are made publicly available by the plugin for theme and plugin developers. They can be used after the action `plugins_loaded` has been fired, or in a `plugins_loaded` action (just make sure the priority is above `-99`).
Although the main classes can theoretically be instantiated without side effect if the `$hook_init` parameter is set to `false`, it is recommended to use only the following functions as there is no guarantee future updates won't introduce changes of behaviors.

___
### wppus_is_doing_license_api_request

```php
wppus_is_doing_license_api_request();
```

**Description**  
Determine whether the current request is made by a client plugin or theme interacting with the plugin's license API.

**Return value**
> (bool) `true`  if the current request is a client plugin or theme interacting with the plugin's API, `false` otherwise

___
### wppus_browse_licenses

```php
wppus_browse_licenses( array $browse_query );
```

**Description**  
Browse the license records filtered using various criteria.

**Parameters**  
`$browse_query`
> (array) See [The License Query](#the-license-query)

**Return value**
> (array) An array of license object matching the License Query

___
### wppus_read_license

```php
wppus_read_license( array $payload );
```

**Description**  
Read a license record.

**Parameters**  
`$payload`
> (array) See the License API action [read](#read)

**Return value**
> (mixed) An object in case of success (see the response of the License API action [read](#read) - the object is the decoded value of the JSON string) or an empty array otherwise

___
### wppus_edit_license

```php
wppus_edit_license( array $payload );
```

**Description**  
Add a license record.

**Parameters**  
`$payload`
> (array) See `$params` for the License API action [add](#add)

**Return value**
> (mixed) An object in case of success (see the response of the License API action [add](#add) - the object is the decoded value of the JSON string) or an array of errors otherwise

___
### wppus_add_license

```php
wppus_add_license( array $payload );
```

**Description**  
Add a license.

**Parameters**  
`$payload`
> (array) See `$params` for the License API action [add](#add)

**Return value**
> (mixed) An object in case of success (see the response of the License API action [add](#add) - the object is the decoded value of the JSON string) or an array of errors otherwise

___
### wppus_delete_license

```php
wppus_delete_license( array $payload );
```

**Description**  
Delete a license record.

**Parameters**  
$payload
> (array) See `$params` for the License API action [delete](#delete)

**Return value**
> (mixed) An object in case of success (see the response of the License API action [delete](#delete) - the object is the decoded value of the JSON string) or an empty array otherwise

___
### wppus_check_license

```php
wppus_check_license( array $payload );
```

**Description**  
Check a License information.

**Parameters**  
`$payload`
> (array) An associative array with a single value - `array( 'license_key' => 'key_of_the_license_to_check' )`.

**Return value**
> (mixed) An object in case of success, and associative array in case of failure (see the response of the License API action [check](#check) - the object is the decoded value of the JSON string)

___
### wppus_activate_license

```php
wppus_activate_license( array $payload );
```

**Description**  
Activate a License.

**Parameters**  
`$payload`
> (array) An associative array with 2 values - `array( 'license_key' => 'key_of_the_license_to_activate', 'allowed_domains' => 'domain_to_activate' )`.

**Return value**
> (mixed) An object in case of success, and associative array in case of failure (see the response of the License API action [activate](#activate) - the object is the decoded value of the JSON string)

___
### wppus_deactivate_license

```php
wppus_deactivate_license( array $payload );
```

**Description**  
Deactivate a License.

**Parameters**  
`$payload`
> (array) An associative array with 2 values - `array( 'license_key' => 'key_of_the_license_to_deactivate', 'allowed_domains' => 'domain_to_deactivate' )`.

**Return value**
> (mixed) An object in case of success, and associative array in case of failure (see the response of the License API action [deactivate](#deactivate) - the object is the decoded value of the JSON string)

___
## Actions

WP Packages Update Server gives developers the possibility to have their plugins react to some events with a series of custom actions.
**Warning**: the actions below with the mention "Fired during client license API request" need to be used with caution. Although they may also be triggered when using the functions above, these actions will be called when client packages request for updates or when License API calls are performed. Registering functions doing heavy computation to these actions when client license API requests are handled can seriously degrade the server's performances.

___
### wppus_added_license_check

```php
do_action( 'wppus_added_license_check', string $package_slug );
```

**Description**  
Fired after a package was marked as "Requires License".

**Parameters**  
> (string) the slug of the package

___
### wppus_removed_license_check

```php
do_action( 'wppus_removed_license_check', string $package_slug );
```

**Description**  
Fired after a package was marked as "Does not Require License".

**Parameters**  
`$package_slug`
> (string) the slug of the package

___
### wppus_registered_license_schedule

```php
do_action( 'wppus_registered_license_schedule', string $scheduled_hook );
```

**Description**  
Fired after the license maintenance action has been registered.

**Parameters**  
`$scheduled_hook`
> (string) the license event hook that has been registered

___
### wppus_cleared_license_schedule

```php
do_action( 'wppus_cleared_license_schedule' );
```

**Description**  
Fired after the license maintenance event has been unscheduled.

___
### wppus_scheduled_license_event

```php
do_action( 'wppus_scheduled_license_event', bool $result, int $timestamp, string $frequency, string $hook );
```

**Description**  
Fired after the license maintenance event has been scheduled.

**Parameters**  
`$result`
> (bool) `true` if the event was scheduled, `false` otherwise

`$timestamp`
> (int) timestamp for when to run the event the first time after it's been scheduled

`$frequency`
> (string) frequency at which the event would be ran

`$hook`
> (string) event hook to fire when the event is ran

`$params`
> (array) parameters passed to the actions registered to $hook when the event is ran

___
### wppus_browse_licenses

```php
do_action( 'wppus_browse_licenses', array $payload );
```

**Description**  
Fired when about to browse license records, before the payload has been cleaned up and the License Query has been validated.
Fired during client license API request.

**Parameters**  
`$payload`
> (array) a dirty payload for a License Query

___
### wppus_did_browse_licenses

```php
do_action( 'wppus_did_browse_licenses', array $licenses, array $payload );
```

**Description**  
Fired after browsing license records.
Fired during client license API request.

**Parameters**  
`$licenses`
> (array) the license records retrieved or an empty array

`$payload`
> (array) the payload of the request  

___
### wppus_did_read_license

```php
do_action( 'wppus_did_read_license', mixed $result, array $payload );
```

**Description**  
Fired after reading a license record.
Fired during client license API request.

**Parameters**  
`$result`
> (mixed) the result of the operation - a license object record or an empty array

`$payload`
> (array) the payload of the request  

___
### wppus_did_edit_license

```php
do_action( 'wppus_did_edit_license', mixed $result, array $payload, mixed $original );
```

**Description**  
Fired after editing a license record.
Fired during client license API request.

**Parameters**  
`$result`
> (mixed) the result of the operation - a license record object or an array of errors

`$payload`
> (array) the payload of the request  

`$result`
> (mixed) the original record to edit - a license record object or an array of errors
___
### wppus_did_add_license

```php
do_action( 'wppus_did_add_license', mixed $result, array $payload );
```

**Description**  
Fired after adding a license record.
Fired during client license API request.

**Parameters**  
`$result`
> (mixed) the result of the operation - a license record object or an array of errors

`$payload`
> (array) the payload of the request  

___
### wppus_did_delete_license

```php
do_action( 'wppus_did_delete_license', mixed $result, array $payload );
```

**Description**  
Fired after deleting a license record.
Fired during client license API request.

**Parameters**  
`$result`
> (mixed) the result of the operation - a license record object or an empty array

`$payload`
> (array) the payload of the request  

___
### wppus_did_check_license

```php
do_action( 'wppus_did_check_license', mixed $result, array $payload );
```

**Description**  
Fired after checking a License.
Fired during client license API request.

**Parameters**  
`$result`
> (mixed) the result of the operation - a license record object or an associative array with non-existant license key

`$payload`
> (array) the payload of the request  

___
### wppus_pre_activate_license

```php
do_action( 'wppus_pre_activate_license', mixed $license );
```

**Description**  
Fired before activating a License.
Fired during client license API request.

**Parameters**  
`$license`
> (mixed) the license to activate as an object in case it exists (see the response of the License API action [read](#read) - the object is the decoded value of the JSON string) or an empty array otherwise

___
### wppus_did_activate_license

```php
do_action( 'wppus_did_activate_license', mixed $result, array $payload );
```

**Description**  
Fired after activating a License.
Fired during client license API request.

**Parameters**  
`$result`
> (mixed) the result of the operation - a license record object, an associative array with the illegal status, an associative array with the domain already active for the license record, or an associative array with the maximum number of domains reached for the license record

`$payload`
> (array) the payload of the request  

___
### wppus_pre_deactivate_license

```php
do_action( 'wppus_pre_deactivate_license', mixed $license );
```

**Description**  
Fired before deactivating a License.
Fired during client license API request.

**Parameters**  
`$license`
> (mixed) the license to deactivate as an object in case it exists (see the response of the License API action [read](#read) - the object is the decoded value of the JSON string) or an empty array otherwise

___
### wppus_did_deactivate_license

```php
do_action( 'wppus_did_deactivate_license', mixed $result, array $payload );
```

**Description**  
Fired after activating a License.
Fired during client license API request.

**Parameters**  
`$result`
> (mixed) the result of the operation - a license record object, an associative array with the illegal status, or an associative array with the domain already inactive for the license record

`$payload`
> (array) the payload of the request  

___
### wppus_license_api_request

```php
do_action( 'wppus_license_api_request', string $action, array $payload );
```

**Description**  
Fired before the License API request is processed ; useful to bypass the execution of currently implemented actions, or implement new actions. 

**Parameters**  
`$action`
> (string) the License API action  

`$payload`
> (array) the payload of the request  

___
## Filters

WP Packages Update Server gives developers the possibility to customize its behavior with a series of custom filters.
**Warning**: the filters below with the mention "Fired during client license API request" need to be used with caution. Although they may be triggered when using the functions above, these filters will be called when client packages request for updates or when License API calls are performed. Registering functions doing heavy computation to these filters when client license API requests are handled can seriously degrade the server's performances.

___
### wppus_licensed_package_slugs

```php
apply_filters( 'wppus_licensed_package_slugs', array $package_slugs );
```

**Description**  
Filter the slugs of packages requiring a license.
Fired during client license API request.

**Parameters**  
`$package_slugs`
> (array) the slugs of packages requiring a license

___
### wppus_license_valid

```php
apply_filters( 'wppus_license_valid', bool $is_valid, mixed $license, string $license_signature );
```

**Description**  
Filter whether a license is valid when requesting for an update.
Fired during client license API request.

**Parameters**  
`$is_valid`
> (bool) wheter the license is valid

**Parameters**  
`$license`
> (mixed) the license to validate

**Parameters**  
`$license_signature`
> (string) the signature of the license

___
### wppus_license_server

```php
apply_filters( 'wppus_license_server', mixed $license_server );
```

**Description**  
Filter the Wppus_License_Server object to use.
Fired during client license API request.

**Parameters**  
`$license_server`
> (mixed) the Wppus_License_Server object

___
### wppus_license_api_config

```php
apply_filters( 'wppus_license_api_config', array $config );
```

**Description**  
Filter the license API configuration values before using them.
Fired during client license API request.

**Parameters**  
`$config`
> (array) the license api configuration values

___
### wppus_submitted_licenses_config

```php
apply_filters( 'wppus_submitted_licenses_config', array $config );
```

**Description**  
Filter the submitted license configuration values before saving them.

**Parameters**  
`$config`
> (array) the submitted license configuration values

___
### wppus_check_license_result

```php
apply_filters( 'wppus_check_license_result', mixed $result, array $payload );
```

**Description**  
Filter the result of license check.
Fired during client license API request.

**Parameters**  
`$result`
> (mixed) the result of the operation - a license record object or an associative array with non-existant license key

**Parameters**  
`$payload`
> (array) the data used to attempt to check the license


___
### wppus_activate_license_result

```php
apply_filters( 'wppus_activate_license_result', mixed $result, array $payload, mixed $maybe_license );
```

**Description**  
Filter the result of license activation.
Fired during client license API request.

**Parameters**  
`$result`
> (mixed) the result of the operation - a license record object, an associative array with the illegal status, an associative array with the domain already active for the license record, or an associative array with the maximum number of domains reached for the license record

**Parameters**  
`$payload`
> (array) the data used to attempt to activate the license

**Parameters**  
`$maybe_license`
> (mixed) a license object record or an empty array

___
### wppus_deactivate_license_result

```php
apply_filters( 'wppus_deactivate_license_result', mixed $result, array $payload, mixed $maybe_license );
```

**Description**  
Filter the result of license deactivation.
Fired during client license API request.

**Parameters**  
`$result`
> (mixed) the result of the operation - a license record object, an associative array with the illegal status, or an associative array with the domain already inactive for the license record

**Parameters**  
`$payload`
> (array) the data used to attempt to deactivate the license

**Parameters**  
`$maybe_license`
> (mixed) a license object record or an empty array

___
### wppus_check_license_dirty_payload

```php
apply_filters( 'wppus_check_license_dirty_payload', array $dirty_payload );
```

**Description**  
Filter the payload used to check a license before it is cleaned up or used in any way.
Fired during client license API request.

**Parameters**  
`$dirty_payload`
> (array) the dirty payload used to attempt to check a license

___
### wppus_activate_license_dirty_payload

```php
apply_filters( 'wppus_activate_license_dirty_payload', array $dirty_payload );
```

**Description**  
Filter the payload used to activate a license before it is cleaned up or used in any way.
Fired during client license API request.

**Parameters**  
`$dirty_payload`
> (array) the dirty payload used to attempt to activate a license

___
### wppus_deactivate_license_dirty_payload

```php
apply_filters( 'wppus_deactivate_license_dirty_payload', array $dirty_payload );
```

**Description**  
Filter the payload used to deactivate a license before it is cleaned up or used in any way.
Fired during client license API request.

**Parameters**  
`$dirty_payload`
> (array) the dirty payload used to attempt to deactivate a license

___
### wppus_activate_license_payload

```php
apply_filters( 'wppus_activate_license_payload', array $payload );
```

**Description**  
Filter the payload used to activate a license - fired only if the payload was valid.
Fired during client license API request.

**Parameters**  
`$payload`
> (array) the payload used to activate a license

___
### wppus_deactivate_license_payload

```php
apply_filters( 'wppus_deactivate_license_payload', array $payload );
```

**Description**  
Filter the payload used to deactivate a license - fired only if the payload was valid.
Fired during client license API request.

**Parameters**  
`$payload`
> (array) the payload used to deactivate a license

___
### wppus_browse_licenses_payload

```php
apply_filters( 'wppus_browse_licenses_payload', array $payload );
```

**Description**  
Filter the payload used to browse licenses - before the payload has been cleaned up and the License Query has been validated.
Fired during client license API request.

**Parameters**  
`$payload`
> (array) a dirty payload for a License Query

___
### wppus_read_license_payload

```php
apply_filters( 'wppus_read_license_payload', array $payload );
```

**Description**  
Filter the payload used to read a license record - after the payload has been cleaned up, before the payload has been validated.
Fired during client license API request.

**Parameters**  
`$payload`
> (array) payload used to read a license record

___
### wppus_edit_license_payload

```php
apply_filters( 'wppus_edit_license_payload', array $payload );
```

**Description**  
Filter the payload used to edit a license record - after the payload has been cleaned up, before the payload has been validated.
Fired during client license API request.

**Parameters**  
`$payload`
> (array) payload used to edit a license record

___
### wppus_add_license_payload

```php
apply_filters( 'wppus_add_license_payload', array $payload );
```

**Description**  
Filter the payload used to add a license record - after the payload has been cleaned up, before the payload has been validated.
Fired during client license API request.

**Parameters**  
`$payload`
> (array) payload used to add a license record

___
### wppus_delete_license_payload

```php
apply_filters( 'wppus_delete_license_payload', array $payload );
```

**Description**  
Filter the payload used to delete a license record - after the payload has been cleaned up, before the payload has been validated.
Fired during client license API request.

**Parameters**  
`$payload`
> (array) payload used to delete a license record

___
### wppus_license_public_api_actions

```php
apply_filters( 'wppus_license_public_api_actions', array $public_api_actions );
```

**Description**  
Filter the public API actions ; public actions can be accessed via the `GET` method, all other actions are considered private and can only be accessed via the `POST` method.

**Parameters**  
`$public_api_actions`
> (array) the public API actions  

___
### wppus_license_api_request_authorized

```php
apply_filters( 'wppus_license_api_request_authorized', bool $authorized, string $method, array $payload );
```

**Description**  
Filter whether the License API request is authorized.

**Parameters**  
`$authorized`
> (bool) whether the License API request is authorized  

`$method`
> (string) the method of the request - `GET` or `POST`  

`$payload`
> (array) the payload of the request  

___
### wppus_license_bypass_signature

```php
apply_filters( 'wppus_license_bypass_signature', bool $bypass, object $license );
```

**Description**  
Filter whether to bypass the license signature check.  
Fired during client license API request.

**Parameters**  
`$bypass`
> (bool) whether to bypass the license signature check  

`$license`
> (string) the license object  
___
### wppus_api_license_actions

```php
apply_filters( 'wppus_api_license_actions', array $actions );
```

**Description**  
Filter the License API actions available for API access control.  

**Parameters**  
`$actions`
> (array) the API actions  

___
### wppus_api_license_actions

```php
apply_filters( 'wppus_license_update_server_prepare_license_for_output', array $output, object $license );
```

**Description**  
Filter the license data to send to the remote client.  

**Parameters**  
`$output`
> (array) the license data to send to the remote client  

`$license`
> (array) the original license object  

___
