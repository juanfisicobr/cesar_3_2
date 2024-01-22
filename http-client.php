<?php

/*
  Version 0.9, 6th April 2003 - Simon Willison ( http://simon.incutio.com/ )
  Manual: http://scripts.incutio.com/httpclient/

  Grabbing an HTML page (static method):

  $pageContents = http_client::quick_get('http://example.com/');


  Posting a form and grabbing the response (static method):

  $pageContents = http_client::quick_post('http://example.com/someForm', array(
  'name' => 'Some Name',
  'email' => 'email@example.com'
  ));


  The static methods are easy to use, but seriously limit the functionality of the class as you
  cannot access returned headers or use facilities such as cookies or authentication. A simple
  GET request using the class:

  $client = new http_client('example.com');
  if (!$client->get('/')) {
  die('An error occurred: '.$client->get_error());
  }
  $pageContents = $client->get_content();


  A GET request with debugging turned on:

  $client = new http_client('example.com');
  $client->set_debug(true);
  if (!$client->get('/')) {
  die('An error occurred: '.$client->get_error());
  }
  $pageContents = $client->get_content();


  A GET request demonstrating automatic redirection:

  $client = new http_client('www.amazon.com');
  $client->set_debug(true);
  if (!$client->get('/')) {
  die('An error occurred: '.$client->get_error());
  }
  $pageContents = $client->get_content();


  Check to see if a page exists:

  $client = new http_client('example.com');
  $client->set_debug(true);
  if (!$client->get('/thispagedoesnotexist')) {
  die('An error occurred: '.$client->get_error());
  }
  if ($client->getStatus() == '404') {
  echo 'Page does not exist!';
  }
  $pageContents = $client->get_content();


  Fake the User Agent string:

  $client = new http_client('example.com');
  $client->set_debug(true);
  $client->set_user_agent('Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.3a) Gecko/20021207');
  if (!$client->get('/')) {
  die('An error occurred: '.$client->get_error());
  }
  $pageContents = $client->get_content();


  Log in to a site via a login form, then request a page:

  In this example, it is assumed that correctly logging in will result in the server sending a
  sesssion cookie of some sort. This cookie is resent by the http_client class automatically, so
  a request to a page that requires users to be logged in will now work.

  $client = new http_client('example.com');
  $client->post('/login.php', array(
  'username' => 'Simon',
  'password' => 'ducks'
  ));
  if (!$client->get('/private.php')) {
  die('An error occurred: '.$client->get_error());
  }
  $pageContents = $client->get_content();


  Using HTTP authorisation:

  Note that the class uses the American spelling 'authorization' to fit with the HTTP specification.

  $client = new http_client('example.com');
  $client->set_authorization('Username', 'Password');
  if (!$client->get('/')) {
  die('An error occurred: '.$client->get_error());
  }
  $pageContents = $client->get_content();


  Print out the headers from a response:

  $client = new http_client('example.com');
  if (!$client->get('/')) {
  die('An error occurred: '.$client->get_error());
  }
  print_r($client->get_headers());


  Setting the maximum number of redirects:

  $client = new http_client('www.amazon.com');
  $client->set_debug(true);
  $client->set_max_redirects(3);
  $client->get('/');
 */

class http_client {

	// Request vars
	var $host;
	var $port;
	var $path;
	var $method;
	var $postdata = '';
	var $cookies = array();
	var $referer;
	var $accept = 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*';
	var $accept_encoding = 'gzip';
	var $accept_language = 'en-us';
	var $user_agent = 'Incutio http_client v0.9';
	
	// Options
	var $timeout = 20;
	var $use_gzip = true;
	var $persist_cookies = true;
	// If true, received cookies are placed in the $this->cookies array ready for the next request
	// Note: This currently ignores the cookie path (and time) completely. Time is not important,
	//       but path could possibly lead to security problems.

	var $persist_referers = true;
	// For each request, sends path of last request as referer

	var $debug = false;
	var $handle_redirects = true;
	// Auaomtically redirect if Location or URI header is found

	var $max_redirects = 5;
	var $headers_only = false;
	// If true, stops receiving once headers have been read.
	// Basic authorization variables

	var $username;
	var $password;

	// Response vars
	var $status;
	var $headers = array();
	var $content = '';
	var $errormsg;

	// Tracker variables
	var $redirect_count = 0;
	var $cookie_host = '';
	function http_client($host, $port=80) {
		$this->host = $host;
		$this->port = $port;
	}

