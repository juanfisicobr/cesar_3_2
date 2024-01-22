<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="pragma" content="no-cache">
<link href="themes/<?php echo($G_theme); ?>/style.css" type="text/css" rel="stylesheet">
<title><?php grse("APP_NAME"); ?></title>
</head>

<body>
<form name="root" action="<?php echo( $_INCLUDER ); ?>" method="<?php echo( $_METHOD ); ?>">
<table border="0" width="100%" id="tblHeader">
	<tr><td align="center"><img src="themes/<?php echo($G_theme); ?>/header.gif" border="0" alt="Header"></td></tr>
	<tr><td align="center"><font size="1"><?php grse("HEADER_SUBTITLE"); ?></font></td></tr>
	<tr><td height="5"></td></tr>
<?php if( $G_username == "" ) { 
	
	
?></select></td>
<?php } else { ?>
	<tr><td align="center">
		<table class="infoBar">
			<tr>
				<td style="font-size: 10px" align="left"><?php
						$infobar_username = ($G_username == "" ? "[".grs("INFOBAR_UNAUTHENTICATED")."]" : $G_username);
						
						switch( $G_user_type )
						{
							case "ROOT": $infobar_type_description = grs("MANAGEMENT_USERS_ROOT"); break;
							case "SRSLR": $infobar_type_description = grs("MANAGEMENT_USERS_ADMINISTRATOR"); break;
							case "RSLR": $infobar_type_description = grs("HOME_RESELLER"); break;
							default: $infobar_type_description = grs("MANAGEMENT_USERS_UNKNOWN");
						}

						if( $G_username_owner <> "" )
							$infobar_username_owner = " | ".grs("RESELLER_MANAGEMENT_OWNER").": <b>$G_username_owner</b>";
						else
							$infobar_username_owner = "";
						
						echo(grs("INFOBAR_USERNAME").": <b>".$infobar_username. "</b> | ".grs("INFOBAR_USERNAME_NAME").": <b>".$G_username_name."</b> | ".grs("INFOBAR_TYPE").": <b>".$infobar_type_description."</b>$infobar_username_owner | ".grs("INFOBAR_LAST_LOGIN").": <b>"._datetime_format($G_last_login, TRUE)."</b>");
					?></td>
				<td align="right"><select onChange="location.href='<?php echo( $_INCLUDER ); ?>?language=' + this.value;" name="language"><?php
					foreach( $G_languages as $language => $name )
					{
						if( $language == $G_current_language ) $selected = "selected "; else $selected = "";
						echo("<option ".$selected."value=\"$language\">$name</option>");
					}
				?></select></td>
			</tr>
		</table>
	</td></tr>
<?php include("menu.php"); ?>
<?php } ?>
	<tr><td height="10"></td></tr>
	<tr>
		<td align="center"><hr></td>
	</tr>
</table>

<div align="center">
<table border="0" width="1080" id="tblBody" class="mainArea" height="450">
<tr>
<td align="left" valign="top">
