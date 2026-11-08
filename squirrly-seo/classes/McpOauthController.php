<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

/**
 * Loads the bundled OAuth 2.1 layer so Squirrly can be added to claude.ai as a standard
 * connector, instead of only through a local MCP client holding an Application Password.
 *
 * claude.ai does not ask the user for a username and password when it adds a connector. It
 * asks the site who signs people in, by fetching /.well-known/oauth-protected-resource and
 * /.well-known/oauth-authorization-server. The MCP Adapter plugin serves neither and returns
 * a bare 401, so there is nothing to discover and the connector reports that authorization
 * failed. The bundled library publishes both documents, adds the authorize/token endpoints,
 * and recognises Claude as a known client.
 *
 * Everything here is inert unless the site opts in: the switch is off by default, and each
 * guard below fails closed.
 *
 * Class SQ_Classes_McpOauthController
 */
class SQ_Classes_McpOauthController {

	/** @var string minimum PHP version - the bundled library is PHP 7.4 source */
	const MIN_PHP = '7.4';

	/** @var string REST route of the MCP server the library registers */
	const SERVER_ROUTE = 'mcp/mcp-oauth-server';

	/** @var string id of the MCP server the library creates */
	const SERVER_ID = 'mcp-oauth-server';

	/** @var array the OpenAI client documents we have checked: Codex and the ChatGPT web connector */
	const OPENAI_CLIENT_DOCUMENTS = array(
		'https://chatgpt.com/oauth/codex/client.json',
		'https://chatgpt.com/oauth/client.json',
	);

	/** @var array the discovery documents claude.ai fetches, by label */
	public static $discovery = array(
		'protected-resource'   => '/.well-known/oauth-protected-resource',
		'authorization-server' => '/.well-known/oauth-authorization-server',
	);

	public function __construct() {

		//The library is PHP 7.4 source. Nothing below may be require()d on an older PHP, so
		//this is checked before any include, not inside one.
		if ( version_compare( PHP_VERSION, self::MIN_PHP, '<' ) ) {
			return;
		}

		//WordPress < 6.9 has no Abilities API, so there is nothing to expose over MCP
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		//Off unless the site turned it on in Settings > Connect Tools > AI Tools
		if ( ! SQ_Classes_Helpers_Tools::getOption( 'sq_mcp_oauth' ) ) {
			add_action( 'plugins_loaded', array( $this, 'cleanup' ), 20 );

			return;
		}

		add_action( 'plugins_loaded', array( $this, 'load' ), 5 );
	}

	/**
	 * Wire the OAuth layer, on plugins_loaded so every other plugin has been given the
	 * chance to declare its own copy of the library first.
	 */
	public function load() {

		//WP Rocket and Imagify load the same library (WPMedia\MCP\OAuth) at plugin-file time and
		//boot it when the MCP Adapter (WP\MCP) is there. It's one sign-in server per site, so when
		//theirs runs on an adapter it works with (0.7+, what the library is built for; the 0.1.0
		//inside WooCommerce has no sessions), Squirrly adds its clients to it rather than starting a second one.
		if ( class_exists( '\WPMedia\MCP\OAuth\Bootstrap' ) && self::sharedAdapterWorks() ) {

			add_filter( 'wpmedia_mcp_oauth_trusted_publishers', array( $this, 'trustedPublishers' ) );
			add_filter( 'wpmedia_mcp_oauth_server_tools', array( $this, 'serverTools' ) );
			//their copy hasn't Squirrly's ChatGPT patch
			add_filter( 'http_response', array( $this, 'publicClientDocument' ), 10, 3 );
			//their server lists only the generic tools: add Squirrly's named ones after it's created
			add_action( 'mcp_adapter_init', array( $this, 'addToolsToSharedServer' ), PHP_INT_MAX );

			try {
				\WP\MCP\Core\McpAdapter::instance();
				\WPMedia\MCP\OAuth\Bootstrap::instance();
			} catch ( Throwable $e ) {
				return;
			}

			return;
		}

		//Otherwise Squirrly runs its own copy, in its own namespace (plugin/mcp-isolate.py).
		//Another plugin's copy can't run well here (no adapter, or an old one): keep it quiet so the two don't answer the same addresses.
		if ( class_exists( '\WPMedia\MCP\OAuth\Bootstrap' ) ) {
			add_filter( 'wpmedia_mcp_oauth_server_enabled', '__return_false', PHP_INT_MAX );
		}

		//The MCP Adapter provides the MCP protocol itself. Without it there is no server to
		//authenticate against, so the OAuth endpoints would lead nowhere.
		if ( ! self::loadAdapter() ) {
			return;
		}

		if ( ! is_readable( _SQ_MCP_DIR_ . 'autoload.php' ) ) {
			return;
		}

		require_once _SQ_MCP_DIR_ . 'typed-filters.php';
		require_once _SQ_MCP_DIR_ . 'autoload.php';

		//An incomplete upload can leave Bootstrap.php in place while the sub-namespaces it
		//wires are missing - an FTP client that does not recurse, or a flattened archive,
		//produces exactly that. Booting it then throws on plugins_loaded and takes the whole
		//site down, front and admin, so one class from each sub-namespace is checked first.
		if ( ! class_exists( '\SquirrlySEO\Vendor\WPMedia\MCP\OAuth\Bootstrap' )
		     || ! class_exists( '\SquirrlySEO\Vendor\WPMedia\MCP\OAuth\Auth\AuthorizeEndpoint' )
		     || ! class_exists( '\SquirrlySEO\Vendor\WPMedia\MCP\OAuth\Auth\Discovery\Endpoints' )
		     || ! class_exists( '\SquirrlySEO\Vendor\WPMedia\MCP\OAuth\Transport\Server' ) ) {
			return;
		}

		add_filter( 'sq_mcp_oauth_trusted_publishers', array( $this, 'trustedPublishers' ) );
		add_filter( 'sq_mcp_oauth_server_tools', array( $this, 'serverTools' ) );

		//Belt and braces: whatever the library does, it must never be able to break the site
		try {
			\SquirrlySEO\Vendor\WPMedia\MCP\OAuth\Bootstrap::instance();
		} catch ( Throwable $e ) {
			return;
		}
	}

