<?php
	
	/* if ($_SERVER['REMOTE_ADDR'] == '') {  
    ini_set('display_errors',1);
    error_reporting(E_ALL);  
	}  */
	
	require "dbip.class.php";
	function getLocationInfoByIp($dir_ip){
    $ip_data = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$dir_ip));   
    $country = $ip_data->geoplugin_countryName;
	return $country;
	}
	
	include("global.php");

	if( ($G_user_type <> "ROOT") and ($G_user_type <> "SRSLR") ) { header("Location: logout.php"); die; }

	if( !isset($_REQUEST["username"]) )
	{
		echo("ERROR: No reseller was specified.");
		die;
	}
	
	$reseller = entry_filter($_REQUEST["username"]);
	$status_message = "";
	
	// -- check if it is a valid reseller username
	if( $G_user_type == "SRSLR" )
	{
		if( query_scalar("select username_owner from users where username = '$reseller'") <> $G_username )
		{
			echo("ERROR: Invalid reseller specified.");
			die;
		}
	}
	
	// -- update subaccount expiry
	if( isset($_REQUEST["update"]) )
	{
	  if( srv_connchk() == "C" ) {
	  
		$_REQUEST["update"] = entry_filter($_REQUEST["update"]);

		if( !isset($_REQUEST["new_expiry"]) )
		{
			echo("ERROR: No new expiry date was specified.");
			die;
		}
		$_REQUEST["new_expiry"] = entry_filter($_REQUEST["new_expiry"]);
		$expiry_timestamp = text_datetime_to_timestamp($_REQUEST["new_expiry"]);
		
			mysql_begin();
			if( db_update("accounts", array("expires" => date("Y-m-d", $expiry_timestamp)), "username = '$reseller' and account = '{$_REQUEST["update"]}'") )
			{
				$status_message = grs("RESELLER_SUBACCOUNT_EXPIRY_UPDATE_SUCCESS");
				mysql_commit();
			}
			else
			{
				$status_message = grs("RESELLER_SUBACCOUNT_EXPIRY_UPDATE_FAILURE");
				mysql_rollback();
			}
		
       } else {
	  
		$_REQUEST["update"] = entry_filter($_REQUEST["update"]);

		if( !isset($_REQUEST["new_expiry"]) )
		{
			echo("ERROR: No new expiry date was specified.");
			die;
		}
		$_REQUEST["new_expiry"] = entry_filter($_REQUEST["new_expiry"]);
		$expiry_timestamp = text_datetime_to_timestamp($_REQUEST["new_expiry"]);
		
		$effective_account = get_effective_account($_REQUEST["update"], query_scalar("select accounts_prefix from users where username = '$reseller'"));
		if( server_update_account_expiry($effective_account, $expiry_timestamp, $server_status_description) )
		{
			mysql_begin();
			if( db_update("accounts", array("expires" => date("Y-m-d", $expiry_timestamp)), "username = '$reseller' and account = '{$_REQUEST["update"]}'") )
			{
				$status_message = grs("RESELLER_SUBACCOUNT_EXPIRY_UPDATE_SUCCESS");
				mysql_commit();
			}
			else
			{
				$status_message = grs("RESELLER_SUBACCOUNT_EXPIRY_UPDATE_FAILURE");
				mysql_rollback();
			}
		}
		else $status_message = "SERVER: ".$server_status_description;
	  } 
	}
	
	// -- update subaccount maxlogin
	if( isset($_REQUEST["updateM"]) )
	{
	  if( srv_connchk() == "C" ) {
	  
		$_REQUEST["updateM"] = entry_filter($_REQUEST["updateM"]);

		if( !isset($_REQUEST["new_maxlogin"]) )
		{
			echo("ERROR: No new expiry date was specified.");
			die;
		}
		$_REQUEST["new_maxlogin"] = entry_filter($_REQUEST["new_maxlogin"]);
		$expiry_timestamp = text_datetime_to_timestamp($_REQUEST["new_maxlogin"]);
		
			mysql_begin();
			if( db_update("accounts", array("maxlogin" => $_REQUEST["new_maxlogin"]), "username = '$reseller' and account = '{$_REQUEST["updateM"]}'") )
			{
				$status_message = grs("RESELLER_SUBACCOUNT_EXPIRY_UPDATE_SUCCESS");
				mysql_commit();
			}
			else
			{
				$status_message = grs("RESELLER_SUBACCOUNT_EXPIRY_UPDATE_FAILURE");
				mysql_rollback();
			}
		
       } else {
	  
		$_REQUEST["updateM"] = entry_filter($_REQUEST["updateM"]);

		if( !isset($_REQUEST["new_maxlogin"]) )
		{
			echo("ERROR: No new expiry date was specified.");
			die;
		}
		$_REQUEST["new_maxlogin"] = entry_filter($_REQUEST["new_maxlogin"]);
		$max_login_count = entry_filter($_REQUEST["new_maxlogin"]);
		
		$effective_account = get_effective_account($_REQUEST["updateM"], query_scalar("select accounts_prefix from users where username = '$reseller'"));
		if( server_update_account_max_login_count($effective_account, $max_login_count, $server_status_description) )
		{
			mysql_begin();
			if( db_update("accounts", array("maxlogin" => $_REQUEST["new_maxlogin"]), "username = '$reseller' and account = '{$_REQUEST["updateM"]}'") )
			{
				$status_message = grs("RESELLER_SUBACCOUNT_EXPIRY_UPDATE_SUCCESS");
				mysql_commit();
			}
			else
			{
				$status_message = grs("RESELLER_SUBACCOUNT_EXPIRY_UPDATE_FAILURE");
				mysql_rollback();
			}
		}
		else $status_message = "SERVER: ".$server_status_description;
	  } 
	}
	
	// -- delete subaccount
	if( isset($_REQUEST["delete"]) )
	{
	  if( srv_connchk() == "C" ) {
	  
		$_REQUEST["delete"] = entry_filter($_REQUEST["delete"]);

		mysql_begin();
		if( db_delete("transactions", "type = 'DBIT' and username = '$reseller' and account = '{$_REQUEST["delete"]}'") )
		{
			if( db_delete("accounts", "username = '$reseller' and account = '{$_REQUEST["delete"]}'") )
			{
				$status_message = grs("RESELLER_SUBACCOUNT_DELETE_SUCCESS");
				mysql_commit();
				
			}
			else
			{
				$status_message = grs("RESELLER_SUBACCOUNT_DELETE_FAILURE");
				mysql_rollback();
			}
		}
		else
		{
			$status_message = grs("RESELLER_SUBACCOUNT_DELETE_FAILURE");
			mysql_rollback();
		}
      } else {

		$_REQUEST["delete"] = entry_filter($_REQUEST["delete"]);

		mysql_begin();
		if( db_delete("transactions", "type = 'DBIT' and username = '$reseller' and account = '{$_REQUEST["delete"]}'") )
		{
			if( db_delete("accounts", "username = '$reseller' and account = '{$_REQUEST["delete"]}'") )
			{
				$status_message = grs("RESELLER_SUBACCOUNT_DELETE_SUCCESS");
				mysql_commit();
				
				$server_status_message = "";
				$effective_account = get_effective_account($_REQUEST["delete"], query_scalar("select accounts_prefix from users where username = '$reseller'"));
				if( !server_delete_account($effective_account, $server_status_message) )
					$status_message .= ". ".grs("RESELLER_SUBACCOUNT_SERVER_WASNT_DELETED").": [$server_status_message]";
			}
			else
			{
				$status_message = grs("RESELLER_SUBACCOUNT_DELETE_FAILURE");
				mysql_rollback();
			}
		}
		else
		{
			$status_message = grs("RESELLER_SUBACCOUNT_DELETE_FAILURE");
			mysql_rollback();
		}
	 }		
	}
	
	if( isset($_REQUEST["add"]) and isset($_REQUEST["add_username"]) and isset($_REQUEST["add_password"]) and
		isset($_REQUEST["add_months"]) )
	{
	  if( srv_connchk() == "C" ) {
	  
		$_REQUEST["add_username"] = entry_filter($_REQUEST["add_username"]);
		$_REQUEST["add_password"] = entry_filter($_REQUEST["add_password"]);
		
		$current_date = date("Ymd", time());
		$coverage_start = mktime(0, 0, 0,
			substr($current_date, 4, 2),
			substr($current_date, 6, 2),
			substr($current_date, 0, 4));

		$coverage_end = $coverage_start + ((3600 * 24 * $G_days_per_month) * (int)$_REQUEST["add_months"]);

		if( (get_credit_balance( $reseller ) - (int)$_REQUEST["add_months"]) >= 0 )
		{
			// -- check if account already exists
			if( (int)query_scalar("select count(*) from accounts where upper(account) = '".strtoupper($_REQUEST["add_username"])."'") == 0 )
			{
					// -- add record to the database
					mysql_begin();
					if( db_insert("accounts", array(
						"username" => $reseller,
						"account" => $_REQUEST["add_username"],
						"password" => $_REQUEST["add_password"],
						"maxlogin" => 1,
						"expires" => date("Y-m-d", $coverage_end)) ) )
					{
						if( db_insert("transactions", array(
							"username" => $reseller,
							"transaction" => (int)query_scalar("select max(transaction) + 1 from transactions where username = '$reseller'"),
							"type" => "DBIT",
							"periods" => (int)$_REQUEST["add_months"],
							"account" => $_REQUEST["add_username"],
							"timestamp" => date("Y-m-d H:i:s"),
							"coverage_start" => date("Y-m-d", $coverage_start),
							"coverage_end" => date("Y-m-d", $coverage_end)) ) )
						{
							mysql_commit();
							header("Location: sr-reseller-subaccounts.php?username=$reseller");
							die;
						}
						else
						{
							$status_message_add = grs("HOME_ADD_FAILED");
							mysql_rollback();
						}
					}
					else
					{
						$status_message_add = grs("HOME_ADD_FAILED");
						mysql_rollback();
					}
			}
			else $status_message_add = grs("HOME_ADD_ALREADY_EXISTS");
		}
		else $status_message_add = grs("HOME_ADD_INSUFFICIENT_CREDIT");
		
	   } else {
	   
		$_REQUEST["add_username"] = entry_filter($_REQUEST["add_username"]);
		$_REQUEST["add_password"] = entry_filter($_REQUEST["add_password"]);
		
		$current_date = date("Ymd", time());
		$coverage_start = mktime(0, 0, 0,
			substr($current_date, 4, 2),
			substr($current_date, 6, 2),
			substr($current_date, 0, 4));

		$coverage_end = $coverage_start + ((3600 * 24 * $G_days_per_month) * (int)$_REQUEST["add_months"]);

		if( (get_credit_balance( $reseller ) - (int)$_REQUEST["add_months"]) >= 0 )
		{
			// -- check if account already exists
			if( (int)query_scalar("select count(*) from accounts where upper(account) = '".strtoupper($_REQUEST["add_username"])."'") == 0 )
			{
				// -- add server user
				$server_status_description = "";
				$effective_account = get_effective_account($_REQUEST["add_username"], query_scalar("select accounts_prefix from users where username = '$reseller'"));
				if( server_add_account($effective_account, $_REQUEST["add_password"], $coverage_end, TRUE,
					sprintf($G_fslb_global_account_comment, $reseller), $server_status_description) )
				{
					// -- add record to the database
					mysql_begin();
					if( db_insert("accounts", array(
						"username" => $reseller,
						"account" => $_REQUEST["add_username"],
						"password" => $_REQUEST["add_password"],
						"maxlogin" => 1,
						"expires" => date("Y-m-d", $coverage_end)) ) )
					{
						if( db_insert("transactions", array(
							"username" => $reseller,
							"transaction" => (int)query_scalar("select max(transaction) + 1 from transactions where username = '$reseller'"),
							"type" => "DBIT",
							"periods" => (int)$_REQUEST["add_months"],
							"account" => $_REQUEST["add_username"],
							"timestamp" => date("Y-m-d H:i:s"),
							"coverage_start" => date("Y-m-d", $coverage_start),
							"coverage_end" => date("Y-m-d", $coverage_end)) ) )
						{
							mysql_commit();
							header("Location: sr-reseller-subaccounts.php?username=$reseller");
							die;
						}
						else
						{
							$status_message_add = grs("HOME_ADD_FAILED");
							mysql_rollback();
						}
					}
					else
					{
						$status_message_add = grs("HOME_ADD_FAILED");
						mysql_rollback();
					}
				}
				else $status_message_add = "SERVER: ".$server_status_description;
			}
			else $status_message_add = grs("HOME_ADD_ALREADY_EXISTS");
		}
		else $status_message_add = grs("HOME_ADD_INSUFFICIENT_CREDIT");
	  }	
	}

	$rs_reseller = mysql_query("select name, status, accounts_prefix from users where username = '$reseller'");
	$rw_reseller = mysql_fetch_assoc( $rs_reseller );
	if( !$rw_reseller )
	{
		echo("ERROR: Invalid reseller was specified.");
		die;
	}

	// -- get reseller sub-accounts
	$rs_accounts = mysql_query("select account, password, unix_timestamp(expires) as expires, maxlogin, comments, ".
		"(select count(*) from transactions t where t.username = a.username and t.account = a.account) as ".
		"transaction_count from accounts a where a.username = '$reseller' order by a.account");
		
	if( srv_connchk() == "C" ) {
	// -- connect for csp
    $sql = mysql_query("SELECT * FROM settings WHERE id='1'");
    $line=mysql_fetch_array($sql);
	if ($line["srv_from"] == "1" ) {
		$showuseronline = "1";
	} else {
		$showuseronline = "0";
	}
		if ($showuseronline == "1") {
			$i = 0;
			$srv_ip = $line["srv_ip"];
			$srv_port = $line["srv_port"];
			$srv_user = $line["srv_user"];
			$srv_pass = $line["srv_pass"];
			$srv_protocol = $line["srv_protocol"];
			$xml = simplexml_load_file($srv_protocol."://".$srv_user.":".$srv_pass."@".$srv_ip.":".$srv_port."/xmlHandler?command=proxy-users");
			$proxyusers = "proxy-users";
				foreach($xml->$proxyusers->user as $users) {
					$y = 0;
					$activesession = 0;
					$userstate = "0";
					$xmlusername = "";
					foreach($xml->$proxyusers->user[$i]->session as $active) {
						if ((string)$xml->$proxyusers->user[$i]->session[$y]->attributes()->active == "true") {
							$userstate = "1";
							$activesession = $y;
						}
						$y++;
					}
					$activeusers[] = (string)$xml->$proxyusers->user[$i]->attributes()->name;
					$activeusersstate[(string)$xml->$proxyusers->user[$i]->attributes()->name] = $userstate;
					$xmlusername = (string)$xml->$proxyusers->user[$i]->attributes()->name;
					$userinfo[$xmlusername."-host"] = (string)$xml->$proxyusers->user[$i]->session[$activesession]->attributes()->host;
					$userinfo[$xmlusername."-duration"] = (string)$xml->$proxyusers->user[$i]->session[$activesession]->attributes()->duration;
						if ($userstate == "1") {
							$userinfo[$xmlusername."-servid"] = @(string)$xml->$proxyusers->user[$i]->session[$activesession]->service->attributes()->id;
							$userinfo[$xmlusername."-servcdata"] = @(string)$xml->$proxyusers->user[$i]->session[$activesession]->service->attributes()->cdata;
							$userinfo[$xmlusername."-servname"] = @(string)$xml->$proxyusers->user[$i]->session[$activesession]->service->attributes()->name;
						} else {
							$userinfo[$xmlusername."-servid"] = "";
							$userinfo[$xmlusername."-servcdata"] = "";
							$userinfo[$xmlusername."-servname"] = "";
						}
					$i++;
				}
		}
    } else {
	// -- connect for fslb
    $sql = mysql_query("SELECT * FROM settings WHERE id='1'");
    $line=mysql_fetch_array($sql);
	if ($line["srv_from"] == "1" ) {
		$showuseronline = "1";
	} else {
		$showuseronline = "0";
	}
		if ($showuseronline == "1") {
			$i = 0;
			$cspsrv_ip = $line["srv_ip"];
			$cspsrv_port = $line["srv_port"];
			$cspsrv_user = $line["srv_user"];
			$cspsrv_pass = $line["srv_pass"];
			$cspsrv_protocol = $line["srv_protocol"];
			$xml = simplexml_load_file($cspsrv_protocol."://".$cspsrv_user.":".$cspsrv_pass."@".$cspsrv_ip.":".$cspsrv_port."/users-active.xml?server");
			$proxyusers = "active-users";
				foreach($xml->$proxyusers->user as $users) {
					$y = 0;
					$activesession = 0;
					$userstate = "0";
					$xmlusername = "";
					$activeusers[] = (string)$xml->$proxyusers->user[$i]->attributes()->name;
					$activeusersstate[(string)$xml->$proxyusers->user[$i]->attributes()->name] = $userstate;
					$xmlusername = (string)$xml->$proxyusers->user[$i]->attributes()->name;
					$userinfo[$xmlusername."-ipaddress"] = (string)$xml->$proxyusers->user[$i]->attributes()->ipaddress;
					$userinfo[$xmlusername."-sid"] = (string)$xml->$proxyusers->user[$i]->attributes()->sid;
					$i++;
				}
		}
	}	
