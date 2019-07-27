<?php
/**
 * User: catxp0
 * Date: 7/24/19
 * Time: 6:25 PM
 */

class IncapsulaProxyValidator
{
    /** @var array */
    private $proxies = [];
    /** @var string */
    private $domain;
    /** @var int */
    private $maxProxiesPerFork;
    /** @var string */
    private $cookieFolderPath;
    /** @var string */
    private $rawDataFolder;

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
}