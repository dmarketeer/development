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
			$error_message = sprintf(
				__( 'Erro ao buscar dados do GitHub. Código HTTP: %d. URL: %s', 'oportunidades' ),
				$status_code,
				$raw_url
			);

			// Add specific help for 404 errors
			if ( $status_code === 404 ) {
				$error_message .= "\n\n" . sprintf(
					__( 'O ficheiro não foi encontrado. Verifique se: 1) O repositório está correto, 2) O branch "%s" existe, 3) O caminho "%s" está correto e o ficheiro existe no repositório.', 'oportunidades' ),
					$branch,
					$file_path
				);
			}

			return new \WP_Error(
				'github_fetch_error',
				$error_message
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
	 * Validate GitHub configuration by checking if the file exists.
	 *
	 * @param string $repo_url GitHub repository URL
	 * @param string $branch Branch name
	 * @param string $file_path Path to the data file
	 * @return true|WP_Error True if valid, WP_Error otherwise
	 */
	public function validate_config( $repo_url, $branch = 'main', $file_path = 'output/oportunidades.json' ) {
		// Parse repository URL
		$parsed = $this->parse_github_url( $repo_url );
		if ( is_wp_error( $parsed ) ) {
			return $parsed;
		}

		list( $owner, $repo ) = $parsed;

		// Check if repository exists via GitHub API
		$api_url = sprintf( 'https://api.github.com/repos/%s/%s', $owner, $repo );
		$response = wp_remote_get(
			$api_url,
			[
				'timeout'     => 15,
				'user-agent'  => 'WordPress/Oportunidades-Plugin',
				'headers'     => [
					'Accept' => 'application/vnd.github.v3+json',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'github_validation_error',
				sprintf(
					__( 'Erro ao validar repositório: %s', 'oportunidades' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code === 404 ) {
			return new \WP_Error(
				'github_repo_not_found',
				sprintf(
					__( 'Repositório não encontrado: %s/%s', 'oportunidades' ),
					$owner,
					$repo
				)
			);
		} elseif ( $status_code !== 200 ) {
			return new \WP_Error(
				'github_validation_error',
				sprintf(
					__( 'Erro ao validar repositório. Código HTTP: %d', 'oportunidades' ),
					$status_code
				)
			);
		}

		// Check if file exists
		$file_url = sprintf(
			'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
			$owner,
			$repo,
			ltrim( $file_path, '/' ),
			$branch
		);

		$file_response = wp_remote_get(
			$file_url,
			[
				'timeout'     => 15,
				'user-agent'  => 'WordPress/Oportunidades-Plugin',
				'headers'     => [
					'Accept' => 'application/vnd.github.v3+json',
				],
			]
		);

		$file_status = wp_remote_retrieve_response_code( $file_response );
		if ( $file_status === 404 ) {
			return new \WP_Error(
				'github_file_not_found',
				sprintf(
					__( 'Ficheiro não encontrado: "%s" no branch "%s". Verifique se o caminho e o branch estão corretos.', 'oportunidades' ),
					$file_path,
					$branch
				)
			);
		} elseif ( $file_status !== 200 ) {
			return new \WP_Error(
				'github_validation_error',
				sprintf(
					__( 'Erro ao validar ficheiro. Código HTTP: %d', 'oportunidades' ),
					$file_status
				)
			);
		}

		return true;
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