?>
<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="pragma" content="no-cache">
<link href="themes/<?php echo($G_theme); ?>/style.css" type="text/css" rel="stylesheet">
<title><?php grse("APP_NAME"); ?></title>
<script type="text/javascript" language="javascript"><!--
function save_expiry( account )
{
	root_form = document.forms[0];

	if( !confirm('<?php grse("RESELLER_SUBACCOUNT_EXPIRY_DATE_CHANGE_CONFIRM"); ?>.') ) return;

	save_expiry_url = 'sr-reseller-subaccounts.php?scrollLeft=' +
		escape(root_form.scrollLeft.value) + '&scrollTop=' +
		escape(root_form.scrollTop.value) + '&__postback=1&username=<?php echo( $reseller ); ?>' +
		'&update=' + account + '&new_expiry=' + root_form.new_expiry.value;
		
	location.href = save_expiry_url;
}

function save_maxlogin( account )
{
	root_form = document.forms[0];

	if( !confirm('<?php grse("RESELLER_SUBACCOUNT_MAX_LOGIN_CHANGE_CONFIRM"); ?>.') ) return;

	save_maxlogin_url = 'sr-reseller-subaccounts.php?scrollLeft=' +
		escape(root_form.scrollLeft.value) + '&scrollTop=' +
		escape(root_form.scrollTop.value) + '&__postback=1&username=<?php echo( $reseller ); ?>' +
		'&updateM=' + account + '&new_maxlogin=' + root_form.new_maxlogin.value;
		
	location.href = save_maxlogin_url;
}

