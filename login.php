<?php
	$G_login_page = TRUE;
	include("global.php");
	
	// -- check for login parameters
	if( isset($_REQUEST["username"]) and isset($_REQUEST["password"]) )
	{
		$_REQUEST["username"] = entry_filter($_REQUEST["username"]);
		$_REQUEST["password"] = entry_filter($_REQUEST["password"]);

		if( $_REQUEST["username"] == "" or $_REQUEST["password"] == "" )
		{
			$status_message = grs("LOGIN_NO_INFO");
		}
		else
		{
			if( query_scalar("select count(*) from users where username = '".
					$_REQUEST["username"]."' and password = '".
					$_REQUEST["password"]."' and status = 'A'") > 0 )
			{
				$status_message = "";

				$_SESSION["username"] = $_REQUEST["username"];

				$_SESSION["username_name"] = query_scalar("select name from users where username = '".
						$_REQUEST["username"]."'");

				$_SESSION["user_type"] = query_scalar("select type from users where username = '".
						$_REQUEST["username"]."'");
				
				$_SESSION["user_account_prefix"] = query_scalar("select accounts_prefix from users ".
						"where username = '".$_REQUEST["username"]."'");

				$_SESSION["username_owner"] = query_scalar("select username_owner from users ".
						"where username = '".$_REQUEST["username"]."'");
				
				$G_username = $_SESSION["username"];
				$G_username_name = $_SESSION["username_name"];
				update_login_info();

				if( $_SESSION["user_type"] == "ROOT" )
					header("Location: root-super-reseller-management.php");
				else if( $_SESSION["user_type"] == "SRSLR" )
					header("Location: sr-reseller-management.php");
				else
					header("Location: reseller-home.php");
				
				die;
			}
			else $status_message = grs("LOGIN_INVALID");
		}
	}
	else $status_message = "";
	
	$_METHOD = "post";
	$_INCLUDER = "login.php";
	include("header.php");

	
?>
<table border="0" width="100%" id="tblCategory">
	<tr>
		<td align="center">
			<table class="subtleBackground" style="padding: 50px; border: none; width: 340px; height: 120px; box-shadow: 40px 40px 40px #d44e59;"
 border="0"><tbody><tr><td>
				<table style="margin-left: 10px; margin-top: 10px;" border="0">
					<tr>
						<td></td>
						<td height="15"><?php grse("LOGIN_MESSAGE"); ?>.</td>
					</tr>
					<tr>
						<td colspan="2" height="5"></td>
					</tr>
					<tr>
						<td class="fieldTitle"><?php grse("LOGIN_USERNAME"); ?>:</td>
						<td><input type="text" name="username" size="63"></td>
					</tr>
					<tr>
						<td class="fieldTitle"><?php grse("LOGIN_PASSWORD"); ?>:</td>
						<td><input type="password" name="password" size="63"></td>
					</tr>
					<tr>
						<td></td>
						<td><input type="submit" name="submit" size="55"> <value="<?php grse("LOGIN_SUBMIT"); ?>"></td>
					</tr>
					<tr>
						<td colspan="2" height="10"></td>
					</tr>
					<tr>
						<td colspan="2" class="errorMessage"><?php if( $status_message <> "" ) echo($status_message."."); ?></td>
					</tr>
					<?php if( $status_message <> "" ) { ?>
					<tr>
						<td colspan="2" height="15"></td>
					</tr>
					<?php } ?>
				</table>
			</td></tr></table>
		</td>
	</tr>
</table>
<?php include("footer.php"); ?>