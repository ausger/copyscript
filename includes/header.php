<?php
$mage_url = 'http://ausger.dev:8888/de/api/soap/?wsdl'; 
$mage_user = 'ausgerdev'; 
$mage_api_key = '12345678'; 
$soap = new SoapClient( $mage_url ); 

if($_POST['action']=='connect'){
	$session_id = $soap->login( $mage_user, $mage_api_key );
	if(strpos($session_id, "Fatal Error")!==false){
		die();
	}
	setcookie("tools_session", $session_id, time()+3600);
}

if (isset($_COOKIE["tools_session"])){
  $session_id = $_COOKIE["tools_session"];
}

if($session_id){
  
  	if($_POST['action']=='disconnect'){
		$soap->endSession( $session_id );
		setcookie("tools_session", "", time()-3600);
		echo "Connection Ended";
		?>
        <form action="index.php" method="post">
<input name="action" type="hidden" value="connect" />
<input name="Connect" type="submit" value="Connect"  />
</form>
        <?php
		die();
	}
  
  $resources = $soap->resources( $session_id );
  if( is_array( $resources ) && !empty( $resources )) {
	 print_r($resource);
  }else{
	  setcookie("tools_session", "", time()-3600);
	  die('Session ID Bad');
  }
 
  
  
}else{
  echo "Tools Connection Session Not Found<br />";
?>
<form action="index.php" method="post">
<input name="action" type="hidden" value="connect" />
<input name="Connect" type="submit" value="Connect"  />
</form>
<?php die(); }?>