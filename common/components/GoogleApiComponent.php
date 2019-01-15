<?php
namespace common\components;

use Yii;
use yii\base\Component;
use google\apiclient;

set_include_path(Yii::getAlias('@vendor') . '/google/apiclient/src');
class GoogleApiComponent extends Component {

	

	/**
	* This action will authenticate user with Google for the very first time
	*
	* @author Saed Yousef 
	* @param $code <Return from google APIs>
	* @return strin/array|mixed
	*/
	public function authenticateClient($code = null)
	{
		if (session_status() == PHP_SESSION_NONE) {
		    session_start();

		    // Set the expiration date of the access token returned from Google APIs
		    $_SESSION['expire_date'] = date("Y-m-d H:i:s", strtotime("+55 minutes"));
		}

		$credentials = Yii::getAlias('@common'). '/credentials.json';
		$client = new \Google_Client();
		$client->setAuthConfig($credentials);
		$client->setApplicationName("Syarah Task");
		$client->setAccessType('offline');
		$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
		
		$client->addScope(\Google_Service_Drive::DRIVE);

		// If there aready non expired token reuse it
		if (isset($_SESSION['access_token']) && (date('Y-m-d H:i:s') < $_SESSION['expire_date'])) {
		  $client->setAccessToken($_SESSION['access_token']);
		  $drive = new \Google_Service_Drive($client);
		  $files = $drive->files->listFiles([]);
		  return ['files' => $files, 'authUrl' => false];
		}

		// Reset the expiration date of the access token
		if(date('Y-m-d H:i:s') < $_SESSION['expire_date'])
			$_SESSION['expire_date'] = date("Y-m-d H:i:s", strtotime("+55 minutes"));
		
		$authUrl = $client->createAuthUrl();
		return ['authUrl' => $authUrl];
	}

	/**
	* This action will use the access_token/code already generate by Google APIs
	*
	* @author Saed Yousef 
	* @param $code
	* @param $access_token
	* @return url/array|mixed
	*/
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
				if(isset($auth['error']))
				{
					$auth_url = $client->createAuthUrl();
		  			return ['authUrl' => $auth_url];
				}
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