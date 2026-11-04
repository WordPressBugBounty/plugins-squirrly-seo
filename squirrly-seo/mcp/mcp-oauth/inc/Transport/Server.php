<?php
declare( strict_types=1 );

namespace WPMedia\MCP\OAuth\Transport;

use SquirrlySEO\Vendor\WP\MCP\Core\McpAdapter;
use SquirrlySEO\Vendor\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;
use WPMedia\MCP\OAuth\Context;

class Server {
	/**
	 * OAuth server context.
	 *
	 * @var Context
	 */
	private $context;

	/**
	 * Constructor.
	 *
	 * @param Context $context OAuth server context.
	 */
	public function __construct( Context $context ) {
		$this->context = $context;
	}

	/**
	 * Register the MCP server with the adapter.
	 *
	 * Creates an isolated server at /wp-json/mcp/mcp-oauth-server using the custom
	 * OAuthHttpTransport for JWT Bearer authentication.
	 *
	 * @return void
	 */
	public function register_server(): void {
		if ( ! class_exists( McpAdapter::class ) ) {
			return;
		}

		$adapter = McpAdapter::instance();

		// Squirrly patch: let the host plugin publish its abilities as named tools,
		// so the client lists "Read page SEO" instead of one generic "Execute Ability".
		$tools = wpm_apply_filters_typed(
			'array',
			'wpmedia_mcp_oauth_server_tools',
			[
				'mcp-adapter/discover-abilities',
				'mcp-adapter/get-ability-info',
				'mcp-adapter/execute-ability',
			]
		);

		$adapter->create_server(
			'mcp-oauth-server',
			'mcp',
			'mcp-oauth-server',
			'MCP OAuth Server',
			'MCP Server with OAuth 2.1 authentication',
			'v1.0.0',
			[ OAuthHttpTransport::class ],
			ErrorLogMcpErrorHandler::class,
			$this->context->observability_handler(),
			$tools
		);
	}
}
