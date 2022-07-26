<?php

/**
 * The EasyWiki library is composed of this single class
 */
class EasyWiki {

	/**
	 * Stores the URL of the MediaWiki API endpoint being used
	 * @var string
	 * @internal
	 */
	private $api;

	/**
	 * Initialize EasyWiki 
	 * @param string $api URL of the MediaWiki API endpoint to use
	 * @param string $user Bot username to log in with
	 * @param string $pass Bot password to log in with
	 * @return self EasyWiki instance
	 */
	public function __construct( string $api = '', string $user = '', string $pass = '' ) {
		if ( $api ) {
			$this->api = $api;
		}
		if ( $user && $pass ) {
			$this->login( $user, $pass );
		}
	}

	################
	# BASE METHODS #
	################

	/**
	 * Do a GET request to the API
	 * @param array $params Parameters of the GET request
	 * @param string $needle Key of the data to extract from the response
	 * @return array Response data
	 */
	public function get( array $params = [], string $needle = '' ) {
		$params += [
			'format' => 'json',
			'formatversion' => 2,
			'errorformat' => 'plaintext'
		];
		$params = array_filter( $params, function ( $value ) {
			return !is_null( $value );
		} );
		if ( $this->api ) {
			$query = http_build_query( $params );
			$curl = curl_init( $this->api . '?' . $query );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_COOKIEJAR, '/tmp/cookie.txt' );
			curl_setopt( $curl, CURLOPT_COOKIEFILE, '/tmp/cookie.txt' );
			$response = curl_exec( $curl );
			curl_close( $curl );
			if ( !$response ) {
				throw new Exception;
			}
			$data = json_decode( $response, true );
		} else {
			$context = RequestContext::getMain();
			$request = $context->getRequest();
			$derivative = new DerivativeRequest( $request, $params );
			$api = new ApiMain( $derivative );
			$api->execute();
			$result = $api->getResult();
			$data = $result->getResultData();
		}
		if ( $needle ) {
			$data = $this->find( $needle, $data );
		}
		return $data;
	}

	/**
	 * Do a POST request to the API
	 * @param array $params Parameters of the GET request
	 * @param string $needle Key of the data to extract from the response
	 * @return array Response data
	 */
	public function post( array $params = [], string $needle = '' ) {
		$params += [
			'format' => 'json',
			'formatversion' => 2,
			'errorformat' => 'plaintext'
		];
		$params = array_filter( $params, function ( $value ) {
			return !is_null( $value );
		} );
		if ( $this->api ) {
			$curl = curl_init( $this->api );
			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $params );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_COOKIEJAR, '/tmp/cookie.txt' );
			curl_setopt( $curl, CURLOPT_COOKIEFILE, '/tmp/cookie.txt' );
			$response = curl_exec( $curl );
			curl_close( $curl );
			if ( !$response ) {
				throw new Exception;
			}
			$data = json_decode( $response, true );
		} else {
			$context = RequestContext::getMain();
			$request = $context->getRequest();
			$derivative = new DerivativeRequest( $request, $params, true );
			$api = new ApiMain( $derivative, true );
			$api->execute();
			$result = $api->getResult();
			$data = $result->getResultData();
		}
		if ( $needle ) {
			$data = $this->find( $needle, $data );
		}
		return $data;
	}

	/**
	 * Recursively search for a key in an array and return its value
	 * @param string $needle Key to find
	 * @param array $haystack Array where to search
	 * @return mixed Value of the needle or null if not found
	 */
	public function find( string $needle, array $haystack ) {
		$values = [];
		foreach ( $haystack as $key => $value ) {
			if ( $key === $needle ) {
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
	 * Login module
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
		$login = $this->find( 'login', $response );
		if ( $login['result'] === 'Failed' ) {
			throw new Exception( $login['reason'] );
		}
		return $response;
	}

	/**
	 * Logout module
	 */
	public function logout() {
		return $this->post( [ 'action' => 'logout' ] );
	}

	/**
	 * Query module
	 * @param array $params Additional params for the query module
	 * @param string $needle Key of the data to extract from the response
	 * @return array Aggregated response data
	 */
	public function query( array $params = [], string $needle = '' ) {
		$params += [
			'action' => 'query',
			'redirects' => 1,
		];
		return $this->get( $params, $needle );
	}

	/**
	 * Parse module
	 * @param array $params Additional params for the parse module
	 * @param string $needle Key of the data to extract from the response
	 * @return array Response data
	 */
	public function parse( array $params = [], string $needle = '' ) {
		$params += [
			'action' => 'parse',
			'redirects' => 1,
		];
		return $this->get( $params, $needle );
	}

	/**
	 * Edit module
	 * @param string|int $page Name or ID of the page to edit
	 * @param array $params Additional params for the edit module
	 * @param string $needle Key of the data to extract from the response
	 * @return array Reponse data
	 */
	public function edit( $page, array $params = [], string $needle = '' ) {
		$params += [
			'action' => 'edit',
			'token' => $this->getToken(),
			'title' => is_string( $page ) ? $page : null,
			'pageid' => is_int( $page ) ? $page : null,
		];
		return $this->post( $params, $needle );
	}

	/**
	 * Move module
	 * @param string|int $from Name or ID of the page to move
	 * @param string $to New page name
	 * @param array $params Additional params for the move module
	 * @param string $needle Key of the data to extract from the response
	 * @return array Reponse data
	 */
	public function move( $from, string $to, array $params = [], string $needle = '' ) {
		$params += [
			'action' => 'move',
			'token' => $this->getToken(),
			'from' => is_string( $from ) ? $from : null,
			'fromid' => is_int( $from ) ? $from : null,
			'to' => $to,
		];
		return $this->post( $params, $needle );
	}

	/**
	 * Delete module
	 * @param string|int $page Name or ID of the page to delete
	 * @param array $params Additional parameters for delete module
	 * @param string $needle Key of the data to extract from the response
	 * @return array Reponse data
	 */
	public function delete( $page, array $params = [], $needle = '' ) {
		$params += [
			'action' => 'delete',
			'token' => $this->getToken(),
			'title' => is_string( $page ) ? $page : null,
			'pageid' => is_int( $page ) ? $page : null,
		];
		return $this->post( $params, $needle );
	}

	#####################
	# SHORTHAND METHODS #
	#####################

	/**
	 * Get a token for write actions
	 * @param string $type Type of token to get
	 * @return string Token
	 */
	 public function getToken( string $type = 'csrf', array $params = [] ) {
		$params += [
			'meta' => 'tokens',
			'type' => $type
		];
		$token = $this->query( $params, $type . 'token' );
		return $token;
	}

	/**
	 * Get the wikitext of a page
	 * @param string $page Page name or ID
	 * @param array $params Additional parameters for parse module
	 * @return string Wikitext of the page
	 */
	public function getWikitext( $page, array $params = [] ) {
		$params += [
			'page' => is_string( $page ) ? $page : null,
			'pageid' => is_int( $page ) ? $page : null,
			'prop' => 'wikitext',
		];
		$wikitext = $this->parse( $params, 'wikitext' );
		return $wikitext;
	}

	/**
	 * Get the HTML of a page
	 * @param string|int $page Page name or ID
	 * @param array $params Additional parameters for parse module
	 * @return string HTML of the page
	 */
	public function getHTML( $page, array $params = [], $needle = '' ) {
		$params += [
			'page' => is_string( $page ) ? $page : null,
			'pageid' => is_int( $page ) ? $page : null,
			'prop' => 'text',
		];
		$html = $this->parse( $params, 'text' );
		return $html;
	}

	/**
	 * Create a page
	 * @param string $page Name of the new page
	 * @param string $text Content of the new page
	 * @param array $params Additional parameters for edit module
	 */
	public function create( string $title, string $text, array $params = [], $needle = '' ) {
		$params += [
			'createonly' => true,
			'recreate' => true,
			'text' => $text,
		];
		$response = $this->edit( $title, $params, $needle );
		return $response;
	}

	/**
	 * Prepend wikitext to a page
	 * @param string|int $page Name or ID of the page to edit
	 * @param string $text Text to prepend
	 * @param array $params Additional parameters for edit module
	 */
	public function prepend( $page, string $text, array $params = [], $needle = '' ) {
		$params += [
			'prependtext' => $text,
		];
		$response = $this->edit( $page, $params, $needle );
		return $response;
	}

	/**
	 * Append wikitext to a page
	 * @param string|int $page Name or ID of the page to edit
	 * @param string $text Text to append
	 * @param array $params Additional parameters for the edit module
	 */
	public function append( $page, string $text, array $params = [], $needle = '' ) {
		$params += [
			'appendtext' => $text,
		];
		$response = $this->edit( $page, $params, $needle );
		return $response;
	}

	/**
	 * Get the categories of a page
	 * @param string|int $page Name or ID of the page
	 * @param array $params Additional parameters for the query module
	 * @return string[] Indexed array of categories of the page (with the "Category:" prefix).
	 */
	public function getCategories( $page, array $params = [], $needle = '' ) {
		$params += [
			'titles' => is_string( $page ) ? $page : null,
			'pageids' => is_int( $page ) ? $page : null,
			'prop' => 'categories',
			'cllimit' => 'max',
		];
		$pages = $this->query( $params, 'pages' );
		if ( $pages ) {
			$categories = $this->find( 'categories', $pages );
		}
		if ( $categories ) {
			$titles = $this->find( 'title', $categories );
		}
		if ( $titles ) {
			return $titles;
		}
		return [];
	}

	/**
	 * Get the basic info of a page
	 * @see https://en.wikipedia.org/w/api.php?action=query&formatversion=2&prop=info&titles=Science
	 * @param string|int $page Name or ID of the page
	 * @return array Requested page info
	 */
	public function getInfo( $page ) {
		$params = [
			'titles' => is_string( $page ) ? $page : null,
			'pageids' => is_int( $page ) ? $page : null,
			'prop' => 'info',
		];
		$info = $this->query( $params, 'pages' );
		$info = $response[0];
		return $info;
	}

	/**
	 * Get the general info of the site
	 * @see https://en.wikipedia.org/w/api.php?action=query&formatversion=2&meta=siteinfo
	 * @return array Requested site info
	 */
	public function getSiteInfo() {
		$params = [
			'meta' => 'siteinfo',
		];
		$info = $this->query( $params, 'general' );
		return $info;
	}
}