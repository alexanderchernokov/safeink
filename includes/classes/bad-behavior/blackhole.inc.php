<?php if (!defined('BB2_CORE')) die('I said no cheating!');

// Look up address on various blackhole lists.
// These should not be used for GET requests under any circumstances!
// FIXME: Note that this code is no longer in use
function bb2_blackhole($package) {
	// Can't use IPv6 addresses yet
	if (@is_ipv6($package['ip'])) return false;

	// Workaround for "MySQL server has gone away"
	bb2_db_query("SET @@session.wait_timeout = 90");

	// Only conservative lists
	$bb2_blackhole_lists = array(
		"sbl-xbl.spamhaus.org",	// All around nasties
//		"dnsbl.sorbs.net",	// Old useless data.
//		"list.dsbl.org",	// Old useless data.
//		"dnsbl.ioerror.us",	// Bad Behavior Blackhole
	);

	// Things that shouldn't be blocked, from aggregate lists
	$bb2_blackhole_exceptions = array(
		"sbl-xbl.spamhaus.org" => array("127.0.0.4"),	// CBL is problematic
		"dnsbl.sorbs.net" => array("127.0.0.10",),	// Dynamic IPs only
		"list.dsbl.org" => array(),
		"dnsbl.ioerror.us" => array(),
	);

	// Check the blackhole lists
	$ip = $package['ip'];
	$find = implode('.', array_reverse(explode('.', $ip)));
	foreach ($bb2_blackhole_lists as $dnsbl) {
		$result = gethostbynamel($find . "." . $dnsbl . ".");
		if (!empty($result)) {
			// Got a match and it isn't on the exception list
			$result = @array_diff($result, $bb2_blackhole_exceptions[$dnsbl]);
			if (!empty($result)) {
				return '136673cd';
			}
		}
	}
	return false;
}

function bb2_httpbl($settings, & $package) {
	// Can't use IPv6 addresses yet
	if (@is_ipv6($package['ip'])) return false;

	if (@!$settings['httpbl_key']) return false;

	// Workaround for "MySQL server has gone away"
	bb2_db_query("SET @@session.wait_timeout = 90");

	$find = implode('.', array_reverse(explode('.', $package['ip'])));
	$result = @gethostbynamel($settings['httpbl_key'].".${find}.dnsbl.httpbl.org.");
	if (!empty($result)) {
		$ip = explode('.', $result[0]);

		if((count($ip)==4) && ($ip[0] == 127) && ($ip[3] > 1) &&
       (!empty($settings['httpbl_maxage']) && ($ip[1] <= (int)$settings['httpbl_maxage'])) &&
       (!empty($settings['httpbl_threat']) && ($ip[2] >= (int)$settings['httpbl_threat'])))
    {
      //SD: whitelist usergroups
      global $userinfo;
      if(!empty($userinfo['userid']) && !empty($settings['httpbl_whitelisted_groups']) &&
         is_array($settings['httpbl_whitelisted_groups']) &&
         @in_array($userinfo['usergroupid'], $settings['httpbl_whitelisted_groups']))
      {
        return false;
      }
      //SD: pass code into package
      $package['httpbl_code'] = (int)$ip[3];
      if ($ip[3] & 7) {
        $package['httpbl_level'] = (int)$ip[2]; //SD344
      }
      sleep(4); //SD
			return '2b021b1f';
		}
		// Check if search engine
		if ($ip[3] == 0) {
			return 1;
		}
	}
	return false;
}
