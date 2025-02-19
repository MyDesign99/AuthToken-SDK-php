<?php 
/**
 *  Copyright (c) 2025 MyDesign99 LLC
 */

namespace MD99_SDK;


class JwtToken
{
   public static function build ($payloadAsArray, $secret)
   {
		$hdr64   = self::headerAs64 ();
		$pay64   = self::urlEncodeArrayToBase64 ($payloadAsArray);
		$sign64	= self::sign ($hdr64, $pay64, $secret);
		
		$token   = $hdr64 . "." . $pay64 . "." . $sign64;
		
      return $token;
   }


   public static function sign ($hdr64, $pay64, $secret)
   {
		$fullStr = $hdr64 . "." . $pay64;
		$signStr = hash_hmac ("sha256", $fullStr, $secret, true);
		$sign64	= self::urlEncodeStrToBase64 ($signStr);

      return $sign64;
   }


   protected static function headerAs64 ()
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