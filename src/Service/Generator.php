<?php
/**
 * User: catxp0
 * Date: 7/24/19
 * Time: 6:25 PM
 */

class Generator
{
    const SAME_PROXY_RETRIES = 5;
    const CURL_TIMEOUT = 20;
    const PROXY_VERIFY_ENDPOINT = '_Incapsula_Resource?'; //SWKMTFSR=1&e=

    /** @var array */
    private $proxies = [];
    /** @var string */
    private $domain;
    /** @var bool */
    private $verbose;
    /** @var int */
    private $maxProxiesPerFork;
    /** @var string */
    private $cookieFolderPath;
    /** @var string */
    private $rawDataFolder;
    /** @var string  */
    private $proxyVerifyLink;

    /* Static link, the domain+this resource contains the functions needed, but obfuscated.
     * This is the link to check in case the plugin keeps failing, it may be because something
     * changed inside the obfuscated functions.
     */
    private $cookieConstructResource = '_Incapsula_Resource?SWJIYLWA=2977d8d74f63d7f8fedbea018b7a1d05&ns=2';

    private $defaultCookieConfig = array(
        "navigator"=>"exists",
        "navigator.vendor"=>"value",
        "navigator.appName"=>"value",
        "navigator.plugins.length==0"=>"value",
        "navigator.platform"=>"value",
        "navigator.webdriver"=>"value",
        "platform"=>"plugin_extentions",
        "ActiveXObject"=>"exists",
        "webkitURL"=>"exists",
        "_phantom"=>"exists",
        "callPhantom"=>"exists",
        "chrome"=>"exists",
        "yandex"=>"exists",
        "opera"=>"exists",
        "opr"=>"exists",
        "safari"=>"exists",
        "awesomium"=>"exists",
        "puffinDevice"=>"exists",
        "__nightmare"=>"exists",
        "spawn"=>"exists",
        "emit"=>"exists",
        "Buffer"=>"exists",
        "domAutomation"=>"exists",
        "domAutomationController"=>"exists",
        "_Selenium_IDE_Recorder"=>"exists",
        "document.__webdriver_script_fn"=>"exists",
        'document.$cdc_asdjflasutopfhvcZLmcfl_'=>"exists",
        "process.version"=>"exists",
        "navigator.cpuClass"=>"exists",
        "navigator.oscpu"=>"exists",
        "navigator.connection"=>"exists",
        "navigator.language=='C'"=>"value",
        "window.outerWidth==0"=>"value",
        "window.outerHeight==0"=>"value",
        "window.WebGLRenderingContext"=>"exists",
        "document.documentMode"=>"value",
        "eval.toString().length"=>"value"
    );

    /* user agents to use when making requests*/
    private $userAgents = [
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1309.0 Safari/537.17",
        "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1",
        "Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0",
        "Mozilla/5.0 (Windows NT 5.1; rv:11.0) Gecko Firefox/11.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10; rv:33.0) Gecko/20100101 Firefox/33.0",
        "Mozilla/5.0 (X11; Linux i586; rv:31.0) Gecko/20100101 Firefox/31.0",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246",
        "Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201",
        "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9a3pre) Gecko/20070330",
        "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.9.2a1pre) Gecko",
        "Opera/9.80 (X11; Linux i686; Ubuntu/14.10) Presto/2.12.388 Version/12.16",
        "Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; fr) Presto/2.9.168 Version/11.52",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A",
        "Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2"
    ];

    private $goodProxies = array();

    public function __construct($proxies, $domain, $maxProxiesFork = 15, $cookieFolder = 'cookies', $rawDataFolder = 'raw_data')
    {
        $this->proxies = $proxies;
        $this->domain = $domain;
        $this->maxProxiesPerFork = $maxProxiesFork;
        $this->cookieFolderPath = $cookieFolder;
        $this->rawDataFolder = $rawDataFolder;
    }

