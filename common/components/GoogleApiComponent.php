<?php
namespace common\components;

use Yii;
use yii\base\Component;
use google\apiclient;

set_include_path(Yii::getAlias('@vendor') . '/google/apiclient/src');
class GoogleApiComponent extends Component {

	

	public function authenticateClient($code = null)
	{
		if (session_status() == PHP_SESSION_NONE) {
		    session_start();
		}

		$credentials = Yii::getAlias('@common'). '/credentials.json';
		$client = new \Google_Client();
		$client->setAuthConfig($credentials);
		$client->setApplicationName("Syarah Task");
		$client->setAccessType('offline');
		$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
		//$client->setRedirectUri($redirect_uri);
		$client->addScope(\Google_Service_Drive::DRIVE);

		if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
		  $client->setAccessToken($_SESSION['access_token']);
		  $drive = new \Google_Service_Drive($client);
		  $files = $drive->files->listFiles([]);
		  return ['files' => $files, 'authUrl' => false];
		}

		$authUrl = $client->createAuthUrl();
		return ['authUrl' => $authUrl];
	}

	public function retrieveAllFiles($code = null, $access_token = null)
	{
		$credentials = Yii::getAlias('@common'). '/credentials.json';
		$client = new \Google_Client();
		$client->setAuthConfig($credentials);
		$client->setApplicationName("Syarah Task");
		$client->setAccessType('offline');
		$client->addScope(\Google_Service_Drive::DRIVE);


		if(empty($code) && empty($access_token))
		{
		  	$auth_url = $client->createAuthUrl();
		  	return ['authUrl' => $auth_url];
		}
		else 
		{
			if(!empty($code) && empty($access_token))
			{
				$auth = $client->authenticate($code);
				$_SESSION['access_token'] = $client->getAccessToken();
				$client->setAccessToken($client->getAccessToken());
			}else
			  	$client->setAccessToken($access_token);

			  	$drive = new \Google_Service_Drive($client);
			  	$files = $drive->files->listFiles([]);
			  	return ['files' => $files, 'authUrl' => false];
		}
	}
}