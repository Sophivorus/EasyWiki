<?php

namespace Sophivorus;

/**
 * EasyWiki is a friendly PHP client for the MediaWiki Action API
 */
class EasyWiki {

	/**
	 * Will store the URL of the MediaWiki API endpoint being used
	 * @var string
	 * @internal
	 */
	private $api;

	/**
	 * Initialize EasyWiki 
	 * @param string $api URL of the MediaWiki API endpoint to use
	 * @return self EasyWiki instance
	 */
	public function __construct( string $api ) {
		$this->api = $api;
	}

	################
	# BASE METHODS #
	################

	/**
	 * Do a GET request to the API
	 * @param array $params Parameters of the GET request
	 * @return array Response data
	 */
	public function get( array $params = [] ) {
		$params += [
			'format' => 'json',
			'formatversion' => 2,
			'errorformat' => 'plaintext'
		];
		$params = array_filter( $params, function ( $value ) {
			return !is_null( $value );
		} );
		$query = http_build_query( $params );
		$curl = curl_init( $this->api . '?' . $query );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_COOKIEJAR, '/tmp/cookie.txt' );
		curl_setopt( $curl, CURLOPT_COOKIEFILE, '/tmp/cookie.txt' );
		$response = curl_exec( $curl );
		curl_close( $curl );
		if ( !$response ) {
			$message = curl_error( $curl );
			throw new \Exception( $message );
		}
		$data = json_decode( $response, true );
		return $data;
	}

	/**
	 * Do a POST request to the API
	 * @param array $params Parameters of the GET request
	 * @return array Response data
	 */
	public function post( array $params = [] ) {
		$params += [
			'format' => 'json',
			'formatversion' => 2,
			'errorformat' => 'plaintext'
		];
		$params = array_filter( $params, function ( $value ) {
			return !is_null( $value );
		} );
		$curl = curl_init( $this->api );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $params );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_COOKIEJAR, '/tmp/cookie.txt' );
		curl_setopt( $curl, CURLOPT_COOKIEFILE, '/tmp/cookie.txt' );
		$response = curl_exec( $curl );
		curl_close( $curl );
		if ( !$response ) {
			$message = curl_error( $curl );
			throw new \Exception( $message );
		}
		$data = json_decode( $response, true );
		return $data;
	}

	/**
	 * Recursively search for a key in an array and return its value
	 * @param string $needle Key to find
	 * @param array $haystack Array where to search
	 * @return mixed Value of the needle or null if not found
	 */
	public function find( string $needle, array $haystack ) {
		if ( $needle === '' ) {
			return $haystack;
		}
		$values = [];
		foreach ( $haystack as $key => $value ) {
			if ( strval( $key ) === $needle ) {
				$values[] = $value;
			}
			if ( is_array( $value ) ) {
				$value = $this->find( $needle, $value );
				if ( !is_null( $value ) ) {
					$values[] = $value;
				}
			}
		}
		if ( $values ) {
			if ( count( $values ) > 1 ) {
				return $values;
			}
			return $values[0];
		}
	}

	##################
	# ACTION METHODS #
	##################

	/**
	 * Log in with a bot account
	 * @param string $user Bot username
	 * @param string $pass Bot password
	 * @return array Response data
	 */
	public function login( string $user, string $pass ) {
		$this->logout();
		$response = $this->post( [
			'action' => 'login',
			'lgname' => $user,
			'lgpassword' => $pass,
			'lgtoken' => $this->getToken( 'login' ),
		] );
		$result = $this->find( 'result', $response );
		if ( $result === 'Failed' ) {
			$message = $this->find( 'text', $response );
			throw new \Exception( $message );
		}
		return $response;
	}

	/**
	 * Log out
	 */
	public function logout() {
		return $this->post( [ 'action' => 'logout' ] );
	}

	/**
	 * Parse content
	 * @param array $params Additional params for the parse module
	 * @return array Response data
	 */
	public function parse( array $params = [] ) {
		$params += [
			'action' => 'parse',
		];
		return $this->get( $params );
	}

	/**
	 * Query the site
	 * @param array $params Additional params for the query module
	 * @return array Response data
	 */
	public function query( array $params = [] ) {
		$params += [
			'action' => 'query',
			'redirects' => 1,
		];
		return $this->get( $params );
	}

	/**
	 * Edit a page
	 * @param string|int $page Name or ID of the page to edit
	 * @param string $text New wikitext of the page
	 * @param array $params Additional params for the edit module
	 * @return array Reponse data
	 */
	public function edit( $page, array $params = [] ) {
		$params += [
			'action' => 'edit',
			'token' => $this->getToken(),
			'title' => is_string( $page ) ? $page : null,
			'pageid' => is_int( $page ) ? $page : null,
		];
		return $this->post( $params );
	}

	/**
	 * Move (rename) a page
	 * @param string|int $from Name or ID of the page to move
	 * @param string $to New page name
	 * @param array $params Additional params for the move module
	 * @return array Reponse data
	 */
	public function move( $from, string $to, array $params = [] ) {
		$params += [
			'action' => 'move',
			'token' => $this->getToken(),
			'from' => is_string( $from ) ? $from : null,
			'fromid' => is_int( $from ) ? $from : null,
			'to' => $to,
		];
		return $this->post( $params );
	}

	/**
	 * Delete a page
	 * @param string|int $page Name or ID of the page to delete
	 * @param array $params Additional parameters for the delete module
	 * @return array Reponse data
	 */
	public function delete( $page, array $params = [] ) {
		$params += [
			'action' => 'delete',
			'token' => $this->getToken(),
			'title' => is_string( $page ) ? $page : null,
			'pageid' => is_int( $page ) ? $page : null,
		];
		return $this->post( $params );
	}

	#####################
	# SHORTHAND METHODS #
	#####################

	/**
	 * Get a token for write actions
	 * @see https://en.wikipedia.org/w/api.php?action=help&modules=query+tokens
	 * @see https://en.wikipedia.org/w/api.php?action=query&meta=tokens
	 * @see https://www.mediawiki.org/wiki/API:Tokens
	 * @param string $type Type of token to get
	 * @return string Token
	 */
	 public function getToken( string $type = 'csrf' ) {
		$params = [
			'meta' => 'tokens',
			'type' => $type
		];
		$data = $this->query( $params );
		return $this->find( $type . 'token', $data );
	}

	/**
	 * Create a page
	 * @see https://en.wikipedia.org/w/api.php?action=help&modules=edit
	 * @param string|int $page Name or ID of the page to edit
	 * @param string $text Wikitext of the new page
	 * @param array $params Additional parameters for the edit module
	 */
	public function create( $page, string $text, array $params = [] ) {
		$params += [
			'createonly' => true,
			'recreate' => true,
			'text' => $text,
		];
		return $this->edit( $page, $params );
	}

	/**
	 * Prepend wikitext to a page
	 * @see https://en.wikipedia.org/w/api.php?action=help&modules=edit
	 * @param string|int $page Name or ID of the page to edit
	 * @param string $text Wikitext to prepend
	 * @param array $params Additional parameters for the edit module
	 * @return array Edit module response
	 */
	public function prepend( $page, string $text, array $params = [] ) {
		$params += [
			'prependtext' => $text,
		];
		return $this->edit( $page, $params );
	}

	/**
	 * Append wikitext to a page
	 * @see https://en.wikipedia.org/w/api.php?action=help&modules=edit
	 * @param string|int $page Name or ID of the page to edit
	 * @param string $text Wikitext to append
	 * @param array $params Additional parameters for the edit module
	 * @return array Edit module response
	 */
	public function append( $page, string $text, array $params = [] ) {
		$params += [
			'appendtext' => $text,
		];
		return $this->edit( $page, $params );
	}

	/**
	 * Get the wikitext of a page
	 * @see https://en.wikipedia.org/w/api.php?action=help&modules=parse
	 * @see https://en.wikipedia.org/w/api.php?action=parse&formatversion=2&prop=wikitext&page=Example
	 * @param string|int $page Name or ID of the page
	 * @param array $params Additional parameters for the parse module
	 * @return string Wikitext of the page
	 */
	public function getWikitext( $page, array $params = [] ) {
		$params += [
			'page' => is_string( $page ) ? $page : null,
			'pageid' => is_int( $page ) ? $page : null,
			'prop' => 'wikitext',
		];
		$data = $this->parse( $params );
		return $this->find( 'wikitext', $data );
	}

	/**
	 * Get the HTML of a page
	 * @see https://en.wikipedia.org/w/api.php?action=help&modules=parse
	 * @see https://en.wikipedia.org/w/api.php?action=parse&formatversion=2&prop=text&page=Example
	 * @param string|int $page Name or ID of the page
	 * @param array $params Additional parameters for the parse module
	 * @return string HTML of the page
	 */
	public function getHTML( $page, array $params = [] ) {
		$params += [
			'page' => is_string( $page ) ? $page : null,
			'pageid' => is_int( $page ) ? $page : null,
			'prop' => 'text',
		];
		$data = $this->parse( $params );
		return $this->find( 'text', $data );
	}

	/**
	 * Get the categories of a page
	 * @see https://en.wikipedia.org/w/api.php?action=help&modules=query+categories
	 * @param string|int $page Name or ID of the page
	 * @param array $params Additional parameters for the parse module
	 * @return array Page categories
	 */
	public function getCategories( $page, array $params = [] ) {
		$params += [
			'titles' => is_string( $page ) ? $page : null,
			'pageids' => is_int( $page ) ? $page : null,
			'prop' => 'categories',
			'cllimit' => 'max',
		];
		$data = $this->query( $params );
		$page = $this->find( '0', $data );
		$categories = $this->find( 'categories', $page );
		if ( !$categories ) {
			return [];
		}
		$titles = $this->find( 'title', $categories );
		if ( is_string( $titles ) ) {
			return [ $titles ];
		}
		return $titles;
	}

	/**
	 * Get basic info about a page
	 * @see https://en.wikipedia.org/w/api.php?action=help&modules=query+info
	 * @param string|int $page Name or ID of the page
	 * @param array $params Additional parameters for the parse module
	 * @return array|string Page info
	 */
	public function getPageInfo( $page, array $params = [] ) {
		$params += [
			'titles' => is_string( $page ) ? $page : null,
			'pageids' => is_int( $page ) ? $page : null,
			'prop' => 'info',
		];
		$data = $this->query( $params );
		return $this->find( '0', $data );
	}

	/**
	 * Get general info about the site
	 * @see https://en.wikipedia.org/w/api.php?action=help&modules=query+siteinfo
	 * @param array $params Additional parameters for the parse module
	 * @return array|string General site info
	 */
	public function getSiteInfo( array $params = [] ) {
		$params += [
			'meta' => 'siteinfo',
		];
		$data = $this->query( $params );
		return $this->find( 'general', $data );
	}

	/**
	 * Get info about the namespaces of the site
	 * @see https://en.wikipedia.org/w/api.php?action=help&modules=query+siteinfo
	 * @param array $params Additional parameters for the parse module
	 * @return array|string Namespaces info
	 */
	public function getNamespaces( array $params = [] ) {
		$params += [
			'meta' => 'siteinfo',
			'siprop' => 'namespaces',
		];
		$data = $this->query( $params );
		return $this->find( 'namespaces', $data );
	}
}