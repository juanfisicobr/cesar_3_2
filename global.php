<?php
	$G_globalIncluded = TRUE;
	
	// -- version
	$G_version = "2.90";
	
	include("config.php");
	include("http-client.php");
	ob_start();

	if( !session_id() ) { session_start(); }

	if( isset($_REQUEST["language"]) )
	{
		$_SESSION["current_language"] = $_REQUEST["language"];
		setcookie("default_language", $_REQUEST["language"], time() + 31536000);
		$G_current_language = $_REQUEST["language"];
	}
	
	/* declaraciones globales */
	if( !isset($_SESSION["username"]) ) $_SESSION["username"] = "";
	if( !isset($_SESSION["username_name"]) ) $_SESSION["username_name"] = "";
	if( !isset($_SESSION["user_type"]) ) $_SESSION["user_type"] = "";
	if( !isset($_SESSION["user_account_prefix"]) ) $_SESSION["user_account_prefix"] = "";
	if( !isset($_SESSION["last_login"]) ) $_SESSION["last_login"] = 0;
	if( !isset($_SESSION["current_login"]) ) $_SESSION["current_login"] = 0;
	if( !isset($_SESSION["username_owner"]) ) $_SESSION["username_owner"] = "";

	if( !isset($_SESSION["current_language"]) )
	{
		if( isset($_COOKIE["default_language"] ) )
		{
			$_SESSION["current_language"] = $_COOKIE["default_language"];
		}
		else
		{
			$_SESSION["current_language"] = $G_default_language;
			setcookie("default_language", $G_default_language, time() + 31536000);
		}
	}
	
	$G_username = $_SESSION["username"];
	$G_username_name = $_SESSION["username_name"];
	$G_user_type = $_SESSION["user_type"];
	$G_user_account_prefix = $_SESSION["user_account_prefix"];
	
	$G_current_language = $_SESSION["current_language"];
	$G_last_login = (int)$_SESSION["last_login"];
	$G_current_login = $_SESSION["current_login"];
	
	$G_username_owner = $_SESSION["username_owner"];
	
	include("language.php");

	switch( $G_current_language )
	{
		case "es":
		{
			$G_date_format = "d/m/Y";
			$G_datetime_format = "d/m/Y g:ia";
			break;
		}

		case "en":
		{
			$G_date_format = "m/d/Y";
			$G_datetime_format = "m/d/Y g:ia";
			break;
		}

		default:
		{
			$G_date_format = "Y-m-d";
			$G_datetime_format = "Y-m-d g:ia";
			break;
		}
	}

	if( !isset($G_login_page) )
	{
		if( $G_username == "" )
		{
			header("Location: login.php");
			die;
		}
	}
	
	switch( $G_user_type )
	{
		case "ROOT":
			$G_user_type_description = grs("USER_TYPE_ROOT");
			break;
		
		case "ADMN":
			$G_user_type_description = grs("USER_TYPE_ADMN");
			break;
		
		case "RSLR":
			$G_user_type_description = grs("USER_TYPE_RSLR");
			break;

		default:
			$G_user_type_description = "";
			break;
	}
	
	mysql_connect($G_mysql_servername, $G_mysql_username, $G_mysql_password);
	mysql_select_db( $G_mysql_dbname );

	$_METHOD = "get";
	
	//------------------- funciones -------------------------
	function server_get_account_info( $account, $password, &$expiry, &$status, &$comment, &$status_description )
	{
		global $G_fslb_server;
		global $G_fslb_integration_calls_port;
		global $G_fslb_integration_calls_username;
		global $G_fslb_integration_calls_password;
		
		$server = new http_client($G_fslb_server, $G_fslb_integration_calls_port);
		$server->set_authorization($G_fslb_integration_calls_username, $G_fslb_integration_calls_password);
		
		if( $server->get("/user-management.fssp?integration=1&info=1&info_username=$account&info_password=$password") )
		{
			$response = $server->get_content();

			if( substr($response, 0, 3) == "OK " )
			{
				$values = explode(":", substr($response, 3));
				
				$expiry = $values[0];
				$status = $values[1];
				$comment = $values[3];

				$status_description = "Account information retrieved successfully";
				return TRUE;
			}
			else
			{
				if( substr($response, 0, 4) == "NOK " )
					$status_description = substr($response, 4);
				else
					$status_description = "Unknown error";
				
				return FALSE;
			}
		}
		
		$status_description = "Failed to send request to server";
		return FALSE;
	}

	function server_add_account( $account, $password, $expiry, $status, $comment, &$status_description )
	{
		global $G_fslb_server;
		global $G_fslb_integration_calls_port;
		global $G_fslb_integration_calls_username;
		global $G_fslb_integration_calls_password;

		// -- validate parameters
		if( $account == "" || $password == "" || $expiry == 0 || $status == "" )
		{
			$status_description = "Argument(s) missing: ";
			if( $account == "" ) $status_description .= "[Account] ";
			if( $passowrd == "" ) $status_description .= "[Password] ";
			if( $expiry == 0 ) $status_description .= "[Expiry] ";
			if( $status == "" ) $status_description .= "[Status] ";
			return FALSE;
		}

		$server = new http_client($G_fslb_server, $G_fslb_integration_calls_port);
		$server->set_authorization($G_fslb_integration_calls_username, $G_fslb_integration_calls_password);

		$expiry_string = date("Y-m-d", $expiry);
		if( $status ) $status_string = "1";	else $status_string = "0";
		$comment_string = urlencode( $comment );
		
		if( $server->get("/user-management.fssp?integration=1&add=1&add_username=$account&add_password=$password&".
			"add_expiry=$expiry_string&add_status=$status_string&add_comment=$comment_string") )
		{
			$response = $server->get_content();
			
			if( substr($response, 0, 3) == "OK " )
			{
				$status_description = substr($response, 3);
				return TRUE;
			}
			else
			{
				if( substr($response, 0, 4) == "NOK " )
					$status_description = substr($response, 4);
				else
					$status_description = "Unknown error";

				return FALSE;
			}
		}

		$status_description = "Failed to send request to server";
		return FALSE;
	}

	function server_update_account_password( $account, $password, &$status_description )
	{
		global $G_fslb_server;
		global $G_fslb_integration_calls_port;
		global $G_fslb_integration_calls_username;
		global $G_fslb_integration_calls_password;

		// -- validate parameters
		if( $account == "" || $password == "" )
		{
			$status_description = "Argument(s) missing: ";
			if( $account == "" ) $status_description .= "[Account] ";
			if( $passowrd == "" ) $status_description .= "[Password] ";
			return FALSE;
		}

		$server = new http_client($G_fslb_server, $G_fslb_integration_calls_port);
		$server->set_authorization($G_fslb_integration_calls_username, $G_fslb_integration_calls_password);
		
		if( $server->get("/user-management.fssp?integration=1&update=1&update_username=$account&".
			"update_password=$password" ) )
		{
			$response = $server->get_content();
			
			if( substr($response, 0, 3) == "OK " )
			{
				$status_description = substr($response, 3);
				return TRUE;
			}
			else
			{
				if( substr($response, 0, 4) == "NOK " )
					$status_description = substr($response, 4);
				else
					$status_description = "Unknown error";

				return FALSE;
			}
		}

		$status_description = "Failed to send request to server";
		return FALSE;
	}
	
	function server_update_account_expiry( $account, $expiry, &$status_description )
	{
		global $G_fslb_server;
		global $G_fslb_integration_calls_port;
		global $G_fslb_integration_calls_username;
		global $G_fslb_integration_calls_password;
		
		// -- validate parameters
		if( $account == "" || $expiry == 0 )
		{
			$status_description = "Argument(s) missing: ";
			if( $account == "" ) $status_description .= "[Account] ";
			if( $expiry == 0 ) $status_description .= "[Expiry] ";
			return FALSE;
		}
		
		$server = new http_client($G_fslb_server, $G_fslb_integration_calls_port);
		$server->set_authorization($G_fslb_integration_calls_username, $G_fslb_integration_calls_password);

		$expiry_string = date("Y-m-d", $expiry);

		if( $server->get("/user-management.fssp?integration=1&update=1&update_username=$account&".
			"update_expiry=$expiry_string" ) )
		{
			$response = $server->get_content();

			if( substr($response, 0, 3) == "OK " )
			{
				$status_description = substr($response, 3);
				return TRUE;
			}
			else
			{
				if( substr($response, 0, 4) == "NOK " )
					$status_description = substr($response, 4);
				else
					$status_description = "Unknown error";

				return FALSE;
			}
		}

		$status_description = "Failed to send request to server";
		return FALSE;
	}
	
	function server_update_account_status( $account, $status, &$status_description )
	{
		global $G_fslb_server;
		global $G_fslb_integration_calls_port;
		global $G_fslb_integration_calls_username;
		global $G_fslb_integration_calls_password;

		// -- validate parameters
		if( $account == "" || $status == "" )
		{
			$status_description = "Argument(s) missing: ";
			if( $account == "" ) $status_description .= "[Account] ";
			if( $status == "" ) $status_description .= "[Status] ";
			return FALSE;
		}

		$server = new http_client($G_fslb_server, $G_fslb_integration_calls_port);
		$server->set_authorization($G_fslb_integration_calls_username, $G_fslb_integration_calls_password);
		
		if( $server->get("/user-management.fssp?integration=1&update=1&update_username=$account&".
			"update_status=$status" ) )
		{
			$response = $server->get_content();
			
			if( substr($response, 0, 3) == "OK " )
			{
				$status_description = substr($response, 3);
				return TRUE;
			}
			else
			{
				if( substr($response, 0, 4) == "NOK " )
					$status_description = substr($response, 4);
				else
					$status_description = "Unknown error";

				return FALSE;
			}
		}

		$status_description = "Failed to send request to server";
		return FALSE;
	}
	
	//  suspender todos usuarios super y dealer
	
	function server_update_account_status_full( $account, $status, &$status_description )
	{
		global $G_fslb_server;
		global $G_fslb_integration_calls_port;
		global $G_fslb_integration_calls_username;
		global $G_fslb_integration_calls_password;

		// -- validate parameters
		if( $account == "" || $status == "" )
		{
			$status_description = "Argument(s) missing: ";
			if( $account == "" ) $status_description .= "[Account] ";
			if( $status == "" ) $status_description .= "[Status] ";
			return FALSE;
		}

		$server = new http_client($G_fslb_server, $G_fslb_integration_calls_port);
		$server->set_authorization($G_fslb_integration_calls_username, $G_fslb_integration_calls_password);
		
		if( $server->get("/user-management.fssp?integration=1&update=1&update_username=$account&".
			"update_status=$status" ) )
		{
			$response = $server->get_content();
			
			if( substr($response, 0, 3) == "OK " )
			{
				$status_description = substr($response, 3);
				return TRUE;
			}
			else
			{
				if( substr($response, 0, 4) == "NOK " )
					$status_description = substr($response, 4);
				else
					$status_description = "Unknown error";

				return FALSE;
			}
		}

		$status_description = "Failed to send request to server";
		return FALSE;
	}
	
	// fin de suspender todos usuarios super y dealer
	
	function server_update_account_max_login_count( $account, $max_login_count, &$status_description )
	{
		global $G_fslb_server;
		global $G_fslb_integration_calls_port;
		global $G_fslb_integration_calls_username;
		global $G_fslb_integration_calls_password;

		// -- validate parameters
		if( $account == "" || $max_login_count == "" )
		{
			$status_description = "Argument(s) missing: ";
			if( $account == "" ) $status_description .= "[Account] ";
			if( $max_login_count == "" ) $status_description .= "[Max_Login_Count] ";
			return FALSE;
		}

		$server = new http_client($G_fslb_server, $G_fslb_integration_calls_port);
		$server->set_authorization($G_fslb_integration_calls_username, $G_fslb_integration_calls_password);
		
		if( $server->get("/user-management.fssp?integration=1&update=1&update_username=$account&".
			"update_max_login_count=$max_login_count" ) )
		{
			$response = $server->get_content();
			
			if( substr($response, 0, 3) == "OK " )
			{
				$status_description = substr($response, 3);
				return TRUE;
			}
			else
			{
				if( substr($response, 0, 4) == "NOK " )
					$status_description = substr($response, 4);
				else
					$status_description = "Unknown error";

				return FALSE;
			}
		}

		$status_description = "Failed to send request to server";
		return FALSE;
	}

	function server_delete_account( $account, &$status_description )
	{
		global $G_fslb_server;
		global $G_fslb_integration_calls_port;
		global $G_fslb_integration_calls_username;
		global $G_fslb_integration_calls_password;

		// -- validate parameters
		if( $account == "" )
		{
			$status_description = "Argument(s) missing: ";
			if( $account == "" ) $status_description .= "[Account] ";
			return FALSE;
		}
		
		$server = new http_client($G_fslb_server, $G_fslb_integration_calls_port);
		$server->set_authorization($G_fslb_integration_calls_username, $G_fslb_integration_calls_password);
		
		if( $server->get("/user-management.fssp?integration=1&delete=1&delete_username=$account" ) )
		{
			$response = $server->get_content();

			if( substr($response, 0, 3) == "OK " )
			{
				$status_description = substr($response, 3);
				return TRUE;
			}
			else
			{
				if( substr($response, 0, 4) == "NOK " )
					$status_description = substr($response, 4);
				else
					$status_description = "Unknown error";

				return FALSE;
			}
		}

		$status_description = "Failed to send request to server";
		return FALSE;
	}
	
	function get_credit_balance( $username )
	{
		return (int)query_scalar("select sum(case when type = 'CRDT' then periods else -periods end) as balance from transactions where username = '$username';");
	}

	function get_effective_account( $account, $account_prefix )
	{
		global $G_fslb_global_account_prefix;
		global $G_user_account_prefix;
		global $G_fslb_global_account_prefix_separator;
		
		if( $G_fslb_global_account_prefix <> "" )
			$global_account_prefix = $G_fslb_global_account_prefix.$G_fslb_global_account_prefix_separator;
		else
			$global_account_prefix = "";

		if( $account_prefix <> "" )
			$prefix = $account_prefix.$G_fslb_global_account_prefix_separator;
		else
			$prefix = "";
		
		return $global_account_prefix.$prefix.$account;
	}

	function update_login_info()
	{
		global $G_username;

		mysql_query("update users set last_login_time = current_login_time where username = '$G_username'");
		mysql_query("update users set current_login_time = now() where username = '$G_username'");
		
		$_SESSION["last_login"] = query_scalar("select unix_timestamp(last_login_time) from users where username = '$G_username'");
		$_SESSION["current_login"] = query_scalar("select unix_timestamp(current_login_time) from users where username = '$G_username'");
	}

	function mysql_begin()
	{
		mysql_query("BEGIN");
	}

	function mysql_commit()
	{
		mysql_query("COMMIT");
	}

	function mysql_rollback()
	{
		mysql_query("ROLLBACK");
	}

	function query_scalar( $query )
	{
		$result = mysql_query( $query );
		if( mysql_num_rows( $result ) == 0 )
			return "";
		else
		{
			$row = mysql_fetch_array( $result );
			return $row[0];
		}
	}
	
	function query_vector( $query )
	{
		$result = mysql_query( $query );
		if( mysql_num_rows( $result ) == 0 )
			return array();
		else
		{
			while( ($row = mysql_fetch_array( $result )) )
				$temp[] = $row[0];
			return $temp;
			
		}
	}
	
	function db_insert( $table, $values )
	{
		if( count($values) == 0 ) return FALSE;

		$sql = "insert into $table(";
		foreach( $values as $name => $value )
		{
			$sql .= $name.", ";
		}

		$sql = substr($sql, 0, strlen($sql) - 2).") values(";
		foreach( $values as $name => $value )
		{
			if( gettype( $value ) == "string" )
			{
				if( $value == "[null]" )
					$sql .= "null, ";
				else
					$sql .= "'".$value."', ";
			}
			else
				$sql .= $value.", ";
		}
		$sql = substr($sql, 0, strlen($sql) - 2).")";
		
		//echo $sql."|";
		$result = mysql_query($sql);
		//echo mysql_error()."|";
		return $result;
	}
	
	function db_update( $table, $values, $filter )
	{
		if( count($values) == 0 ) return FALSE;
		
		$sql = "update $table set ";
		foreach( $values as $name => $value )
		{
			$sql .= $name." = ";
			if( gettype( $value ) == "string" )
			{
				if( $value == "[null]" )
					$sql .= "null, ";
				else
					$sql .= "'".$value."', ";
			}
			else
				$sql .= $value.", ";
		}
		$sql = substr($sql, 0, strlen($sql) - 2);
		
		if( $filter != "" )
		{
			$sql .= " where $filter";
		}
		
		return mysql_query($sql);
	}
	
	function db_delete( $table, $filter )
	{
		$sql = "delete from $table";
		if( $filter != "" ) 
		{
			$sql .= " where $filter";
			
		}
		
		return mysql_query($sql);
	}
	
	function search_sql( $criteria )
	{
		$elements = explode(" ", str_replace(array("%", "_", "[", "]", "?", "^"), "", $criteria));
		$result = "%";
		
		foreach( $elements as $element )
		{
			if( trim($element) != "" )
				$resultado .= $element."%";
		}
		return $resultado;
	}
	
	function mysql_datetime($format, $datetime)
	{
    	$year = substr($datetime, 0, 4);
	    $month = substr($datetime, 5, 2);
	    $day = substr($datetime, 8, 2);
	    $hour = substr($datetime, 11 ,2);
	    $min = substr($datetime, 14, 2);
	    $sec = substr($datetime, 17, 2);
		
		return date($format, mktime($hour, $min, $sec, $month, $day, $year));
	}
	
	function date_add_internal($interval, $quantity, $date)
	{
		switch( $interval )
		{
			case "d":
				return $date + (86400 * $quantity);
				break;
			
			default:
				return time();
		}
	}
	
	function currency_format( $amount )
	{
		return number_format($amount, 2, '.', ',');
	}

	function _date_format($timestamp, $localtime = FALSE)
	{
		global $G_date_format;

		if( (int)$timestamp )
		{
			if( $localtime )
				return date($G_date_format, $timestamp);
			else
				return gmdate($G_date_format, $timestamp);
		}
		else return "";
	}

	function _datetime_format($timestamp, $localtime = FALSE)
	{
		global $G_datetime_format;

		if( (int)$timestamp )
		{
			if( $localtime )
				return date($G_datetime_format, $timestamp);
			else
				return gmdate($G_datetime_format, $timestamp);
		}
		else return "";
	}

	function text_datetime_to_timestamp( $text_date )
	{
		global $G_current_language;

		switch( $G_current_language )
		{
			case "es": // dd/mm/yyyy hh:nn:ss
				$year = substr($text_date, 6, 4);
				$month = substr($text_date, 3, 2);
				$day = substr($text_date, 0, 2);
				break;

			case "en": // mm/dd/yyyy hh:nn:ss
				$year = substr($text_date, 6, 4);
				$month = substr($text_date, 0, 2);
				$day = substr($text_date, 3, 2);
				break;
			
			default:   // yyyy-mm-dd hh:nn:ss
				$year = substr($text_date, 0, 4);
				$month = substr($text_date, 5, 2);
				$day = substr($text_date, 8, 2);
				break;
		}
		
		$hour = substr($text_date, 11 ,2);
		$min = substr($text_date, 14, 2);
		$sec = substr($text_date, 17, 2);
		
		return mktime($hour, $min, $sec, $month, $day, $year);
	}
	
	function mysql_to_csv( $sql, $with_echo )
	{
		$csv = "";
		$rs_result = mysql_query($sql);
		
		if( $rs_result )
		{
			$line = "";
			for($i=0;$i<mysql_num_fields($rs_result);$i++) $line .= strtoupper(mysql_field_name($rs_result, $i)).",";
			if( strlen( $line ) > 0 ) $line = substr($line, 0, strlen($line)-1);
			$line .= "\r\n";
			$csv = $line;
			if( $with_echo ) echo $line;
			
			while( $rw_result = mysql_fetch_assoc( $rs_result ) )
			{
				$i = 0;
				$line = "";
				foreach($rw_result as $name => $value)
				{
					$info = mysql_fetch_field($rs_result, $i);
					if( $info->type == "string" || $info->type == "blob" )
						$line .= "\"$value\",";
					else
						$line .= "$value,";
					$i++;
				}
				if( $i > 0 ) $line = substr($line, 0, strlen($line)-1);
				$line .= "\r\n";
				
				if( $with_echo )
					echo $line;
				else
					$csv .= $line;
			}
		}

		if( $with_echo )
			return "";
		else
			return $csv;
	}
	
	function msgbox_redirect($title, $message, $redirect)
	{
		echo "<html><head><title>$title</title></head><body onload=\"alert('$message'); document.location.href='$redirect';\"></body></html>";
	}
	
	function entry_filter( $message )
	{
		return str_replace(array($G_fslb_user_file_token_separator, "\"", "'", "\\", "`"), "", $message);
	}
	
    function RandomU($length=6,$n=TRUE)
    {
              $source = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
              if($n==1) $source .= '23456789';
              if($length>0){
              $rstr = "";
              $source = str_split($source,1);
              for($i=1; $i<=$length; $i++){
              mt_srand((double)microtime() * 1000000);
              $num = mt_rand(1,count($source));
              $rstr .= $source[$num-1];
     }

     }
              return $rstr;
     }

     function RandomP($length=6,$n=TRUE)
     {
              $source = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
              if($n==1) $source .= '23456789';
              if($length>0){
              $rstr = "";
              $source = str_split($source,1);
              for($i=1; $i<=$length; $i++){
              mt_srand((double)microtime() * 1000000);
              $num = mt_rand(1,count($source));
              $rstr .= $source[$num-1];
     }

     }
              return $rstr;
     }
	 
    function srv_connchk() {
	
        $sql = mysql_query("SELECT * FROM settings WHERE id='1'");
        
		if( $sql ) {
        $row = mysql_fetch_assoc($sql);
		return $row["srv_connchk"];
	    }
    }
	
    function select_code() {
	
        $sql = mysql_query("SELECT * FROM country ORDER BY id ASC");
        echo "<select name=\"code\" style=\"width: 90px\" onchange=\"document.getElementById('phone').value= this.value\">";
        if ($sql) {
        echo "<option value=\"\">Area Code</option>";
        while ($datos = mysql_fetch_array($sql)) {
        echo "<option value=\"".$datos["code"]."\">".$datos["name"]." ".$datos["code"]."</option>";
        }
        } else {
        echo "<option value=\"-1\">Error en la consulta</option>";
        }
        echo "</select> ";
	 }
	 
    function send_date() {
	
		$date = date("Y-m-d");
        $sql = mysql_query("SELECT * FROM settings WHERE id='1'");
        $row = mysql_fetch_assoc($sql);
		
        if($row["send_date"] == $date){
		
        $Days = 2;

        $result = mysql_query("SELECT * FROM accounts WHERE UNIX_TIMESTAMP(expires) < (UNIX_TIMESTAMP() + (60*60*24*$Days))");
        $num = mysql_num_rows($result);
	
        $i=0;
        while ($i < $num)
        {
        $account=mysql_result($result,$i,"account");
        $expires=mysql_result($result,$i,"expires");
        $email=mysql_result($result,$i,"email");
	    $lang=mysql_result($result,$i,"lang");

        // Comienzo del envio de email
	    if($lang == "es"){
        $to = $email;
        $subject = "Su cuenta esta por vencer";
        $message = "<p><b>!! Notificacion de cuenta a vencer !!</b></p>
        Su cuenta <b>".$account."</b> esta proximo a vencer, le quedan 2 d�as de servicio, debe efectuar su pago antes de <b>".$expires."</b> para evitar la interrupcion de su servicios. 
	    <br />
        <p>&nbsp;</p>   
        <p>Pongase en contacto con su proveedor.</p>";
        $headers = "MIME-Version: 1.0\nContent-type: text/html; charset=iso-8859-1\nX-Priority: 3\nX-MSMail-Priority: Normal\nX-Mailer: php\nFrom: ".$to."\n";
        mail($to, $subject, $message, $headers);
	    }else{
        $to = $email;
        $subject = "Your account is about to expire";
        $message = "<p><b>!! Notification account to expire !!</b></p>
        Your Account <b>".$account."</b> is about to expire, you are two days of service, you must make your payment before  <b>".$expires."</b>  To avoid interruption of your service.
	    <br />
        <p>&nbsp;</p>   
        <p>Please contact your provider.</p>";
        $headers = "MIME-Version: 1.0\nContent-type: text/html; charset=iso-8859-1\nX-Priority: 3\nX-MSMail-Priority: Normal\nX-Mailer: php\nFrom: ".$to."\n";
        mail($to, $subject, $message, $headers);
	    }
	    // Fin del envio del email
        $i++;
        }
	 update_send_date();
	 }
	}
	
    function update_send_date() {
	
        $startdate = date("Y-m-d");
        $enunmes = explode("-", $startdate);
        $sumaunmes = mktime(0, 0, 0, date($enunmes[1]), date($enunmes[2]+ 1), date($enunmes[0]));
        $findate = date("Y-m-d", $sumaunmes);
		
		mysql_query("UPDATE settings SET send_date ='".$findate."' WHERE id='1'") or sqlerror();
	}

    function removeuser()
    {
		 
		$Days = 60;
		  
        $func = mysql_query("select srv_connchk from settings WHERE id='1' ") or sqlerror();
		
        if( mysql_fetch_assoc( $func ) == "C" ) {
			
        mysql_query("DELETE FROM accounts WHERE UNIX_TIMESTAMP(expires) < (UNIX_TIMESTAMP() - (60*60*24*$Days))") or sqlerror();
			
		} else {
			
		$result = mysql_query("select account from accounts WHERE UNIX_TIMESTAMP(expires) < (UNIX_TIMESTAMP() - (60*60*24*$Days))") or sqlerror();
        while( $row =  mysql_fetch_assoc( $result ) ){  
		   
        $account =  $row['account'];
		
        mysql_query("DELETE FROM accounts WHERE account='".$account."'") or sqlerror();
   
		global $G_fslb_server;
		global $G_fslb_integration_calls_port;
		global $G_fslb_integration_calls_username;
		global $G_fslb_integration_calls_password;
		
		$server = new http_client($G_fslb_server, $G_fslb_integration_calls_port);
		$server->set_authorization($G_fslb_integration_calls_username, $G_fslb_integration_calls_password);
		$server->get("/user-management.fssp?integration=1&delete=1&delete_username=$account" );
		
      }
     }
    }	
?>