    public function _createLegitProxies()
    {
        //creating cookies folder
        if (!is_dir($this->cookieFolderPath))
        {
            mkdir($this->cookieFolderPath, 0777, true);
        }

        //creating raw_data folder
        if (!is_dir($this->rawDataFolder))
        {
            mkdir($this->rawDataFolder, 0777, true);
        }

        if($this->domain[strlen($this->domain) - 1] !== '/')
            $this->domain .= '/';

        $this->proxyVerifyLink = $this->domain . $this->proxyVerifyLink . self::PROXY_VERIFY_ENDPOINT;


        $chunksNumber = round(count($this->proxies) / $this->maxProxiesPerFork);
        $procChunks = array_chunk($this->proxies, $chunksNumber, true);
        $proxiesPerProc = count($procChunks);
        print_r("Splitting into $proxiesPerProc subprocesses".PHP_EOL);
        $pids = array();

        for ($proc= 0; $proc < $proxiesPerProc; $proc++)
        {
            $pids[$proc] = pcntl_fork();

            if (0 == $pids[$proc])
            {
                //start process
                $totalProxiesPerProc = count($procChunks[$proc]);

                print_r("Starting process $proc with $totalProxiesPerProc proxies".PHP_EOL);

                $count = 0;
                $goodProxies = array();
                foreach($procChunks[$proc] as $proxy)
                {
//                    $proxy = $this->_arrProxies[$proxyKey];
                    $debugString = "[$proc: $count/$totalProxiesPerProc]";
                    $response = $this->_getPage($this->domain, $proxy);
                    if(!$response)
                    {
                        continue;
                    }

                    // if Incapsula assigns a resource to the request, it means that the client is ready to be validated,
                    // otherwise it has already been categorised as a bot
                    if(preg_match('%(\/_Incapsula_Resource.*?),%s',$response, $resp ))
                    {
                        $response = $this->_getPage($this->domain.'/'.$this->cookieConstructResource, $proxy, false);

                        if(!$response)
                        {
                            print_r("$debugString Proxy ".$proxy['IP:PORT'].' failed! Couldn\'t get '.$this->domain.'/'.$this->cookieConstructResource);
                            continue;
                        }
                    }
                    else
                    {
                        print_r("$debugString Didn't find incapsula resource code in the request with ".$proxy['IP:PORT']);
                        continue;
                    }

                    $obfuscatedCode = $this->_getObfuscatedCode($response);
                    if(!$obfuscatedCode)
                    {
                        print_r("$debugString Didn't find obfuscated code in the request with ".$proxy['IP:PORT'].PHP_EOL);
                        continue;
                    }
                    $timing = array();
                    $timeStart = round(microtime(true)*1000);

                    $decoded = $this->_decodeObfuscatedCode($obfuscatedCode);

                    // get the unique salt that is generated everytime in the obfuscated javascript
                    if(preg_match('%var sl = "(.*?)"%s', $decoded, $matchSalt))
                    {
                        $salt = $matchSalt[1];
                    }
                    else
                    {
                        $this->GetLogger()->WriteMessage("$debugString Couldn't get salt string from " .$this->domain.'/'.$this->cookieConstructResource . ' with ' . $proxy['IP:PORT']);
                        continue;
                    }

//                    $this->_strProxyVerifyLink .= $resources[0];

                    print_r("$debugString Loading data and navigator plugins to create Incapsula cookie for ".$proxy['IP:PORT'].PHP_EOL);

                    //getting random browser navigator
                    $navigators = glob('navigators/*.json', GLOB_NOSORT);
                    $randomNavigator = $navigators[array_rand($navigators)];
                    $jsonRaw = file_get_contents($randomNavigator);
                    $navigator = json_decode($jsonRaw);

                    //getting navigator extensions and properties
                    $extensions = $this->_getNavigatorProperties($navigator, $this->defaultCookieConfig);

                    //setting incapsula cookie
                    $incapsulaCookie = $this->_setIncapsulaCookie($extensions, $proxy['IP:PORT'], $salt);
                    if(!$incapsulaCookie)
                    {
                        print_r("$debugString Couldn't find the cookie for ".$proxy['IP:PORT'].PHP_EOL);
                        continue;
                    }

                    //this request just needs to be done, the request response is not needed
                    $urlReqTest = $this->_getPage( $this->domain.$resp[1], $proxy, $incapsulaCookie);

                    print_r("$debugString Requesting access link #1: " . $this->domain.$resp[1] . ' with '.$proxy['IP:PORT'].PHP_EOL);
                    $accessGranted = false;
                    for($i = 0; $i < self::SAME_PROXY_RETRIES; $i++)
                    {
                        $timing[] = 's:'.(round(microtime(true)*1000)-$timeStart);
                        $response = $this->_getPage($this->domain . $resp[1], $proxy, $incapsulaCookie);
                        if($response)
                        {
                            $accessGranted = true;
                            break;
                        }
                        sleep(0.1);
                    }
                    if(!$accessGranted)
                    {
                        print_r("$debugString Access was not granted in link #1 for ".$proxy['IP:PORT'].'. Proxy may fail...'.PHP_EOL);
                        //                        continue;
                    }

                    //might not be needed
                    //                    $timing[] = 'c:'.(round(microtime(true)*1000)-$timeStart);
                    //                    //simulating a page reload
                    //                    sleep(0.2);
                    //                    $timing[] = 'r:'.(round(microtime(true)*1000)-$timeStart);
                    //                    $response = $this->_getPage(self::PROXY_CHECK_URL.$resources[0] . urlencode('complete ('.implode(',',$timing).')'), $proxy, $incapsulaCookie);
                    //                    if(!$response)
                    //                    {
                    //                        print_r('Access was not granted in link #2 for '.$proxy['IP:PORT']. '. Proxy may fail...');
                    //                    }
                    $accessGranted = false;
                    for($i = 0; $i < self::SAME_PROXY_RETRIES; $i++)
                    {
                        $response = $this->_getPage($this->proxyVerifyLink.((float)rand()/(float)getrandmax()), $proxy, $incapsulaCookie);
                        if($response)
                        {
                            $accessGranted = true;
                            break;
                        }
                    }
                    if(!$accessGranted)
                    {
                        print_r("$debugString Access was not granted in link #3 for ".$proxy['IP:PORT']. '. Proxy may fail...'.PHP_EOL);
                    }

                    //after these requests, we should be able to make normal requests from the goodProxy
                    print_r("$debugString Requesting stations URL: " . $this->domain . ' with '.$proxy['IP:PORT'].PHP_EOL);
                    $cookiedProxyCheck = false;
                    for($i = 0; $i < self::SAME_PROXY_RETRIES; $i++)
                    {
                        sleep(0.1);
                        $checkMainURLResponse = $this->_getPage($this->domain, $proxy);
                        if(strpos($checkMainURLResponse, 'Prodotti') !== false)
                        {
                            print_r("$debugString Proxy ".$proxy['IP:PORT'].' was successful!'.PHP_EOL);
                            $cookiedProxyCheck = true;
                            break;
                        }
                        else
                        {
                            continue;
                        }
                    }
                    if(!$cookiedProxyCheck)
                    {
                        print_r("$debugString Proxy ".$proxy['IP:PORT'].' failed!'.PHP_EOL);
                        continue;
                    }
                    $goodProxies[] = $proxy;
                }

                //save process results
                $pdataFilename = $this->_getProcessPath($proc, '.serial');

                file_put_contents($pdataFilename, serialize($goodProxies));
                print_r("Process $proc finished work.");
                // End process
                exit;
            }
        }

        for ($proc= 0; $proc < $proxiesPerProc; $proc++)
        {
            pcntl_waitpid($pids[$proc], $_, WUNTRACED);

            // check file existence
            $pdataFilename = $this->_getProcessPath($proc, '.serial');
            if (!file_exists($pdataFilename))
            {
                print_r('Partial process file for process '.$proc.' not found'.PHP_EOL);
                continue;
            }

            // verify tht the file has contents
            $response = file_get_contents($pdataFilename);
            unlink($pdataFilename);
            if (!$response)
            {
                print_r('Partial process file for process '.$proc.' could not be read'.PHP_EOL);
                continue;
            }

            // unserialize data from file
            $sliceGoodProxies = unserialize($response);
            if (!$sliceGoodProxies || !is_array($sliceGoodProxies))
            {
                print_r('Partial process file for process '.$proc.' could not be unserialised into an array'.PHP_EOL);
                continue;
            }

            $this->goodProxies = array_merge($sliceGoodProxies, $this->goodProxies);
        }
        print_r('Found '.count($this->goodProxies).' legit proxies!'.PHP_EOL);
        return true;
    }