function delete_subaccount( account )
{
	root_form = document.forms[0];

	if( !confirm('<?php grse("RESELLER_SUBACCOUNT_DELETE_CONFIRM"); ?>.') ) return;

	delete_subaccount_url= 'sr-reseller-subaccounts.php?scrollLeft=' +
		escape(root_form.scrollLeft.value) + '&scrollTop=' +
		escape(root_form.scrollTop.value) + '&__postback=1&username=<?php echo( $reseller ); ?>' +
		'&delete=' + account;

	location.href = delete_subaccount_url;
}

function add_account()
{
	root_form = document.forms[0];

	if( root_form.add_username.value != '' && root_form.add_password.value != '' && root_form.add_months.value != '' )
	{
		if( !confirm('<?php grse("RESELLER_SUBACCOUNT_ADD_CONFIRM"); ?>.') ) return;

		add_account_url = 'sr-reseller-subaccounts.php?scrollLeft=' +
			escape(root_form.scrollLeft.value) + '&scrollTop=' +
			escape(root_form.scrollTop.value) + '&__postback=1&username=<?php echo( $reseller ); ?>' +
			'&add=1&add_username=' + escape(root_form.add_username.value) + '&add_password=' +
			escape(root_form.add_password.value) + '&add_months=' + escape(root_form.add_months.value);
		
		location.href = add_account_url;
	}
	else alert('<?php grse("HOME_ADD_OBLIGATORY"); ?>.');
}
// -->
</script>
</head>