	function get($path, $data = false) {
		$this->path = $path;
		$this->method = 'GET';
		if ($data) {
			$this->path .= '?' . $this->build_query_string($data);
		}
		return $this->do_request();
	}

	function post($path, $data) {
		$this->path = $path;
		$this->method = 'POST';
		$this->postdata = $this->build_query_string($data);
		return $this->do_request();
	}

	function build_query_string($data) {
		$querystring = '';
		if (is_array($data)) {
			// Change data in to postable data
			foreach ($data as $key => $val) {
				if (is_array($val)) {
					foreach ($val as $val2) {
						$querystring .= urlencode($key) . '=' . urlencode($val2) . '&';
					}
				} else {
					$querystring .= urlencode($key) . '=' . urlencode($val) . '&';
				}
			}
			$querystring = substr($querystring, 0, -1); // Eliminate unnecessary &
		} else {
			$querystring = $data;
		}
		return $querystring;
	}

	function do_request() {
		// Performs the actual HTTP request, returning true or false depending on outcome
		if (!$fp = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout)) {
			// Set error message
			switch ($errno) {
				case -3:
					$this->errormsg = 'Socket creation failed (-3)';
				case -4:
					$this->errormsg = 'DNS lookup failure (-4)';
				case -5:
					$this->errormsg = 'Connection refused or timed out (-5)';
				default:
					$this->errormsg = 'Connection failed (' . $errno . ')';
					$this->errormsg .= ' ' . $errstr;
					$this->debug($this->errormsg);
			}
			return false;
		}

		socket_set_timeout($fp, $this->timeout);
		$request = $this->build_request();
		$this->debug('Request', $request);
		fwrite($fp, $request);
		
		// Reset all the variables that should not persist between requests
		$this->headers = array();
		$this->content = '';
		$this->errormsg = '';

		// Set a couple of flags
		$inHeaders = true;
		$atStart = true;

		// Now start reading back the response
		while (!feof($fp)) {
			$line = fgets($fp, 4096);
			if ($atStart) {
				// Deal with first line of returned data
				$atStart = false;
				if (!preg_match('/HTTP\/(\\d\\.\\d)\\s*(\\d+)\\s*(.*)/', $line, $m)) {
					$this->errormsg = "Status code line invalid: " . htmlentities($line);
					$this->debug($this->errormsg);
					return false;
				}
				$http_version = $m[1]; // not used
				$this->status = $m[2];
				$status_string = $m[3]; // not used
				$this->debug(trim($line));
				continue;
			}
			if ($inHeaders) {
				if (trim($line) == '') {
					$inHeaders = false;
					$this->debug('Received Headers', $this->headers);
					if ($this->headers_only) {
						break; // Skip the rest of the input
					}
					continue;
				}
				if (!preg_match('/([^:]+):\\s*(.*)/', $line, $m)) {
					// Skip to the next header
					continue;
				}
				$key = strtolower(trim($m[1]));
				$val = trim($m[2]);
				// Deal with the possibility of multiple headers of same name
				if (isset($this->headers[$key])) {
					if (is_array($this->headers[$key])) {
						$this->headers[$key][] = $val;
					} else {
						$this->headers[$key] = array($this->headers[$key], $val);
					}
				} else {
					$this->headers[$key] = $val;
				}
				continue;
			}
			// We're not in the headers, so append the line to the contents
			$this->content .= $line;
		}
		fclose($fp);

		// If data is compressed, uncompress it
		if (isset($this->headers['content-encoding']) && $this->headers['content-encoding'] == 'gzip') {
			$this->debug('Content is gzip encoded, unzipping it');
			$this->content = substr($this->content, 10); // See http://www.php.net/manual/en/function.gzencode.php
			$this->content = gzinflate($this->content);
		}

		// If $persist_cookies, deal with any cookies
		if ($this->persist_cookies && isset($this->headers['set-cookie']) && $this->host == $this->cookie_host) {
			$cookies = $this->headers['set-cookie'];
			if (!is_array($cookies)) {
				$cookies = array($cookies);
			}
			foreach ($cookies as $cookie) {
				if (preg_match('/([^=]+)=([^;]+);/', $cookie, $m)) {
					$this->cookies[$m[1]] = $m[2];
				}
			}
			// Record domain of cookies for security reasons
			$this->cookie_host = $this->host;
		}

		// If $persist_referers, set the referer ready for the next request
		if ($this->persist_referers) {
			$this->debug('Persisting referer: ' . $this->get_request_url());
			$this->referer = $this->get_request_url();
		}