    private function _getProcessPath($num,$extension)
    {
        if ($num===0)
            return $this->rawDataFolder.'/Incapsula_cookies'.$extension;
        else
            return $this->rawDataFolder.'/Incapsula_cookies_'.$num.$extension;
    }

    /**
     * Function that gets a chunk of hexa obfuscated code
     * that is placed at the end of the response string
     *
     * @param $response
     * @return bool
     */
    private function _getObfuscatedCode($response)
    {
        if(preg_match('%var\s?b\s?=\s?\"(.*?)\"%s', $response, $code))
            return $code[1];
        return false;
    }

    /**
     * Function that decodes the hexa string and returns the
     * deobfuscated javascript code
     *
     * @param $code
     * @return string
     */
    private function _decodeObfuscatedCode($code)
    {
        // decode the hexadecimal string
        $bin = "";
        $codeLength = strlen( $code );
        for ( $i = 0; $i < $codeLength; $i += 2 ) {
            $bin .= pack( "H*", substr( $code, $i, 2 ) );
        }

        return $bin;
    }

    /**
     * Create navigator properties using a random navigator
     * and the config file found in the decoded JS block.
     * WARNING. Change only if you are sure of what you're doing,
     * otherwise the validation won't work
     *
     * @param $navigator
     * @param $config
     * @return array
     */
    private function _getNavigatorProperties($navigator, $config)
    {
        $properties = array();

        foreach($config as $key => $item)
        {
            switch($item)
            {
                case 'exists':
                    switch($key)
                    {
                        case 'navigator':
                        case 'window.WebGLRenderingContext':
                            $properties[] = urlencode($key.'=true');
                            break;
                        case 'chrome':
                        case 'webkitURL':
                        case 'navigator.connection':
                            if(strpos($navigator->userAgent, 'Chrome') !== false)
                                $properties[] = urlencode($key.'=true');
                            else
                                $properties[] = urlencode($key.'=false');
                            break;
                        case 'navigator.oscpu':
                            if(strpos($navigator->userAgent, 'Chrome') !== false)
                                $properties[] = urlencode($key.'=false');
                            else
                                $properties[] = urlencode($key.'=true');
                            break;
                        default:
                            $properties[] = urlencode($key.'=false');
                            break;
                    }
                    break;
                case 'value':
                    switch($key)
                    {
                        case 'navigator.plugins.length==0':
                        case "navigator.language=='C'":
                        case 'window.outerWidth==0':
                        case 'window.outerHeight==0':
                            $properties[] = urlencode($key.'=false');
                            break;
                        case 'document.documentMode':
                            $properties[] = urlencode($key.'=undefined');
                            break;
                        case 'eval.toString().length':
                            // TO CHANGE AND VERIFY IN BROWSER CONSOLE WITH THIS FUNCTION 'eval.toString().length' IF YOU UPLOAD ANOTHER NAVIGATORS
                            if(strpos($navigator->userAgent, 'Chrome') !== false)
                                $properties[] = urlencode($key.'=33');
                            else
                                $properties[] = urlencode($key.'=37');
                            break;
                        default:
                            $elements = explode('.', $key);
                            switch($elements[0])
                            {
                                case 'navigator':
                                    if(isset($navigator->$elements[1]))
                                    {
                                        $properties[] = urlencode($key.'='.$navigator->$elements[1]);
                                    }
                                    else
                                    {
                                        $properties[] = urlencode($key.'=undefined');
                                    }
                                    break;
                            }

                    }
                    break;
                case 'plugin_extentions':
                    // TO CHANGE IF YOU UPLOAD ANOTHER NAVIGATORS
                    $properties[] = urlencode('plugin_ext=so');
                    if(strpos($navigator->userAgent, 'Chrome') !== false)
                        $properties[] = urlencode('plugin_ext=no extention'); //yes I know, but that's the way it is in the incapsula JS. Do NOT modify
                    break;
                default:
                    break;
            }
        }
        return $properties;
    }