<body>
<form name="root" action="sr-reseller-subaccounts.php" method="post">
	<input type="hidden" name="username" value="<?php echo($reseller); ?>">
	<table border="0" width="100%" id="tblSection_1">
		<tr>
			<td colspan="7" class="sectionTitle"><?php grse("RESELLER_EDIT_INFO"); ?></td>
		</tr>
		<tr>
			<td width="25"></td>
			<td class="fieldTitle" width="160"><?php grse("RESELLER_EDIT_RESELLER") ?>:</td>
			<td><?php echo( $reseller ); ?></td>
		</tr>
		<tr>
			<td width="25"></td>
			<td class="fieldTitle" width="160"><?php grse("RESELLER_EDIT_NAME"); ?>:</td>
			<td><?php echo( $rw_reseller["name"]); ?></td>
		</tr>
		<tr>
			<td width="25"></td>
			<td class="fieldTitle" width="160"><?php grse("RESELLER_EDIT_STATUS"); ?>:</td>
			<td><?php
				switch( $rw_reseller["status"] )
				{
					case "A":
						grse("RESELLER_EDIT_ACTIVE");
						break;

					case "S":
						grse("RESELLER_EDIT_SUSPENDED");
						break;

					default:
						grse("RESELLER_EDIT_UNKNOWN");
						break;
				}
			?></td>
		</tr>
		<tr>
			<td width="25"></td>
			<td class="fieldTitle" width="160"><?php grse("RESELLER_EDIT_CREDIT_BALANCE"); ?>:</td>
			<td><?php echo( get_credit_balance( $reseller ) ); ?></td>
		</tr>
		<tr>
			<td width="25"></td>
			<td class="fieldTitle" width="160"><hr></td>
			<td><hr></td>
		</tr>
		<tr>
			<td width="25"></td>
			<td class="fieldTitle" width="160"></td>
			<td class="errorMessage"><?php if( $status_message <> "" ) echo( $status_message )."."; ?></td>
		</tr>
	</table>
	<br>
	<table border="0" width="100%" id="tblSection_2">
		<tr>
			<td class="sectionTitle"><?php grse("RESELLER_SUBACCOUNT_LIST"); ?></td>
		</tr>
		<tr>
			<td>
				<table border="0" width="100%">
					<tr class="listHeader">
						<td width="9%"><?php grse("HOME_LIST_SUBACCOUNT"); ?></td>
						<td width="9%"><?php grse("HOME_LIST_PASSWORD"); ?></td>
						<td width="9%"><?php grse("HOME_LIST_COMMENTS"); ?></td>
						<td width="13%"><?php grse("HOME_LIST_EXPIRY_DATE"); ?></td>
						<td width="12%"><?php grse("RESELLER_SUBACCOUNT_MAX_LOGIN"); ?></td>
						<td width="11%"><?php grse("RESELLER_SUBACCOUNT_TRANSACTION_COUNT"); ?></td>
						<td width="8%">País</td>
						<td width="26%"></td>
						<td width="3%"></td>
					</tr>
