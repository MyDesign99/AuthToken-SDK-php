
# Image Authentication SDK from MyDesign99

Use the Image Authentication SDK from MyDesign99 on your server to build fully formatted URL's for your MyDesign99 images.

![MyDesign99 logo](logo.png "MyDesign99 logo")

> ** **
> **To be used on your PHP server**
> ** **

## OVERVIEW

MyDesign99 requires that image requests use authenticated tokens in the fully-formed URL as a part of your own designs and websites. This SDK is used to retrieve the latest token for your account and to format the URL.

Example:
```
https://mydesign99.com/abcd1234/wxyz5678asdf9876/78/first-asset.png
```

The normal usage of the SDK is to have it embedded on your own server.  Then you would use your own database to retrieve values and the SDK to retrieve an Auth Token. MyDesign99 provides you with a Public Key and a Secret Key. Through your MD99 account, you can create custom designs (graphics). The combination of Public Key, Auth Token, Value, and Asset Name will form a valid URL for an **img** tag to be placed on your own web pages, reports or PDF files.

## USAGE

There are 4 function in this SDK package.

```
getMD99AuthToken ($publicKey, $secretKey)
createImageURL   ($publicKey, $token, $value, $assetName)
errorImageURL    ()
processAll       ($publicKey, $secretKey, $value, $assetName)
```

getMD99AuthToken ($publicKey, $secretKey)
> Request the current authentication token. The developer's public and secret keys are required.

createImageURL ($publicKey, $token, $value, $assetName)
> Create a well-formed URL for the requested image. "clientID" is the publicKey from your account. "token" is retrieved using the "getMD99AuthToken" function. "value" is the numeric value to be displayed in the MD99 graphic. "assetName" is the name of the developer's MD99 asset (graphic) to be displayed.

errorImageURL ()
> Create a well-formed URL for the standard MD99 error image.

processAll (publicKey, secretKey, value, assetName)
> Request the current authentication token and return the well-formed image URL. In case of an error, the Error Image URL is returned.


# Public and Secret Keys

These keys are accessible on the client portal in the MyDesign99 website.

Also, Demo keys can be requested for short-term use for developers trying out our service using the Demo (explained below).

# DEMO

Check out our PHP Server Demo on Github

[github.com/MyDesign99/Server-Demo-php](https://github.com/MyDesign99/Server-Demo-php)