    /**
     * The asl value is a string constructed from an unique salt
     * and the digest of cookies that start with incap_ses_ .
     * This value must be set in the incapsula cookie
     *
     * @param $digests
     * @param $salt
     * @return mixed
     */
    private function _getIncapsulaAsl($digests, $salt)
    {
        $asl = "";
        for($i=0; $i<strlen($salt); $i++)
        {
            $asl .= strtolower(dechex(ord($salt[$i]) . ord($digests[$i % strlen($digests)])));
        }
        return $asl;
    }

    /**
     * Function that creates and returns a specific Incapsula cookie
     * that allows the IP to be validated
     *
     * @param $extensions
     * @param $proxy
     * @param $salt
     * @return bool|string
     */
    private function _setIncapsulaCookie($extensions, $proxy, $salt)
    {
        $cookieFilename = $this->cookieFolderPath.'/cookie-'.$proxy.'.txt';
        if(!file_exists($cookieFilename))
        {
            print_r("No cookie found for $proxy !");
            return false;
        }
        $cookie = file_get_contents($cookieFilename);
        $sessionCookies = $this->_getSessionCookies($cookie);
        if(count($sessionCookies) < 1)
        {
            print_r("No Incapsula cookie found in the $proxy cookie!");
            return false;
        }
        $i=0;
        $digests = array();
        foreach($sessionCookies as $key => $sessionCookie)
        {
            $digests[$i] = $this->_toDigest(implode(',',$extensions) . $sessionCookie);
            $i++;
        }

        // starting new fancy schmancy logic of incapsula...
        $digests = implode(',',$digests);
        // construct a string out of an unique salt and the cookie digests
        $asl = $this->_getIncapsulaAsl($digests, $salt);

        $newCookieValue = implode(',',$extensions) . ",digest=" . $digests .",s=".$asl;

        $finalIncapsulaCookie = $this->_createCookie("___utmvc", $newCookieValue, 20);
        $cookiestr='';
        foreach($sessionCookies as $key => $value)
        {
            $cookiestr .= $key . '='.$value.';';
        }
        $finalIncapsulaCookie = $cookiestr.$finalIncapsulaCookie;

        return $finalIncapsulaCookie;
    }

