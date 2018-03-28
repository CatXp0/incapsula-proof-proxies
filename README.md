## Description
This script allows bots to get to content blocked by the Incapsula bot mitigator. It manages this by validating a list of proxies using Incapsula's own algorithm. 

After the validation, the proxies should be ready to be used for any request on the domain blocked by Incapsula.

## Prerequisites
An array of proxies is needed, the script validates them in order to bypass Incapsula.
The proxies must have these parameters:
```
$arrProxy = array("IP:PORT" => "192.168.0.1:8080", "PROTOCOL" => "HTTP");
```

## Usage
The class needs to be instantiated with the array of proxies to be validated and the domain of the site blocked by Incapsula:
```
$objIncapsulaProxyValidator = new IncapsulaProxyValidator($arrAllProxies,"https://domain-name");
```
You can also instantiate with custom paths for cookies and raw_data folders. 
*  "cookies" folder is used to store cookies for each proxy that is validated
*  "raw_data" folder is used to store pcntl_fork() data
```
$objIncapsulaProxyValidator = new IncapsulaProxyValidator($arrAllProxies,"https://domain-name", $strCustomCookiesFolderPath, $strCustomForkDataFolderPath);
```

After this, the validator needs to be started:
```
$arrLegitProxies = $objIncapsulaProxyValidator->_createLegitProxies();
```
$arrLegitProxies is an array of proxies that have been validated by Incapsula and are ready to be used for requests.