<?php
	if( mysql_num_rows( $rs_accounts ) )
	{
		while( ($rw_account = mysql_fetch_assoc( $rs_accounts )) )
		{
?>
					<tr>
						<td width="9%"><?php echo( get_effective_account($rw_account["account"], $rw_reseller["accounts_prefix"]) ); ?></td>
						<td width="9%"><?php echo($rw_account["password"]); ?></td>
						<td width="9%"><?php echo($rw_account["comments"]); ?></td>
						<td width="13%"><?php
							if( isset($_REQUEST["update_expiry"]) )
							{
								if($_REQUEST["update_expiry"] == $rw_account["account"])
									$edit_mode = TRUE;
								else
									$edit_mode = FALSE;
							}
							else $edit_mode = FALSE;
							
							if( $edit_mode )
							{
								echo("<input type=\"text\" name=\"new_expiry\" size=\"15\" maxlength=\"10\" value=\""._date_format($rw_account["expires"])."\">");
								echo(" [<a href=\"javascript:save_expiry('{$rw_account["account"]}');\">".grs("RESELLER_SUBACCOUNT_EXPIRY_DATE_SAVE")."</a>]");
							}
							else
							{
								echo(_date_format($rw_account["expires"]));
								if( $G_user_type == "ROOT" ) {
								echo(" [<a href=\"sr-reseller-subaccounts.php?username=$reseller&update_expiry={$rw_account["account"]}\">".grs("RESELLER_SUBACCOUNT_EXPIRY_DATE_CHANGE")."</a>]");
								}
							}
						?></td>
						<td width="12%"><?php
							if( isset($_REQUEST["update_max_login_count"]) )
							{
								if($_REQUEST["update_max_login_count"] == $rw_account["account"])
									$edit_mode = TRUE;
								else
									$edit_mode = FALSE;
							}
							else $edit_mode = FALSE;
							
							if( $edit_mode )
							{
								echo("<input type=\"text\" name=\"new_maxlogin\" size=\"15\" maxlength=\"10\" value=\"".$rw_account["maxlogin"]."\">");
								echo(" [<a href=\"javascript:save_maxlogin('{$rw_account["account"]}');\">".grs("RESELLER_SUBACCOUNT_EXPIRY_DATE_SAVE")."</a>]");
							}
							else
							{
								echo($rw_account["maxlogin"]);
								if( $G_user_type == "ROOT" ) {
								echo(" [<a href=\"sr-reseller-subaccounts.php?username=$reseller&update_max_login_count={$rw_account["account"]}\">".grs("RESELLER_SUBACCOUNT_EXPIRY_DATE_CHANGE")."</a>]");
							  }
							}
						?></td>
						<td width="11%"><?php echo($rw_account["transaction_count"]); ?></td>
						<td width="8%">
						<?php $ip_addr = $userinfo[$rw_account["account"]."-host"];
						
						//print_r($userinfo);
						
						//file_put_contents('filename.txt', print_r($userinfo, true));
						
					/* 	if ($userinfo[$rw_account["account"]."-host"] != '')
						{
							$ip_addr = $userinfo[$rw_account["account"]."-host"];
						}
						
						if ($userinfo[$rw_account["account"]."-ipaddress"] != '')
						{
							$ip_addr = $userinfo[$rw_account["account"]."-ipaddress"];
						} */
						
						//echo $ip_addr;
						if($ip_addr != '')
						{
						//$fake_ip = '187.190.161.120';
						// Create new PDO Object with correct parameters
			                        $db = new PDO("mysql:host=localhost;dbname=pan2", "root", "upCDqaQHCXK9F8HT");
						// Instanciate a new DBIP object with the database connection
						$dbip = new DBIP($db);
						// Lookup an IP address
						$inf = $dbip->Lookup($ip_addr); //Cambiar esto por la variable de la IP que se estaba haciendo echo antes. 
						
						// Query
						$query_country = "SELECT country_name FROM countries WHERE alpha2_code=:inf";
						// Query DB to convert 2 char code to full name
						$stmt2 = $db->prepare($query_country); 
						$stmt2->bindParam(':inf', $inf->country); // Bind the variable to the parameter in the query. 
						$stmt2->execute(); 
						$full_ctry_name = $stmt2->fetch();
						echo $full_ctry_name ["country_name"];
						
						}



						?>
						
						
						</td>
						<td width="26%">
						<table border="0" align="center">
                        <tr>
<?php 
  if( srv_connchk() == "C" ) {

	if ($showuseronline == "1" and isset($userinfo[$rw_account["account"]."-host"])) {
		$printuser = $rw_account["account"];
		if ($activeusersstate[$printuser] == "1") {
		echo "<td><font color=\"#006600\"><b>Viendo</b></font></td>";
		echo "<td><font color=\"#006600\"><b>| ".$userinfo[$rw_account["account"]."-servname"]."</b></font></td>";
		} else { 
		echo "<td colspan=\"3\" align=\"center\"><font color=\"#0099FF\"><b>Waiting for requests...</b></font></td>";
        } 
	  } else {
	    echo "<td colspan=\"3\" align=\"center\">&nbsp;</td>";
	  } 
	} else {
	if ($showuseronline == "1" and isset($userinfo[$rw_account["account"]."-ipaddress"])) {
		echo "<td><font color=\"#006600\"><b>".$userinfo[$rw_account["account"]."-ipaddress"]."</b></font> |</td>";
		echo "<td><font color=\"#006600\"><b>".$userinfo[$rw_account["account"]."-sid"]."</b></font></td>";
	//	echo "<td><font color=\"#006600\"><b>".$userinfo[$rw_account["account"]."-duration"]."</b></font></td>";
	   } else {
	    echo "<td colspan=\"2\" align=\"center\">&nbsp;</td>";
	  } 
  } 
?>
                        </tr>
                        </table>
                      </td>
						<td width="3%">
						<?php if( $G_user_type == "ROOT" ) { ?>
						<a href="javascript:delete_subaccount('<?php echo($rw_account["account"]); ?> ');"><img src="themes/<?php echo($G_theme); ?>/delete.png" border="0" alt="edit" width="16" height="16" title="<?php grse("RESELLER_MANAGEMENT_DELETE"); ?> <?php grse("LINK_VIEW_TO"); ?> <?php echo($rw_account["account"]); ?>"></a>
						<?php } ?>
						</td>
					</tr>
<?php
		}
	}
	else echo("<tr><td colspan=\"4\">".grs("HOME_NO_ACCOUNTS")."</td></tr>");