	/**
	 * Add Squirrly's named tools to the sign-in server WP Rocket or Imagify created, next to its generic
	 * discover / info / execute tools. They get the same names as on Squirrly's own server, so an assistant
	 * connected in one mode keeps working when the site switches to the other (e.g. the MCP Adapter plugin
	 * gets installed).
	 *
	 * The adapter keeps the server's tool registry private. If a future version changes that, nothing is
	 * added and the generic tools still reach every Squirrly ability.
	 *
	 * @param object $adapter WP\MCP\Core\McpAdapter, from the mcp_adapter_init action
	 */
	public function addToolsToSharedServer( $adapter ) {
		try {
			if ( ! is_object( $adapter ) || ! method_exists( $adapter, 'get_server' ) ) {
				return;
			}

			$server = $adapter->get_server( self::SERVER_ID );
			if ( ! is_object( $server ) || ! property_exists( $server, 'component_registry' ) ) {
				return;
			}

			$tools = $this->serverTools( array() );
			if ( empty( $tools ) ) {
				return;
			}

			$property = new ReflectionProperty( $server, 'component_registry' );
			$property->setAccessible( true );
			$registry = $property->getValue( $server );

			if ( is_object( $registry ) && method_exists( $registry, 'register_tools' ) ) {
				$registry->register_tools( $tools );
			}
		} catch ( Throwable $e ) {
			return;
		}
	}

