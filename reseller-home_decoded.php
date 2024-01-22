<?php
	 
	include("global.php");
	
	$_METHOD = "post";
	$_INCLUDER = "reseller-home.php";
	include("header.php");

	$status_message = "";

	if( !isset($_REQUEST["show"]) ) $_REQUEST["show"] = "";
	if( $_REQUEST["show"] == "" ) $_REQUEST["show"] = "not_expired";

	if( !isset($_REQUEST["order_by"]) ) $_REQUEST["order_by"] = "";
	if( $_REQUEST["order_by"] == "" ) $_REQUEST["order_by"] = "name";
	
	// -- delete subaccount
	if( isset($_REQUEST["delete"]) )
	{
	  if( srv_connchk() == "C" ) {
	  
		$_REQUEST["delete"] = entry_filter($_REQUEST["delete"]);

		mysql_begin();

			if( db_delete("accounts", "username = '$G_username' and account = '{$_REQUEST["delete"]}'") )
			{
				$status_message = grs("SUBACCOUNT_DELETE_SUCCESS");
				mysql_commit();
				
			}
			else
			{
				$status_message = grs("SUBACCOUNT_DELETE_FAILURE");
				mysql_rollback();
			}
		//comiensa el fslb
      } else {

		$_REQUEST["delete"] = entry_filter($_REQUEST["delete"]);

		mysql_begin();
			if( db_delete("accounts", "username = '$G_username' and account = '{$_REQUEST["delete"]}'") )
			{
				$status_message = grs("SUBACCOUNT_DELETE_SUCCESS");
				mysql_commit();
				
				$server_status_message = "";
				$effective_account = get_effective_account($_REQUEST["delete"], query_scalar("select accounts_prefix from users where username = '$G_username'"));
				if( !server_delete_account($effective_account, $server_status_message) )
					$status_message .= ". ".grs("SUBACCOUNT_SERVER_WASNT_DELETED").": [$server_status_message]";
			}
			else
			{
				$status_message = grs("SUBACCOUNT_DELETE_FAILURE");
				mysql_rollback();
			}
	   }		
	}
	
	// -- add account
	if( !$G_disable_reseller_subaccount_creation )
	{
	 if( srv_connchk() == "C" ) {
	   if( $G_user_type == "ROOT" ) {
	   
		if( isset($_REQUEST["add"]) and isset($_REQUEST["add_username"]) and isset($_REQUEST["add_password"]) and isset($_REQUEST["add_comments"]) and
			isset($_REQUEST["add_months"]) )
		{

			$_REQUEST["add_username"] = entry_filter($_REQUEST["add_username"]);
			$_REQUEST["add_password"] = entry_filter($_REQUEST["add_password"]);
            $_REQUEST["add_comments"] = entry_filter($_REQUEST["add_comments"]);
			
			$current_date = date("Ymd", time());
			$coverage_start = mktime(0, 0, 0,
						substr($current_date, 4, 2),
						substr($current_date, 6, 2),
						substr($current_date, 0, 4));

			$coverage_end = $coverage_start + ((3600 * 24 * $G_days_per_month) * (int)$_REQUEST["add_months"]);

				// -- check if account already exists
				if( (int)query_scalar("select count(*) from accounts where username = '$G_username' and ".
					"upper(account) = '".strtoupper($_REQUEST["add_username"])."'") == 0 )
				{
						// -- add record to the database
						mysql_begin();
						if( db_insert("accounts", array(
							"username" => $G_username,
							"account" => $_REQUEST["add_username"],
							"password" => $_REQUEST["add_password"],
							"comments" => $_REQUEST["add_comments"],
						    "maxlogin" => 1,
						    "status" => 1,
							"expires" => date("Y-m-d", $coverage_end)) ) )
						{
								mysql_commit();
								header("Location: reseller-home.php");
								die;
							}
							else
							{
								$status_message = grs("HOME_ADD_FAILED");
								mysql_rollback();
							}
				}
				else $status_message = grs("HOME_ADD_ALREADY_EXISTS");
		}
      } else {
	  // comiensa el fslb insertar
		if( isset($_REQUEST["add"]) and isset($_REQUEST["add_username"]) and isset($_REQUEST["add_password"]) and isset($_REQUEST["add_comments"]) and
			isset($_REQUEST["add_months"]) )
		{

			$_REQUEST["add_username"] = entry_filter($_REQUEST["add_username"]);
			$_REQUEST["add_password"] = entry_filter($_REQUEST["add_password"]);
            $_REQUEST["add_comments"] = entry_filter($_REQUEST["add_comments"]);
			
			$current_date = date("Ymd", time());
			$coverage_start = mktime(0, 0, 0,
						substr($current_date, 4, 2),
						substr($current_date, 6, 2),
						substr($current_date, 0, 4));

			$coverage_end = $coverage_start + ((3600 * 24 * $G_days_per_month) * (int)$_REQUEST["add_months"]);

			if( (get_credit_balance( $G_username ) - (int)$_REQUEST["add_months"]) >= 0 )
			{
				// -- check if account already exists
				if( (int)query_scalar("select count(*) from accounts where upper(account) = '".strtoupper($_REQUEST["add_username"])."'") == 0 )
				{
						// -- add record to the database
						mysql_begin();
						if( db_insert("accounts", array(
							"username" => $G_username,
							"account" => $_REQUEST["add_username"],
							"password" => $_REQUEST["add_password"],
							"comments" => $_REQUEST["add_comments"],
						    "maxlogin" => 1,
						    "status" => 1,
							"expires" => date("Y-m-d", $coverage_end)) ) )
						{
							if( db_insert("transactions", array(
								"username" => $G_username,
								"transaction" => (int)query_scalar("select max(transaction) + 1 from transactions where username = '$G_username'"),
								"type" => "DBIT",
								"periods" => (int)$_REQUEST["add_months"],
								"account" => $_REQUEST["add_username"],
								"timestamp" => date("Y-m-d H:i:s"),
								"coverage_start" => date("Y-m-d", $coverage_start),
								"coverage_end" => date("Y-m-d", $coverage_end)) ) )
							{
								mysql_commit();
								header("Location: reseller-home.php");
								die;
							}
							else
							{
								$status_message = grs("HOME_ADD_FAILED");
								mysql_rollback();
							}
						}
						else
						{
							$status_message = grs("HOME_ADD_FAILED");
							mysql_rollback();
						}
				}
				else $status_message = grs("HOME_ADD_ALREADY_EXISTS");
			}
			else $status_message = grs("HOME_ADD_INSUFFICIENT_CREDIT");
		}	
	  }	
	 } else {
	   if( $G_user_type == "ROOT" ) {
	   
		if( isset($_REQUEST["add"]) and isset($_REQUEST["add_username"]) and isset($_REQUEST["add_password"]) and isset($_REQUEST["add_comments"]) and
			isset($_REQUEST["add_months"]) )
		{

			$_REQUEST["add_username"] = entry_filter($_REQUEST["add_username"]);
			$_REQUEST["add_password"] = entry_filter($_REQUEST["add_password"]);
            $_REQUEST["add_comments"] = entry_filter($_REQUEST["add_comments"]);
			
			$current_date = date("Ymd", time());
			$coverage_start = mktime(0, 0, 0,
						substr($current_date, 4, 2),
						substr($current_date, 6, 2),
						substr($current_date, 0, 4));

			$coverage_end = $coverage_start + ((3600 * 24 * $G_days_per_month) * (int)$_REQUEST["add_months"]);

				// -- check if account already exists
				if( (int)query_scalar("select count(*) from accounts where upper(account) = '".strtoupper($_REQUEST["add_username"])."'") == 0 )
				{
					// -- add server user
					$server_status_description = "";
					$effective_account = get_effective_account($_REQUEST["add_username"], $G_user_account_prefix);
					if( server_add_account($effective_account, $_REQUEST["add_password"], $coverage_end, TRUE,
						sprintf($G_fslb_global_account_comment, $G_username), $server_status_description) )
					{
						// -- add record to the database
						mysql_begin();
						if( db_insert("accounts", array(
							"username" => $G_username,
							"account" => $_REQUEST["add_username"],
							"password" => $_REQUEST["add_password"],
							"comments" => $_REQUEST["add_comments"],
						    "maxlogin" => 1,
						    "status" => 1,
							"expires" => date("Y-m-d", $coverage_end)) ) )
						{
								mysql_commit();
								header("Location: reseller-home.php");
								die;
						}
						else
						{
							$status_message = grs("HOME_ADD_FAILED");
							mysql_rollback();
						}
					}
					else $status_message = "SERVER: ".$server_status_description;
				}
				else $status_message = grs("HOME_ADD_ALREADY_EXISTS");
		}
	  } else {
		if( isset($_REQUEST["add"]) and isset($_REQUEST["add_username"]) and isset($_REQUEST["add_password"]) and isset($_REQUEST["add_comments"]) and
			isset($_REQUEST["add_months"]) )
		{

			$_REQUEST["add_username"] = entry_filter($_REQUEST["add_username"]);
			$_REQUEST["add_password"] = entry_filter($_REQUEST["add_password"]);
            $_REQUEST["add_comments"] = entry_filter($_REQUEST["add_comments"]);
			
			$current_date = date("Ymd", time());
			$coverage_start = mktime(0, 0, 0,
						substr($current_date, 4, 2),
						substr($current_date, 6, 2),
						substr($current_date, 0, 4));

			$coverage_end = $coverage_start + ((3600 * 24 * $G_days_per_month) * (int)$_REQUEST["add_months"]);

			if( (get_credit_balance( $G_username ) - (int)$_REQUEST["add_months"]) >= 0 )
			{
				// -- check if account already exists
				if( (int)query_scalar("select count(*) from accounts where upper(account) = '".strtoupper($_REQUEST["add_username"])."'") == 0 )
				{
					// -- add server user
					$server_status_description = "";
					$effective_account = get_effective_account($_REQUEST["add_username"], $G_user_account_prefix);
					if( server_add_account($effective_account, $_REQUEST["add_password"], $coverage_end, TRUE,
						sprintf($G_fslb_global_account_comment, $G_username), $server_status_description) )
					{
						// -- add record to the database
						mysql_begin();
						if( db_insert("accounts", array(
							"username" => $G_username,
							"account" => $_REQUEST["add_username"],
							"password" => $_REQUEST["add_password"],
							"comments" => $_REQUEST["add_comments"],
						    "maxlogin" => 1,
						    "status" => 1,
							"expires" => date("Y-m-d", $coverage_end)) ) )
						{
							if( db_insert("transactions", array(
								"username" => $G_username,
								"transaction" => (int)query_scalar("select max(transaction) + 1 from transactions where username = '$G_username'"),
								"type" => "DBIT",
								"periods" => (int)$_REQUEST["add_months"],
								"account" => $_REQUEST["add_username"],
								"timestamp" => date("Y-m-d H:i:s"),
								"coverage_start" => date("Y-m-d", $coverage_start),
								"coverage_end" => date("Y-m-d", $coverage_end)) ) )
							{
								mysql_commit();
								header("Location: reseller-home.php");
								die;
							}
							else
							{
								$status_message = grs("HOME_ADD_FAILED");
								mysql_rollback();
							}
						}
						else
						{
							$status_message = grs("HOME_ADD_FAILED");
							mysql_rollback();
						}
					}
					else $status_message = "SERVER: ".$server_status_description;
				}
				else $status_message = grs("HOME_ADD_ALREADY_EXISTS");
			}
			else $status_message = grs("HOME_ADD_INSUFFICIENT_CREDIT");
		}
	  }	
	 }  
	}
	
	// -- get user sub-accounts
	if( $_REQUEST["order_by"] == "expiry_date")
		$query_order_by = "expires";
	else
		$query_order_by = "account";
	
	switch( $_REQUEST["show"] )
	{
		case "expired":
			$query_show = "and expires < current_date()";
			break;

		case "not_expired":
			$query_show = "and expires >= current_date()";
			break;

		default:
			$query_show = "";
			break;
	}

	$rs_accounts = mysql_query("select account, password, comments, unix_timestamp(expires) as expires, status from accounts where username = ".
		"'$G_username' ".$query_show." order by ".$query_order_by);

	// -- handle paging
	$page_index = 1;
	if( isset($_REQUEST["page_index"]) ) $page_index = (int)entry_filter($_REQUEST["page_index"]);
	$page_count = ((int)(mysql_num_rows( $rs_accounts ) / $G_reselled_accounts_per_page)) + 1;

	if( isset($_REQUEST["page_first"]) ) $page_index = 1;
	if( isset($_REQUEST["page_previous"]) ) $page_index = (int)entry_filter($_REQUEST["page_index"]) - 1;
	if( isset($_REQUEST["page_next"]) ) $page_index = (int)entry_filter($_REQUEST["page_index"]) + 1;
	if( isset($_REQUEST["page_last"]) ) $page_index = $page_count;

	// -- correct page index if needed
	if( $page_index < 1 ) $page_index = 1;
	if( $page_index > $page_count) $page_index = $page_count;
	
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
					$userinfo[$xmlusername."-connected"] = (string)$xml->$proxyusers->user[$i]->session[$activesession]->attributes()->connected;
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
			$servicename = "service-name";
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
					$userinfo[$xmlusername."-servicename"] = (string)$xml->$proxyusers->user[$i]->attributes()->$servicename;
					$userinfo[$xmlusername."-loggedonsince"] = (string)$xml->$proxyusers->user[$i]->attributes()->loggedonsince;
					$i++;
				}
		}
	}	
