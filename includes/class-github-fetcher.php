<?php
namespace Oportunidades\Includes;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GitHub_Fetcher {
	/**
	 * Fetch data from GitHub repository.
	 *
	 * @param string $repo_url GitHub repository URL (e.g., https://github.com/user/repo)
	 * @param string $branch Branch name (default: main)
	 * @param string $file_path Path to the data file in the repository
	 * @return array|WP_Error The fetched data or error
	 */
	public function fetch_from_github( $repo_url, $branch = 'main', $file_path = 'output/oportunidades.json' ) {
		// Parse repository URL to extract owner and repo name
		$parsed = $this->parse_github_url( $repo_url );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		list( $owner, $repo ) = $parsed;

		// Construct raw GitHub URL
		$raw_url = sprintf(
			'https://raw.githubusercontent.com/%s/%s/%s/%s',
			$owner,
			$repo,
			$branch,
			ltrim( $file_path, '/' )
		);

		// Fetch the file content
		$response = wp_remote_get(
			$raw_url,
			[
				'timeout'     => 30,
				'user-agent'  => 'WordPress/Oportunidades-Plugin',
				'sslverify'   => true,
				'headers'     => [
					'Accept' => 'application/json',
				],
			]
		);

		// Check for errors
		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'github_fetch_error',
				sprintf(
					__( 'Erro ao conectar ao GitHub: %s', 'oportunidades' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			return new \WP_Error(
				'github_fetch_error',
				sprintf(
					__( 'Erro ao buscar dados do GitHub. Código HTTP: %d. URL: %s', 'oportunidades' ),
					$status_code,
					$raw_url
				)
			);
		}

		// Get and parse content
		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return new \WP_Error(
				'github_empty_response',
				__( 'Resposta vazia do GitHub.', 'oportunidades' )
			);
		}

		// Try to decode JSON
		$data = json_decode( $body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'github_json_error',
				sprintf(
					__( 'Erro ao decodificar JSON: %s', 'oportunidades' ),
					json_last_error_msg()
				)
			);
		}

		return $data;
	}

	/**
	 * Parse GitHub URL to extract owner and repository name.
	 *
	 * @param string $url GitHub repository URL
	 * @return array|WP_Error Array with [owner, repo] or error
	 */
	protected function parse_github_url( $url ) {
		// Remove trailing slashes and .git extension
		$url = rtrim( $url, '/' );
		$url = preg_replace( '/\.git$/', '', $url );

		// Match GitHub URL pattern
		if ( preg_match( '#github\.com[:/]([^/]+)/([^/]+)#', $url, $matches ) ) {
			return [ $matches[1], $matches[2] ];
		}

		return new \WP_Error(
			'invalid_github_url',
			__( 'URL do GitHub inválida. Use o formato: https://github.com/owner/repo', 'oportunidades' )
		);
	}

	/**
	 * Fetch and import data from GitHub in one step.
	 *
	 * @param Importer $importer Importer instance
	 * @param array $settings Plugin settings
	 * @return array Import summary
	 */
	public function fetch_and_import( Importer $importer, array $settings ) {
		$repo_url   = $settings['github_repo_url'] ?? '';
		$branch     = $settings['github_branch'] ?? 'main';
		$file_path  = $settings['github_file_path'] ?? 'output/oportunidades.json';

		if ( empty( $repo_url ) ) {
			throw new Exception( __( 'URL do repositório GitHub não configurada.', 'oportunidades' ) );
		}

		// Fetch data from GitHub
		$data = $this->fetch_from_github( $repo_url, $branch, $file_path );
		if ( is_wp_error( $data ) ) {
			throw new Exception( $data->get_error_message() );
		}

		// Import the fetched data
		$summary = $importer->import( $data, 'github' );

		// Log the last fetch time
		update_option( 'oportunidades_last_github_fetch', current_time( 'mysql', true ) );

		return $summary;
	}
}