	/**
	 * Whether the MCP Adapter another plugin loaded (WP\MCP) can carry a shared sign-in server.
	 *
	 * @return bool
	 */
	private static function sharedAdapterWorks() {
		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) || ! defined( '\WP\MCP\Core\McpAdapter::VERSION' ) ) {
			return false;
		}

		return version_compare( (string) constant( '\WP\MCP\Core\McpAdapter::VERSION' ), '0.7.0', '>=' );
	}

	/**
	 * Start the MCP Adapter bundled with Squirrly. It lives in its own namespace (SquirrlySEO\Vendor\WP\MCP,
	 * see plugin/mcp-isolate.py), so it runs next to the copy another plugin loads: WooCommerce 11.x
	 * bundles MCP Adapter 0.1.0, and running Squirrly's server on it failed with "Missing Mcp-Session-Id".
	 *
	 * @return bool whether the adapter is available
	 */
	public static function loadAdapter() {
		if ( ! self::hasBundledAdapter() ) {
			return false;
		}

		if ( ! class_exists( '\SquirrlySEO\Vendor\WP\MCP\Core\McpAdapter', false ) ) {
			spl_autoload_register( array( __CLASS__, 'autoloadAdapter' ) );

			if ( ! class_exists( '\SquirrlySEO\Vendor\WP\MCP\Plugin' ) ) {
				return false;
			}

			//the default server and the mcp-adapter/* abilities belong to another plugin's adapter when one is present
			add_filter( 'sq_mcp_adapter_create_default_server', array( __CLASS__, 'ownDefaultServer' ), 99 );
			add_filter( 'sq_mcp_register_shared_abilities', array( __CLASS__, 'ownDefaultServer' ), 99 );

			\SquirrlySEO\Vendor\WP\MCP\Plugin::instance();
		}

		\SquirrlySEO\Vendor\WP\MCP\Core\McpAdapter::instance();

		return true;
	}

	/**
	 * Squirrly's adapter creates the default server and registers the generic mcp-adapter/* abilities only
	 * when no other plugin's MCP Adapter is loaded; otherwise the same route and ability names would collide.
	 * Checked when the adapter asks (init / rest_api_init), after every plugin has registered its autoloader.
	 *
	 * @param bool $enabled
	 *
	 * @return bool
	 */
	public static function ownDefaultServer( $enabled ) {
		return $enabled && ! class_exists( '\WP\MCP\Core\McpAdapter' );
	}

	/**
	 * @return bool
	 */
	public static function hasBundledAdapter() {
		return is_readable( _SQ_MCP_DIR_ . 'mcp-adapter/includes/Plugin.php' );
	}

	/**
	 * Load a class of the bundled MCP Adapter or of its MCP schema package.
	 *
	 * @param string $class
	 */
	public static function autoloadAdapter( $class ) {
		$map = array(
			'SquirrlySEO\\Vendor\\WP\\McpSchema\\' => _SQ_MCP_DIR_ . 'mcp-adapter/vendor/wordpress/php-mcp-schema/src/',
			'SquirrlySEO\\Vendor\\WP\\MCP\\'       => _SQ_MCP_DIR_ . 'mcp-adapter/includes/',
		);

		foreach ( $map as $prefix => $dir ) {
			if ( strpos( $class, $prefix ) === 0 ) {
				$file = $dir . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';

				if ( is_readable( $file ) ) {
					require $file;
				}

				return;
			}
		}
	}

	/**
	 * Publish each Squirrly ability as its own tool on the connector, so the AI client can
	 * list and permit them one by one. Falls back to the generic tools when none are found.
	 *
	 * @param array $tools The generic discover, info and execute tools.
	 *
	 * @return array
	 */
	public function serverTools( $tools ) {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return $tools;
		}

		$squirrly = array();
		foreach ( wp_get_abilities() as $ability ) {
			$meta = $ability->get_meta();

			if ( strpos( $ability->get_name(), 'squirrly/' ) === 0 && ! empty( $meta['mcp']['public'] ) ) {
				$squirrly[] = $ability->get_name();
			}
		}

		return apply_filters( 'sq_mcp_server_tools', ! empty( $squirrly ) ? $squirrly : (array) $tools, (array) $tools );
	}

	/**
	 * Which AI clients may sign in to this site.
	 *
	 * The bundled library ships with claude.ai alone, so every other assistant is turned away
	 * at the authorize screen with "Unknown OAuth client" - the host is rejected before its
	 * metadata document is even fetched. Squirrly is built to be reached by more than one
	 * assistant, so the clients we have verified are added here.
	 *
	 * A publisher is only ever an addition. The library still requires an exact client_id
	 * match, a matching host, and a public client (token_endpoint_auth_method "none"), and the
	 * user still has to approve the connection on their own login screen.
	 *
	 * @param array $publishers Publishers the library already trusts.
	 *
	 * @return array
	 */
	public function trustedPublishers( $publishers ) {

		$publishers = (array) $publishers;

		//OpenAI Codex (native, loopback redirects) and the ChatGPT web connector, which redirects
		//to chatgpt.com and lists "none" among its token methods. Both documents checked 2026-10-16.
		$publishers['openai'] = array(
			'client_ids' => self::OPENAI_CLIENT_DOCUMENTS,
			'host'       => 'chatgpt.com',
		);

		/**
		 * Filters the AI clients allowed to sign in to this site over MCP.
		 *
		 * Adding a publisher lets that client reach the approval screen; it does not grant it
		 * anything. Only add clients whose metadata document you have checked.
		 *
		 * @param array $publishers Keyed by slug, each with client_ids (exact URLs) and host.
		 */
		return (array) apply_filters( 'sq_mcp_trusted_publishers', $publishers );
	}

	/**
	 * Choose the public-client method for the ChatGPT documents, when they offer it.
	 *
	 * ChatGPT's document names private_key_jwt first and also lists "none", the only method this
	 * server supports. Squirrly's copy of the library accepts that, but another plugin's copy
	 * (WP Rocket, Imagify) loads first on those sites and turns ChatGPT away with "Unknown OAuth
	 * client". Choosing "none" here works with any copy and changes nothing else: only the exact
	 * trusted documents are touched, and a document that doesn't offer "none" stays rejected.
	 *
	 * @param array|WP_Error $response
	 * @param array          $args
	 * @param string         $url
	 *
	 * @return array|WP_Error
	 */
	public function publicClientDocument( $response, $args, $url ) {

		if ( is_wp_error( $response ) || ! in_array( $url, self::OPENAI_CLIENT_DOCUMENTS, true ) ) {
			return $response;
		}

		$doc = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $doc ) || empty( $doc['token_endpoint_auth_methods_supported'] ) || ! in_array( 'none', (array) $doc['token_endpoint_auth_methods_supported'], true ) ) {
			return $response;
		}

		$doc['token_endpoint_auth_method'] = 'none';
		$response['body']                  = wp_json_encode( $doc );

		return $response;
	}

	/**
	 * Drop the OAuth rewrite rules once, after the switch is turned off.
	 *
	 * The library adds its rules on init and flushes them itself the first time it runs.
	 * Once it stops loading, nothing removes them again, so the /oauth/ and /.well-known/
	 * paths would stay in the stored rewrite rules for as long as the site lives.
	 */
	public function cleanup() {

		//Another plugin embeds the same library and still wants these rules
		if ( class_exists( '\WPMedia\MCP\OAuth\Bootstrap' ) ) {
			return;
		}

		//The library's own marker. Absent means there is nothing left to clean up.
		if ( get_option( 'wpmedia_mcp_oauth_rewrite_version' ) === false ) {
			return;
		}

		delete_option( 'wpmedia_mcp_oauth_rewrite_version' );

		add_action( 'init', 'flush_rewrite_rules', 99 );
	}

	/**
	 * Whether the switch is on and everything it depends on is present.
	 *
	 * @return bool
	 */
	public static function isActive() {
		return (
			version_compare( PHP_VERSION, self::MIN_PHP, '>=' )
			&& function_exists( 'wp_register_ability' )
			&& ( class_exists( '\WP\MCP\Core\McpAdapter' ) || self::hasBundledAdapter() )
			&& SQ_Classes_Helpers_Tools::getOption( 'sq_mcp_oauth' )
		);
	}

	/**
	 * The address the site owner pastes into claude.ai.
	 *
	 * @return string
	 */
	public static function getServerUrl() {
		return get_rest_url( null, self::SERVER_ROUTE );
	}

	/**
	 * Link that opens Claude's Add custom connector box with this site already filled in.
	 * The domain is in the name so a user connecting several sites can tell them apart.
	 *
	 * @return string
	 */
	public static function getClaudeConnectUrl() {
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );

		return 'https://claude.ai/customize/connectors?' . http_build_query( array(
				'modal'         => 'add-custom-connector',
				'connectorName' => trim( 'Squirrly SEO ' . ( $host <> '' ? '(' . $host . ')' : '' ) ),
				'connectorUrl'  => self::getServerUrl(),
			), '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Why the switch cannot be used on this site, if it cannot.
	 *
	 * @return string empty when there is nothing blocking it
	 */
	public static function getBlocker() {

		if ( version_compare( PHP_VERSION, self::MIN_PHP, '<' ) ) {
			return sprintf(
			/* translators: %1$s: required PHP version, %2$s: PHP version the site runs */
				esc_html__( "This needs PHP %1\$s or newer. Your site runs PHP %2\$s.", 'squirrly-seo' ),
				self::MIN_PHP,
				PHP_VERSION
			);
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			return esc_html__( "This needs WordPress 6.9 or newer, where the Abilities API is part of WordPress itself.", 'squirrly-seo' );
		}

		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) && ! self::hasBundledAdapter() ) {
			return esc_html__( "Install the free MCP Adapter plugin, from Plugins - Add New, and activate it. Squirrly registers the SEO tools; the MCP Adapter is what carries them to an AI assistant.", 'squirrly-seo' );
		}

		if ( strpos( home_url(), 'https://' ) !== 0 ) {
			return esc_html__( "Your site address does not start with https://. An AI assistant will not connect to a site that is not served over HTTPS, so install a certificate and set the address under Settings - General first.", 'squirrly-seo' );
		}

		if ( ! get_option( 'permalink_structure' ) ) {
			return esc_html__( "Your permalinks are set to Plain. Go to Settings - Permalinks, choose any other option, and save. The sign-in addresses are ordinary WordPress URLs and Plain permalinks cannot serve them, so nothing below will work until this is changed.", 'squirrly-seo' );
		}

		return '';
	}

	/**
	 * Check that the two documents claude.ai fetches actually reach WordPress.
	 *
	 * A web server can answer them before WordPress ever runs - the usual causes are an
	 * nginx rule denying dot-folders, or a certificate rule capturing the whole of
	 * /.well-known/. Both leave the MCP address itself working, so the site looks healthy
	 * and the only symptom is claude.ai reporting that authorization failed.
	 *
	 * @return array label => array( code, ok, message )
	 */
	public static function checkDiscovery() {
		$results = array();

		foreach ( self::$discovery as $label => $path ) {

			//sslverify stays on: Claude will not connect to a site whose certificate does not
			//validate, so a certificate this site cannot verify itself is a real failure worth
			//reporting, not noise to suppress.
			$response = wp_remote_get( home_url( $path ), array(
				'timeout'     => 10,
				'redirection' => 2,
			) );

			if ( is_wp_error( $response ) ) {
				//same shape as explainCode(), so the view never has to tell them apart
				$results[ $label ] = array(
					'code'    => 0,
					'ok'      => false,
					'message' => array(
						'cause'    => sprintf(
						/* translators: %s: the error the site's own HTTP request returned */
							__( "This site could not reach its own address: %s", 'squirrly-seo' ),
							$response->get_error_message()
						),
						'footnote' => __( "A firewall or a DNS setting is stopping the site from calling itself. An AI assistant reaching the same address from outside may still fail for a different reason, so fix this first and check again.", 'squirrly-seo' ),
					),
				);
				continue;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			$results[ $label ] = array(
				'code'    => $code,
				'ok'      => ( 200 === $code && is_array( $body ) && ! empty( $body ) ),
				'message' => self::explainCode( $code ),
			);
		}

		return $results;
	}

	/**
	 * Turn the status code of a discovery document into something a site owner can act on.
	 *
	 * A single sentence of nginx configuration is unreadable in a settings screen, so this
	 * returns the parts separately: what is wrong, the rule to look for, and the rule to use
	 * instead. The view renders the two rules as code blocks.
	 *
	 * @param int $code HTTP status code.
	 *
	 * @return array Empty when there is nothing wrong.
	 */
	protected static function explainCode( $code ) {

		switch ( (int) $code ) {

			case 200:
				return array();

			case 403:
				return array(
					'cause'      => __( "Your web server is refusing every address that begins with a dot, before WordPress ever sees it.", 'squirrly-seo' ),
					'find_label' => __( "Look for a rule like this in your site's nginx configuration:", 'squirrly-seo' ),
					'find_code'  => 'location ~ /\. { deny all; }',
					'fix_label'  => __( "Add this above that rule, so only the sign-in addresses are let through:", 'squirrly-seo' ),
					'fix_code'   => "location ^~ /.well-known/ {\n    try_files \$uri \$uri/ /index.php?\$args;\n}",
					'footnote'   => __( "On Apache the equivalent is a rule in .htaccess that blocks dot-folders. Your host can make the same exception.", 'squirrly-seo' ),
				);

			case 404:
				return array(
					'cause'      => __( "Something on your server answers this address before WordPress does, so WordPress never gets the chance to reply.", 'squirrly-seo' ),
					'find_label' => __( "On nginx this is usually a certificate rule written too broadly:", 'squirrly-seo' ),
					'find_code'  => 'location ^~ /.well-known/ { root /var/www/letsencrypt; }',
					'fix_label'  => __( "Narrow it to the certificate path only, so every other address still reaches WordPress:", 'squirrly-seo' ),
					'fix_code'   => 'location ^~ /.well-known/acme-challenge/ { root /var/www/letsencrypt; }',
					'footnote'   => __( "If your permalinks are set to Plain, change that first: these are ordinary WordPress addresses and Plain permalinks cannot serve them.", 'squirrly-seo' ),
				);

			default:
				return array(
					'cause'    => sprintf(
					/* translators: %d: HTTP status code */
						__( "This address answered with status %d instead of publishing the sign-in details.", 'squirrly-seo' ),
						(int) $code
					),
					'footnote' => __( "Open the address in a browser to see what is answering it. Anything other than a short block of JSON means the request is not reaching WordPress.", 'squirrly-seo' ),
				);
		}
	}
}
