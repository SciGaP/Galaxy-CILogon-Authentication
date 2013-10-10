<?php
$ini_array = parse_ini_file("properties.ini");

$callback_url = $ini_array[home_url];
$conskey = $ini_array[cilogon_id];
$dn = $ini_array[dn];

$pkeyfile = $ini_array[oauth_privkey_file];
$authzfile = $ini_array[authorized_users_file];

$req_url = 'https://cilogon.org/oauth/initiate';
$auth_url = 'https://cilogon.org/delegate';
$acc_url = 'https://cilogon.org/oauth/token';
$api_url = 'https://cilogon.org/oauth/getcert';


function pem2der($pem_data) {
  $begin = "-----BEGIN CERTIFICATE REQUEST-----";
  $end   = "-----END CERTIFICATE REQUEST-----";
  $pem_data = substr($pem_data, strpos($pem_data, $begin)+strlen($begin));    
  $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
  return $pem_data;
}

$privkey = openssl_pkey_get_private("file://".$pkeyfile);
$csrt = openssl_csr_new($dn, $privkey);
openssl_csr_export($csrt, $csrout);
$conscsr = pem2der($csrout);
//var_dump($conscsr);

session_start();

// In state=1 the next request should include an oauth_token.
// If it doesn't go back to 0
if(!isset($_GET['oauth_token']) && $_SESSION['state']==1) $_SESSION['state'] = 0;
try {
  $oauth = new OAuth($conskey,'',OAUTH_SIG_METHOD_RSASHA1,OAUTH_AUTH_TYPE_URI);
  $oauth->setRSACertificate(file_get_contents('oauth-privkey.pk8'));
  //$oauth->enableDebug();
  //file_put_contents('php://stderr', print_r($oauth->debugInfo, TRUE));
  if(!isset($_GET['oauth_token']) && !$_SESSION['state']) {
    $oauth->fetch($req_url, array("oauth_callback" => $callback_url, "certreq" => $conscsr));
    parse_str($oauth->getLastResponse(), $request_token_info);
    //var_dump($request_token_info);
    $_SESSION['secret'] = $request_token_info['oauth_token_secret'];
    $_SESSION['state'] = 1;
    header('Location: '.$auth_url.'?vo=iugalaxy&oauth_token='.$request_token_info['oauth_token']);
    //file_put_contents('php://stderr', print_r($oauth->debugInfo, TRUE));
    exit;
  } else if($_SESSION['state']==1) {
    $oauth->setToken($_GET['oauth_token'],$_SESSION['secret']);
    $access_token_info = $oauth->getAccessToken($acc_url);
    $_SESSION['state'] = 2;
    $_SESSION['token'] = $access_token_info['oauth_token'];
    $_SESSION['secret'] = $access_token_info['oauth_token_secret'];
  } 
  $oauth->setToken($_SESSION['token'],$_SESSION['secret']);
  $oauth->fetch($api_url);
  $user_cert_info = $oauth->getLastResponse();
  //var_dump($user_cert_info);
  $beginpem = "-----BEGIN CERTIFICATE-----";
  $usercert = strstr($user_cert_info, $beginpem); 
  //var_dump($usercert);
  $cert_data = openssl_x509_parse($usercert);
  //var_dump($cert_data);
  $email =  $cert_data["extensions"]["subjectAltName"];
  $user = substr($email, 6);
  //$_SESSION['state']=3;
  //if authorized user, forward to login, otherwise to register
  if(strpos(file_get_contents($authzfile),$user) === false) {
    header('Location: /register_galaxy.php');
  } else {
    $_SESSION['GalaxyUser']=$user;
    setrawcookie('GalaxyUser', $_SESSION['GalaxyUser']); 
    //virtual('/login_galaxy.php');
    header('Location: /login_galaxy.php');
  }
} catch(OAuthException $E) { 
  print_r($E);
}
?>