    /**
     * Function that parses a cookie in order to get
     * only get the cookies that start with incap_ses_
     * Needed for the digest
     *
     * @param $cookie
     * @return array
     */
    private function _getSessionCookies($cookie)
    {
        $cookies = array();
        if(preg_match_all('%(incap_ses_[0-9_]+)\s+?([A-Za-z0-9=]+)%s', $cookie, $sessionCookies))
        {
            $count = count($sessionCookies[0]); //TODO be careful (one change made)
            for($i = 0; $i < $count; $i++)
            {
                $cookies[$sessionCookies[1][$i]] = $sessionCookies[3][$i];
            }
        }
        return $cookies;
    }

    /**
     * Return the sum of the ASCII values of all of a string's characters
     *
     * @param $properties
     * @return int
     */
    private function _toDigest($properties)
    {
        $digest = 0;
        $str = str_split($properties);
        foreach($str as $char)
            $digest += ord($char);
        return $digest;
    }

    /**
     * Function that creates a cookie with key, value and expiration date
     *
     * @param $name
     * @param $value
     * @param $seconds
     * @return string
     */
    private function _createCookie($name, $value, $seconds)
    {
        $date = new DateTime('+'.$seconds.' seconds');
        $formatedDate = $date->format('D, d M Y H:i:s');
        $cookie = $name . "=" . $value . '; expires=' . $formatedDate . "; path=/";
        return $cookie;
    }

    /**
     * Function that returns the Incapsula validated proxy
     *
     * @return array
     */
    public function _getValidProxies()
    {
        return $this->goodProxies;
    }

    /**
     * @param $fullURL
     * @param $proxy
     * @param $storeReferer
     * @param string $cookie
     * @return bool|mixed
     */
    private function _getPage($fullURL, $proxy, $cookie='')
    {
        $header = array();
        $ch = curl_init();
        if($cookie === '')
        {
            // set one cookie file per proxy
            $cookieFile = $this->cookieFolderPath."/cookie-".$proxy['IP:PORT'].".txt";

            curl_setopt($ch,CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch,CURLOPT_COOKIEFILE, $cookieFile);
        }
        else
        {
            $header[] = 'cookie: '.$cookie;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_TIMEOUT);
        curl_setopt($ch, CURLOPT_PROXY, $proxy['IP:PORT']);

        switch(trim(strtolower($proxy['PROTOCOL'])))
        {
            case 'http':
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                break;
            case 'socks':
            case 'socks5':
                return false;
            default:
                return false;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgents[array_rand($this->userAgents)]);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
//        curl_setopt($ch, CURLOPT_CAINFO, "cacert.pem");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_URL, $fullURL);


        $response = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        print_r($curlInfo['http_code']. " error code\n");
        if(curl_exec($ch) === false)
        {
            echo 'Curl error: ' . curl_error($ch).PHP_EOL;
        }
        curl_close($ch);
        if($curlInfo['http_code'] == 200)
        {
            return $response;
        }
        return false;
    }
}
