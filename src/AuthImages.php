<?php
/**
 *  Copyright (c) 2025 MyDesign99 LLC
 */

namespace MD99_SDK;


class AuthImages
{
	const		REMOTE_URL			= "https://mydesign99.com";
	const 	AUTH_TOKEN_URL		= "/api/get/authtoken";
	const		ERROR_IMG_URL		= "/images/image_not_found.png";
	const		CACHE_FILENAME		= "md99_data.txt";

   // ---------------------------------------------------------------
	
	public static function createImageURL ($publicKey, $token, $value, $longAssetName)
   {
		$assetName = self::stripAssetName ($longAssetName);
	
		return (self::REMOTE_URL . "/get/" . $publicKey . "/" . $token . "/" . $value . "/" . $assetName . ".png");
   }
	

	public static function errorImageURL ()
	{
		return self::REMOTE_URL . self::ERROR_IMG_URL;
	}
	

   public static function getMD99AuthToken ($publicKey, $secretKey)
   {
		$tokenObj = self::readTokenFromCache ();
		if ($tokenObj['success'])
			return self::formatTokenSuccess ($tokenObj['token'], 0);

		// not found in cache, perform the POST request to the remote MD99 server
		
		// build all the POST params
		$payloadAr	= array ('client_id' => $publicKey);
		$jwtString	= self::buildJWT ($payloadAr, $secretKey);
		$data			= array ("jwt" => $jwtString);
		$url			= self::REMOTE_URL . self::AUTH_TOKEN_URL;
		$httpAr		= array ('header'  => "Content-type: application/x-www-form-urlencoded\r\n",
									'method'  => 'POST',
									'content' => http_build_query($data));
		$options 	= array ('http' => $httpAr);		// use key 'http' even if you send the request to https:
		$context		= stream_context_create ($options);
		
		// make the http call
		$httpResultStr = file_get_contents ($url, false, $context);
		if ($httpResultStr === false) 
			return self::formatTokenError ("Unexpected HTTP error");

		$tokenData = self::parseTokenFromMD99Result ($httpResultStr);
		if (! $tokenData['success'])
			return $tokenData;

		self::writeTokenToCache ($tokenData);

		$tokenData['expires'] = 0;
		return $tokenData;
   }


   public static function processAll ($publicKey, $secretKey, $value, $longAssetName)
   {
		$tokenObj = self::getMD99AuthToken ($publicKey, $secretKey);
		if ($tokenObj['success'] != "1")
			return self::errorImageURL ();
		
		return self::createImageURL ($publicKey, $tokenObj['token'], $value, $longAssetName);
	}
	
   // ---------------------------------------------------------------

   protected static function parseTokenFromMD99Result ($httpResultStr)
   {
		// httpResultStr contains "is_success" plus "data" or "err_msg"

		$httpResultAr = json_decode ($httpResultStr, true);
		if (! $httpResultAr)
			return self::formatTokenError ("Invalid response format (j)");

		if (! isset ($httpResultAr['is_success']))
			return self::formatTokenError ("Invalid response format (s)");

		if ($httpResultAr['is_success'] != "1") {
			if (! isset ($httpResultAr['err_msg']))
				return self::formatTokenError ("Invalid response format (em)");
			return self::formatTokenError ($httpResultAr['err_msg']);
		}

		if (! isset ($httpResultAr['data']))
			return self::formatTokenError ("Invalid response format (s)");

		$responseData = $httpResultAr['data'];

		// responseData contains "client_id", "token", "expires"

		if (! isset ($responseData['token']))
			return self::formatTokenError ("Invalid response format (tok)");
		if (! isset ($responseData['expires']))
			return self::formatTokenError ("Invalid response format (exp)");

		return self::formatTokenSuccess ($responseData['token'], $responseData['expires']);
   }


   protected static function formatTokenSuccess ($token, $expires)
   {
		$retAr = array ('success' => true, 'token' => $token, 'expires' => $expires);
		return $retAr;
   }


   protected static function formatTokenError ($msg) 
   {
		return array ("success" => false, "message" => $msg);
   }
   

   protected static function stripAssetName ($name)
   {
		$name = str_replace (" " , "-", $name);						// replace spaces with dashes
		$name = strtolower($name);											// change to all lower case
		$name = preg_replace('~[^-a-z0-9_]+~', '', $name);			// keep only dash, underscore, letters and numbers
		$name = preg_replace('/([-])\1+/', '$1', $name);			// remove duplicate dashes	
		$name = trim($name, "-");											// removes dashes from the beginning and end
		return $name;
	}
	
   // ---------------------------------------------------------------
	//		File Cache utility functions
   // ---------------------------------------------------------------
   
   protected static function writeTokenToCache ($dataAr)
   {
		$jsonStr = json_encode ($dataAr);
		
		$cacheFile = fopen(self::CACHE_FILENAME, "w");
		if (! $cacheFile)
			return;
		
		fwrite ($cacheFile, $jsonStr);
		fclose ($cacheFile);
   }

   
   protected static function readTokenFromCache ()
   {
		$errRetObj = array ("success" => false);
		
		if (! file_exists (self::CACHE_FILENAME))
			return $errRetObj;
		
		$cacheFile = fopen(self::CACHE_FILENAME, "r");
		if (! $cacheFile)
			return $errRetObj;
		
		$jsonStr = fread ($cacheFile, filesize (self::CACHE_FILENAME));
		fclose ($cacheFile);

		if (! $jsonStr)
			return $errRetObj;
		
		$jsonAr = json_decode ($jsonStr, true);
		if (! $jsonAr)
			return $errRetObj;

		if (! isset ($jsonAr['expires']))
			return $errRetObj;
		
		$curDT		= date_create();
		$curSeconds	= date_timestamp_get ($curDT);
		if ($curSeconds > $jsonAr['expires'])
			return $errRetObj;

		$jsonAr['success'] = true;		
		return $jsonAr;
   }

   // ---------------------------------------------------------------
	//		JWT utility functions
   // ---------------------------------------------------------------
   protected static function buildJWT ($payloadAsArray, $secret)
   {
		$hdr64   = self::jwtHeaderAs64 ();
		$pay64   = self::urlEncodeArrayToBase64 ($payloadAsArray);
		$sign64	= self::signJWT ($hdr64, $pay64, $secret);
		
		$token   = $hdr64 . "." . $pay64 . "." . $sign64;
		
      return $token;
   }


   protected static function signJWT ($hdr64, $pay64, $secret)
   {
		$fullStr = $hdr64 . "." . $pay64;
		$signStr = hash_hmac ("sha256", $fullStr, $secret, true);
		$sign64	= self::urlEncodeStrToBase64 ($signStr);

      return $sign64;
   }


   protected static function jwtHeaderAs64 ()
   {
		$hdrAr = array ("alg" => "HS256", "typ" => "JWT");
		return self::urlEncodeArrayToBase64 ($hdrAr);
   }


	protected static function urlEncodeArrayToBase64 ($ar)
	{
		$str = json_encode ($ar);
		return self::urlEncodeStrToBase64 ($str);
	}
	
	protected static function urlEncodeStrToBase64 ($str)
	{
		$b64 = base64_encode ($str);
		$b64 = strtr ($b64, '+/', '-_');
		return trim ($b64, '=');
	}
	
}