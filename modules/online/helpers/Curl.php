<?php
namespace app\modules\online\helpers;

class Curl
{

    // Curl params
    protected $proxy = "";
    protected $followLocation = true;
    protected $maxRedirs = 5;
    protected $userAgent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.1) Gecko/2008070208";
    protected $timeout = 30;

    /**
     * check link is the same site
     * @param $link
     * @param $host
     * @return bool
     */
    public function isHost($link, $host)
    {
        $url = parse_url($link);
        if ($url === false) {
            return false;
        }
        if (!isset($url['host']) || !$url['host']) {
            return false;
        }
        if (preg_match('/^(?:www\.)?' . $host . '$/i', $url['host'])) {
            return true;
        }
        if (preg_match('/^' . $host . '$/i', $url['host'])) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isFollowLocation()
    {
        return $this->followLocation;
    }

    /**
     * @param bool $followLocation
     */
    public function setFollowLocation($followLocation)
    {
        $this->followLocation = $followLocation;
    }

    /**
     * @return int
     */
    public function getMaxRedirs()
    {
        return $this->maxRedirs;
    }

    /**
     * @param int $maxRedirs
     */
    public function setMaxRedirs($maxRedirs)
    {
        $this->maxRedirs = $maxRedirs;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return string
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @param string $proxy
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * get EFFECTIVE_URL with 301 and 302 redirect
     * @param $url
     * @param int $redirects
     * @return mixed
     */
    public function getUrl($url, $redirects = 5)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 2);
        $data = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 301 || $http_code == 302) {
            $matches = array();
            preg_match('/Location:(.*?)\n/', $data, $matches);
            $url = @parse_url(trim(array_pop($matches)));
            if (!$url) {
                return curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            }
            $last_url = parse_url(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
            if (!isset($url['scheme']) || !$url['scheme']) {
                $url['scheme'] = $last_url['scheme'];
            }
            if (!isset($url['host']) || !$url['host']) {
                $url['host'] = $last_url['host'];
            }
            if (!isset($url['path']) || !$url['path']) {
                $url['path'] = $last_url['path'];
            }
            if (!isset($url['query']) || !$url['query']) {
                $url['query'] = '';
            }
            $new_url = $url['scheme'] . '://' . $url['host'] . $url['path'] . ($url['query'] ? '?' . $url['query'] : '');
            $redirects--;
            if ($redirects < 0) {
                return curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            }
            curl_close($ch);
            return $this->getUrl($new_url, $redirects);
        }
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        if ($curl_error != '') {
            // if many url
            $isHttps = 0;
            $urls = explode('http:', $url);
            if (count($urls) == 1) {
                $isHttps = 1;
                $urls = explode('https:', $url);
            }
            if (count($urls) > 1) {
                $url = ($isHttps ? "https:" : "http:") . $urls[count($urls) - 1];
            }
        }
        curl_close($ch);
        return $url;
    }


    /**
     * get html of page
     * @param $url
     * @return string
     */
    public function getContent($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->timeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        }
        if ($this->followLocation) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->maxRedirs);
        }
        if ($this->userAgent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        }
        if ($this->proxy) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        }
        return curl_exec($ch);
    }

}