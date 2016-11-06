<?php

/*******************************************************************************
Version: 1.1
Website: http://www.oitc.com/source/uri_reputation.html
Author: TR Shaw (tshaw at oitc.com)
Title: URI Reputation Client
Description: PHP Client Library for the URI Reputation checking
				Defaults for SURBL checking

Version 1.1 - cleaned up debug statements and a typo causing small lookup errors.
© 2009-2011 Copyright by TR Shaw and OITC
Licensed under The MIT Open Source License
Software is open source
Redistributions of files must retain the above copyright notice.
Credit must also be give if the using this software in a system/product.
*******************************************************************************/


/**
 * URIReputation Object
 *
 * This object supports all methods necessary to check
 * a URL against a URI Reputation DNS Data Base
 *
 * Usage:
 *			Support files, two-level-tlds downloaded from
 *				http://www.surbl.org/tld/two-level-tlds and
 *				three-level-tlds downloaded from
 *				http://www.surbl.org/tld/three-level-tlds,
 *				are required for correct operation. The files
 *				should be checked to be current periodically -
 *				maybe once per week.
 *
 *			include_once("uri_reputation.php");
 *
 *			$surbl = new URIReputation();
 *			if ($surbl->check_url($url_to_be_checked) === false) not_found;
 *			else found_on_one_or_more_lists;
 *
 *			or
 *
 *			include_once("uri_reputation.php");
 *
 *			$surbl = new URIReputation();
 *			switch ($surbl->check_url($url_to_be_checked)) {
 *				case "127.0.0.2":
 *					found_on_sc.surbl.org
 *					break;
 *				case "127.0.0.4":
 *					found_on_ws.surbl.org
 *					break;
 *				case "127.0.0.8":
 *					found_on_ph.surbl.org
 *					break;
 *				case "127.0.0.16":
 *					found_on_ob.surbl.org
 *					break;
 *				case "127.0.0.32":
 *					found_on_ab.surbl.org
 *					break;
 *				case "127.0.0.64":
 *					found_on_jp.surbl.org
 *					break;
 *				case false:
 *					not_found
 *					break;
 *				default:
 *					found_on_multiple_lists
 *					break;
 *			}
 *
 *			or
 *
 *			include_once("uri_reputation.php");
 *
 *			$surbl = new URIReputation();
 *			$result = ip2long($surbl->check_url($url_to_be_checked));
 *			if (($result > 0x7f000001) && ($result <= 0x7f00ffff)) {
 *			 	// URL detected on 1 or more lists
 *			 	$found_on_sc_surbl_org = ($result & 2);
 *			 	$found_on_ws_surbl_org = ($result & 4);
 *			 	$found_on_ph_surbl_org = ($result & 8);
 *			 	$found_on_ob_surbl_org = ($result & 16);
 *			 	$found_on_ab_surbl_org = ($result & 32);
 *			 	$found_on_jp_surbl_org = ($result & 64);
 *			 } else {
 *			 	// URL not found on any lists
 *			 }
 */
$GLOBALS['sd_ignore_watchdog'] = true;
if(function_exists('ini_set'))
@ini_set('auto_detect_line_endings', true);
$GLOBALS['sd_ignore_watchdog'] = false;

class URIReputation {

	private $two_level_list = array();
	private $three_level_list = array();
	private $list = ".multi.surbl.org";

	/**
	 * Constructor. Initializes object defaults
	 *
	 * @access public
	 * @param string $ip
	 * @return string	reversed dotted quad
	 */
	function __construct($two_level_tlds="two-level-tlds", $three_level_tlds="three-level-tlds") {
    if(file_exists($two_level_tlds)) $this->two_level_list = @file($two_level_tlds, FILE_IGNORE_NEW_LINES);
		if(file_exists($three_level_tlds)) $this->three_level_list = @file($three_level_tlds, FILE_IGNORE_NEW_LINES);
	}

	/**
	 * Reverses an IPv4 IP number
	 *
	 * @access private
	 * @param string $ip
	 * @return string	reversed dotted quad
	 */
	private function _reverse_ipv4($ip) {
		// Reverses an IPv4 IP
		$parts = explode(".", $ip);
		return $parts[3] . "." . $parts[2] . "." . $parts[1] . "." . $parts[0];
	}

	/**
	 * Reverses an IPv6 IP number
	 *
	 * @access private
	 * @param string $ip
	 * @return string	reversed colon ip
	 */
	private function _reverse_ipv6($ip) {
		// Reverses an IPv6 IP
		$parts = explode(":", $ip);
		$result = "";
		for ($i = count($parts) - 1; $i < 0; $i--) {
			$result .= ":" . $parts[$i];
		}
		return substr($result, 1);
	}

	/**
	 * Is host on the level 3 list?
	 *
	 * @access private
	 * @param string $host
	 * @return boolean
	 */
	private function _islevel3host($host) {
		$host = substr($host, strpos($host, ".") + 1);
		return in_array($host, $this->three_level_list);
	}