		// Finally, if handle_redirects and a redirect is sent, do that
		if ($this->handle_redirects) {
			if (++$this->redirect_count >= $this->max_redirects) {
				$this->errormsg = 'Number of redirects exceeded maximum (' . $this->max_redirects . ')';
				$this->debug($this->errormsg);
				$this->redirect_count = 0;
				return false;
			}
			$location = isset($this->headers['location']) ? $this->headers['location'] : '';
			$uri = isset($this->headers['uri']) ? $this->headers['uri'] : '';
			if ($location || $uri) {
				$url = parse_url($location . $uri);
				// This will FAIL if redirect is to a different site
				return $this->get($url['path']);
			}
		}
		return true;
	}
	
	function build_request() {
		$headers = array();
		$headers[] = "{$this->method} {$this->path} HTTP/1.0"; // Using 1.1 leads to all manner of problems, such as "chunked" encoding
		$headers[] = "Host: {$this->host}";
		$headers[] = "User-Agent: {$this->user_agent}";
		$headers[] = "Accept: {$this->accept}";
		if ($this->use_gzip) {
			$headers[] = "Accept-encoding: {$this->accept_encoding}";
		}

		$headers[] = "Accept-language: {$this->accept_language}";
		if ($this->referer) {
			$headers[] = "Referer: {$this->referer}";
		}

		// Cookies
		if ($this->cookies) {
			$cookie = 'Cookie: ';
			foreach ($this->cookies as $key => $value) {
				$cookie .= "$key=$value; ";
			}
			$headers[] = $cookie;
		}

		// Basic authentication
		if ($this->username && $this->password) {
			$headers[] = 'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password);
		}
		
		// If this is a POST, set the content type and length
		if ($this->postdata) {
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
			$headers[] = 'Content-Length: ' . strlen($this->postdata);
		}
		$request = implode("\r\n", $headers) . "\r\n\r\n" . $this->postdata;
		return $request;
	}

	function get_status() {
		return $this->status;
	}

	function get_content() {
		return $this->content;
	}

	function get_headers() {
		return $this->headers;
	}

	function get_header($header) {
		$header = strtolower($header);
		if (isset($this->headers[$header])) {
			return $this->headers[$header];
		} else {
			return false;
		}
	}

	function get_error() {
		return $this->errormsg;
	}

	function get_cookies() {
		return $this->cookies;
	}

	function get_request_url() {
		$url = 'http://' . $this->host;
		if ($this->port != 80) {
			$url .= ':' . $this->port;
		}
		$url .= $this->path;
		return $url;
	}

	// Setter methods
	function set_user_agent($string) {
		$this->user_agent = $string;
	}

	function set_authorization($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}

	function set_cookies($array) {
		$this->cookies = $array;
	}

	// Option setting methods
	function use_gzip($boolean) {
		$this->use_gzip = $boolean;
	}

	function set_persist_cookies($boolean) {
		$this->persist_cookies = $boolean;
	}
	
	function set_persist_referers($boolean) {
		$this->persist_referers = $boolean;
	}

	function set_handle_redirects($boolean) {
		$this->handle_redirects = $boolean;
	}

	function set_max_redirects($num) {
		$this->max_redirects = $num;
	}

	function set_headers_only($boolean) {
		$this->headers_only = $boolean;
	}

	function set_debug($boolean) {
		$this->debug = $boolean;
	}

	// "Quick" static methods
	function quick_get($url) {
		$bits = parse_url($url);
		$host = $bits['host'];
		$port = isset($bits['port']) ? $bits['port'] : 80;
		$path = isset($bits['path']) ? $bits['path'] : '/';
		if (isset($bits['query'])) {
			$path .= '?' . $bits['query'];
		}
		$client = new http_client($host, $port);
		if (!$client->get($path)) {
			return false;
		} else {
			return $client->get_content();
		}
	}
	
	function quick_post($url, $data) {
		$bits = parse_url($url);
		$host = $bits['host'];
		$port = isset($bits['port']) ? $bits['port'] : 80;
		$path = isset($bits['path']) ? $bits['path'] : '/';
		$client = new http_client($host, $port);
		if (!$client->post($path, $data)) {
			return false;
		} else {
			return $client->get_content();
		}
	}

	function debug($msg, $object = false) {
		if ($this->debug) {
			print '<div style="border: 1px solid red; padding: 0.5em; margin: 0.5em;"><strong>http_client Debug:</strong> ' . $msg;
			if ($object) {
				ob_start();
				print_r($object);
				$content = htmlentities(ob_get_contents());
				ob_end_clean();
				print '<pre>' . $content . '</pre>';
			}
			print '</div>';
		}
	}
}
?>