?>
					<tr>
					  <td width="9%"><hr></td>
					  <td width="9%"><hr></td>
					  <td width="9%"><hr></td>
					  <td width="13%"><hr></td>
					  <td width="12%"><hr></td>
					  <td width="11%"><hr></td>
					  <td width="8%"><hr></td>
					  <td width="26%"><hr></td>
					  <td width="3%"><hr></td>
					</tr>
					<tr>
						<td width="9%"><b><?php echo( mysql_num_rows( $rs_accounts ) ); ?></b></td>
						<td width="9%"></td>
						<td width="9%"></td>
						<td width="13%"></td>
						<td width="12%"></td>
						<td width="11%"></td>
						<td width="8%"><hr></td>
						<td width="26%"><hr></td>
						<td width="3%"><hr></td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	<br>
	<table border="0" width="100%" id="tblSection_3">
		<tr>
			<td colspan="3" class="sectionTitle"><?php grse("HOME_SUBACCOUNT_NEW"); ?></td>
		</tr>
		<tr>
			<td width="25"></td>
			<td class="fieldTitle" width="160" valign="top"><?php grse("HOME_ADD_SUBACCOUNT_INFO"); ?>:</td>
			<td valign="top">
				<table border="0">
					<tr>
						<td width="10%"><?php grse("HOME_ADD_USERNAME"); ?></td>
						<td width="10%"><?php grse("HOME_ADD_PASSWORD"); ?></td>
						<td><?php grse("HOME_ADD_MONTHS"); ?></td>
						<td width="70%"></td>
					</tr>
					<tr>
						<td width="10%"><input type="text" maxlength="64" size="15" name="add_username" value=""></td>
						<td width="10%"><input type="text" maxlength="64" size="15" name="add_password" value=""></td>
						<td><select style="width: 60px" name="add_months">
								<option selected value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5</option>
								<option value="6">6</option>
								<option value="7">7</option>
								<option value="8">8</option>
								<option value="9">9</option>
								<option value="10">10</option>
								<option value="11">11</option>
								<option value="12">12</option>
						</select></td>
						<td width="70%"><input type="button" name="add" value="<?php grse("HOME_ADD_ADD"); ?>" onClick="add_account();"></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td width="25"></td>
			<td class="fieldTitle" width="160"></td>
			<td></td>
		</tr>
		<tr>
			<td width="25"></td>
			<td class="fieldTitle" width="160"></td>
			<td class="errorMessage"><?php if( $status_message_add <> "" ) echo($status_message_add."."); ?></td>
		</tr>
	</table>
<?php include("smartnav.php"); ?>
<input type="hidden" name="__postback" value="1">
</form>
</body>

</html>