?>

<script type="text/javascript" language="javascript"><!--
function view_transactions( transaction_type )
{
	w = 980; h = (screen.height * 0.60);
	left_position = (screen.width) ? (screen.width - w) / 2 : 0;
	top_position = (screen.height) ? (screen.height - h) / 2 : 0;
	
	transaction_window = window.open('reseller-transaction-history.php?type=' + transaction_type, 'transactions', 'menubar=no,scrollbars=yes,resizable=no,height=' + h + ',width=' + w + ',top=' + top_position + ',left=' + left_position);
	if( window.focus ) transaction_window.focus();
}

function view_account_actions( account )
{
	w = 600; h = (screen.height * 0.70);
	left_position = (screen.width) ? (screen.width - w) / 2 : 0;
	top_position = (screen.height) ? (screen.height - h) / 2 : 0;

	account_actions_window = window.open('reseller-subaccount-actions.php?account=' + account, 'account_actions', 'menubar=no,scrollbars=yes,resizable=no,height=' + h + ',width=' + w + ',top=' + top_position + ',left=' + left_position);
	if( window.focus ) account_actions_window.focus();
}

function add_account()
{
	root_form = document.forms[0];

	if( root_form.add_username.value != '' && root_form.add_password.value != '' && root_form.add_months.value != '' )
	{
		if( !confirm('<?php grse("HOME_ADD_CONFIRM"); ?>.') ) return;

		add_account_url = 'reseller-home.php?scrollLeft=' + escape(root_form.scrollLeft.value) + '&scrollTop=' +
			escape(root_form.scrollTop.value) +	'&__postback=1&add=1&add_username=' +
			escape(root_form.add_username.value) + '&add_password=' + escape(root_form.add_password.value) +
			'&add_comments=' + escape(root_form.add_comments.value) +
			'&add_months=' + escape(root_form.add_months.value) + '&show=' + root_form.show.value +
			'&order_by=' + root_form.order_by.value + '&page_index=' + root_form.page_index.value;
		
		location.href = add_account_url;
	}
	else alert('<?php grse("HOME_ADD_OBLIGATORY"); ?>.');
}