	/**
	 * Is host on the level 2 list?
	 *
	 * @access private
	 * @param string $host
	 * @return boolean
	 */
	private function _islevel2host($host) {
		$host = substr($host, strpos($host, ".") + 1);
		return in_array($host, $this->two_level_list);
	}

	/**
	 * Sets the two and three level lists file names.
	 * Override default of two-level-tlds and three-level-tlds
	 *
	 * @access public
	 * @param string $two_level_tlds
	 * @param string $three_level_tlds
	 */
	public function set_multilevel_files($two_level_tlds, $three_level_tlds) {
		$this->two_level_list = @file($two_level_tlds, FILE_IGNORE_NEW_LINES);
		$this->three_level_list = @file($three_level_tlds, FILE_IGNORE_NEW_LINES);
	}

	/**
	 * Checks the two level list.
	 *
	 * @access public
	 * @return two level list array or false
	 */
	public function get_two_level_list() {
		return $this->two_level_list;
	}

	/**
	 * Checks the three level list.
	 *
	 * @access public
	 * @return three level list array or false
	 */
	public function get_three_level_list() {
		return $this->three_level_list;
	}

	/**
	 * Sets which uri reputation list to query.
	 * Override default of multi.surbl.org
	 *
	 * @access public
	 * @param string $list
	 */
	public function set_list($list) {
		$this->list = "." . $list;
	}

	/**
	 * Tests the selected uri reputation list using IPv4
	 *
	 * @access public
	 * @return reputation string or false
	 */
	public function test_with_ip() {
		$query_string = "2.0.0.127" . $this->list;
		if (($result = gethostbyname($query_string)) == $query_string) return false;
		return $result;
	}

	/**
	 * Tests the selected uri reputation list using IPv4
	 *
	 * @access public
	 * @return reputation string or false
	 */
	public function test_with_host($host = "test.surbl.org") {
		$query_string = $host . $this->list;
		if (($result = gethostbyname($query_string)) == $query_string) return false;
		return $result;
	}

	/**
	 * Query the selected uri reputation list
	 *
	 * @access public
	 * @param string $host
	 * @return reputation string or false
	 */
	public function extract_uri_reputation_data($host) {
		// Everything happens in lower case
		$host = strtolower($host);
		// Is it an IP?
		if (($ip = ip2long($host)) !== false) {
			// This approach handles the reverse of all ips decodable
			// by http://publibn.boulder.ibm.com/doc_link/en_US/a_doc_lib/libs/commtrf2/inet_addr.htm
			$query_string = $this->_reverse_ipv4(long2ip($ip)) . $this->list;
			if (($result = gethostbyname($query_string)) == $query_string) return false;
			return $result;
		// Is it an IPv6 IP?
		} else if (strpos($host, ":") !== false) {
			// Placeholder for IPv6
			$query_string = $this->_reverse_ipv6($host) . $this->list;
			// IPv6 Not yet supported.
			return false;
		} else {
			// Normal host
			$host_elements = explode('.', $host);
			//print_r($host_elements);
			$cnt = count($host_elements);
			if ($cnt >= 4) {
				// Could be a 3 level tld
				$slice_4_elements = array_slice($host_elements, -4);
				$host_4_elements = implode('.', $slice_4_elements);
				if ($this->_islevel3host($host_4_elements)) {
					// Is a 3 level tld so check it
					$query_string = $host_4_elements . $this->list;
					if (($result = gethostbyname($query_string)) == $query_string) return false;
					return $result;
				}
			}
			if ($cnt >= 3) {
				// Could be a 2 level tld
				$slice_3_elements = array_slice($host_elements, -3);
				//print_r($slice_3_elements);
				$host_3_elements = implode('.', $slice_3_elements);
				if ($this->_islevel2host($host_3_elements)) {
					// Is a 2 level tld so check it
					$query_string = $host_3_elements . $this->list;
					if (($result = gethostbyname($query_string)) == $query_string) return false;
					return $result;
				}
			}
			if ($cnt >= 2) {
				// make sure it is a real domain name
				$slice_2_elements = array_slice($host_elements, -2);
				$host_2_elements = implode('.', $slice_2_elements);
				// Is a domain so check it
				$query_string = $host_2_elements . $this->list;
				if (($result = gethostbyname($query_string)) == $query_string) return false;
				return $result;
			}
		}
		return false;
	}

	/**
	 * Checks the url against the selected uri reputation list
	 *
	 * @access public
	 * @param string $url
	 * @return reputation string or false
	 */
	public function check_url($url) {
		// Extract host_info
    if(@ip2long($url) !== false)
      $host = $url;
    else
      $host = parse_url($url, PHP_URL_HOST);
		// Get and return reputation
		return $this->extract_uri_reputation_data($host);
	}
}

?>