function validar(e) {
    tecla = (document.all) ? e.keyCode : e.which;
    if (tecla==8) return true;
    patron = /[A-Za-z\w]/;
    te = String.fromCharCode(tecla);
    return patron.test(te);
} 

function delete_subaccount( account )
{
	root_form = document.forms[0];

	if( !confirm('<?php grse("SUBACCOUNT_DELETE_CONFIRM"); ?>.') ) return;

	delete_subaccount_url= 'reseller-home.php?scrollLeft=' +
		escape(root_form.scrollLeft.value) + '&scrollTop=' +
		escape(root_form.scrollTop.value) + '&__postback=1&username=<?php echo( $G_username ); ?>' +
		'&delete=' + account;

	location.href = delete_subaccount_url;
}
// -->
</script>

<table border="0" width="100%" id="tblCategory">
	<tr>
		<td class="categoryTitle" align="center">:: <?php grse("MENU_HOME"); ?> ::</td>
	</tr>
	<tr>
		<td class="categoryTitle" align="center" height="18px"></td>
	</tr>
	<tr>
		<td>
			<table border="0" width="100%" id="tblSection_1">
				<tr>
					<td colspan="7" class="sectionTitle"><?php grse("HOME_RESELLER_ACCT_INFO"); ?></td>
				</tr>
				<tr>
					<td width="25"></td>
					<td class="fieldTitle" width="120"><?php grse("HOME_RESELLER"); ?>:</td>
					<td width="200"><?php echo(" $G_username | $G_username_name"); ?></td>
					<td class="fieldTitle" width="120"><?php grse("HOME_CREDIT_PURCHASES"); ?>:</td>
					<td width="200"><a href="javascript:view_transactions('CRDT');"><?php grse("HOME_VIEW_DETAILS"); ?>...</a></td>
					<td class="fieldTitle" width="120"><?php grse("HOME_CREDIT_BALANCE"); ?>:</td>
					<td>
					<?php  
					 echo(get_credit_balance( $G_username )." ".grs("HOME_MONTHS")); 
					 ?></td>
				</tr>
				<tr>
					<td width="25"></td>
					<td class="fieldTitle" width="120"><?php grse("HOME_LOGGED_ON_SINCE"); ?>:</td>
					<td width="200"><?php echo(_datetime_format($G_current_login, TRUE)); ?></td>
					<td class="fieldTitle" width="120"><?php grse("HOME_CREDIT_USAGE"); ?>:</td>
					<td width="200"><a href="javascript:view_transactions('DBIT');"><?php grse("HOME_VIEW_DETAILS"); ?>...</a></td>
					<td class="fieldTitle" width="120"></td>
					<td></td>
				</tr>
			<?php
				if( $G_fslb_client_show_server_url )
				{
			?>
				<tr>
					<td width="25"></td>
					<td class="fieldTitle" width="120"><?php grse("HOME_SERVER_PORT_DES"); ?>:</td>
					<td colspan="5"><?php echo("$G_fslb_server_display:$G_fslb_client_port/$G_fslb_client_des_key"); ?></td>
				</tr>
			<?php
				}
			?>
			</table>
			<br>
			<table border="0" width="100%" id="tblSection_2">
				<tr>
					<td colspan="3" class="sectionTitle"><?php grse("HOME_SUBACCOUNT_LIST"); ?></td>
				</tr>
				<tr>
					<td width="25"></td>
					<td class="pagingBackground" colspan="2">
						<table border="0" width="100%"><tr>
							<td>
								<?php grse("HOME_SHOW"); ?>:
								<select name="show" onChange="this.form.submit();">
									<option <?php if( $_REQUEST["show"] == "all" ) echo("selected "); ?>value="all"><?php grse("HOME_SHOW_ALL"); ?></option>
									<option <?php if( $_REQUEST["show"] == "not_expired" ) echo("selected "); ?>value="not_expired"><?php grse("HOME_SHOW_NOT_EXPIRED"); ?></option>
									<option <?php if( $_REQUEST["show"] == "expired" ) echo("selected "); ?>value="expired"><?php grse("HOME_SHOW_EXPIRED"); ?></option>
								</select>							</td>
							<td>
								<?php grse("HOME_ORDER_BY"); ?>:
								<select name="order_by" onChange="this.form.submit();">
									<option <?php if( $_REQUEST["order_by"] == "name" ) echo("selected "); ?>value="name"><?php grse("HOME_ORDER_BY_SUBACCOUNT_NAME"); ?></option>
									<option <?php if( $_REQUEST["order_by"] == "expiry_date" ) echo("selected "); ?>value="expiry_date"><?php grse("HOME_ORDER_BY_SUBACCOUNT_EXPIRY"); ?></option>
								</select>							</td>
							<td width="40%" align="right">
								<?php grse("HOME_LIST_PAGE"); ?>: <input name="page_index" type="text" size="4" maxlength="4" value="<?php echo($page_index); ?>"> <?php grse("HOME_LIST_OF"); ?> <?php echo($page_count); ?> <input type="submit" name="page_go" value="<?php grse("HOME_LIST_GO"); ?>"> | <input type="submit" name="page_first" value="<<"> <input type="submit" name="page_previous" value="<"> <input type="submit" name="page_next" value=">"> <input type="submit" name="page_last" value=">>">							</td>
						</tr></table>					</td>
				</tr>
				<tr>
					<td width="25"></td>
				  <td colspan="2" class="listBackground"><table border="0" width="100%">
                    <tr class="listHeader">
                      <td width="16%"><?php grse("HOME_LIST_SUBACCOUNT"); ?></td>
                      <td width="16%"><?php grse("HOME_LIST_PASSWORD"); ?></td>
                      <td width="16%"><?php grse("HOME_LIST_COMMENTS"); ?></td>
                      <td width="13%"><?php grse("HOME_LIST_EXPIRY_DATE"); ?></td>
                      <td width="13%"><?php grse("HOME_LIST_IP"); ?></td>
                      <td width="11%"><?php grse("HOME_LIST_CHANEL"); ?></td>
                      <td width="15%"><?php grse("HOME_LIST_DURATION"); ?></td>
                    </tr>
                    <?php
	if( mysql_num_rows( $rs_accounts ) )
	{
		if( $page_index == 1 )
			$rw_account = mysql_fetch_assoc( $rs_accounts );
		else
		{
			for($i = 0; $i < (($page_index - 1) * $G_reselled_accounts_per_page); $i++)
			{
				if( !($rw_account = mysql_fetch_assoc( $rs_accounts )) ) break;
			}
		}
		
		$display_count = 0;
		while( $rw_account )
		{
			// -- exit after the number of users per page has been displayed.
			if( $display_count >= $G_reselled_accounts_per_page ) break;
?>
                    <tr>
                      <td width="16%"><a href="javascript:view_account_actions('<?php echo($rw_account["account"]); ?>');" title="<?php grse("HOME_LIST_EDIT"); ?> <?php echo($rw_account["account"]); ?>"><?php echo(get_effective_account($rw_account["account"], $G_user_account_prefix)); ?> <img src="themes/<?php echo($G_theme); ?>/edit.png" border="0" alt="edit" width="10" height="10"></a> <a href="javascript:delete_subaccount('<?php echo($rw_account["account"]); ?> ');"><img src="themes/<?php echo($G_theme); ?>/delete.png" border="0" alt="edit" width="10" height="10" title="<?php grse("RESELLER_MANAGEMENT_DELETE"); ?> <?php grse("LINK_VIEW_TO"); ?> <?php echo($rw_account["account"]); ?>"></a></td>
                      <td width="16%"><?php echo($rw_account["password"]); ?></td>
                      <td width="16%"><?php echo($rw_account["comments"]); ?></td>
                      <td width="13%"><?php echo(_date_format($rw_account["expires"])); ?></td>
<?php 
  if( srv_connchk() == "C" ) {

	if ($showuseronline == "1" and isset($userinfo[$rw_account["account"]."-host"])) {
		$printuser = $rw_account["account"];
		if ($activeusersstate[$printuser] == "1") {
		echo "<td><font color=\"#006600\"><b>".$userinfo[$rw_account["account"]."-host"]."</b></font></td>";
		echo "<td><font color=\"#006600\"><b>".$userinfo[$rw_account["account"]."-servname"]."</b></font></td>";
		echo "<td><font color=\"#006600\"><b>".$userinfo[$rw_account["account"]."-duration"]."</b></font></td>";
		} else { 
		echo "<td colspan=\"3\" align=\"center\"><font color=\"#0099FF\"><b>Waiting for requests...</b></font></td>";
        } 
	  } else {
	    echo "<td colspan=\"3\" align=\"center\"><font color=\"#FF0000\"><b>Account is not connected</b></font></td>";
	  } 
	} else {
	if ($showuseronline == "1" and isset($userinfo[$rw_account["account"]."-ipaddress"])) {
		echo "<td><font color=\"#006600\"><b>".$userinfo[$rw_account["account"]."-ipaddress"]."</b></font></td>";
		echo "<td><font color=\"#006600\"><b>".$userinfo[$rw_account["account"]."-sid"]." ".$userinfo[$rw_account["account"]."-servicename"]."</b></font></td>";
		
$fecha = $userinfo[$rw_account["account"]."-loggedonsince"];
$dia = substr($fecha,8,2);
$mes = substr($fecha,5,2);
$ano = substr($fecha,0,4);
$h = substr($fecha,11,2);
$m = substr($fecha,14,2);
$s = substr($fecha,17,2);
$actual = $ano."-".$mes."-".$dia." ".$h.":".$m.":".$s;

$start_date = strtotime("$actual");
$end_date = strtotime(date("Y-m-d h:i:s"));
$ajavahe = $end_date - $start_date;
$time_between = gmstrftime('%Hh %Mm %Ss', $ajavahe);

		echo "<td><font color=\"#006600\"><b>".$time_between."</b></font></td>";
	   } else {
	    echo "<td colspan=\"3\" align=\"center\">&nbsp;</td>";
	  } 
  } 
?>
                    </tr>
                    <?php
			$display_count++;
			$rw_account = mysql_fetch_assoc( $rs_accounts );
		}
	}
	else echo("<tr><td colspan=\"4\">".grs("HOME_NO_ACCOUNTS")."</td></tr>");
?>
                  </table></td>
				</tr>
				
				<tr>
					<td width="25"></td>
					<td class="fieldTitle" width="160"></td>
					<td></td>
				</tr>
			</table>
	<?php
		if( !$G_disable_reseller_subaccount_creation )
		{
	?>
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
								<td width="10%"><?php grse("HOME_ADD_COMMENT"); ?></td>
								<td><?php grse("HOME_ADD_MONTHS"); ?></td>
								<td width="70%"></td>
							</tr>
							<tr>
								<td width="10%"><input type="text" maxlength="64" size="15" name="add_username" value="<?php echo RandomU(6) ?>" onKeyPress="return validar(event)"></td>
								<td width="10%"><input type="text" maxlength="64" size="15" name="add_password" value="<?php echo RandomP(6) ?>" onKeyPress="return validar(event)"></td>
								<td width="10%"><input type="text" maxlength="64" size="15" name="add_comments" value=""></td>
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
					<td class="errorMessage"><?php if( $status_message <> "" ) echo($status_message."."); ?></td>
				</tr>
			</table>
	<?php
		}
	?>
		</td>
	</tr>
</table>
<?php include("footer.php"); ?>
