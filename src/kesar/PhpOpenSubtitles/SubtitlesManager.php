<?php

namespace OpenSubtitlesApi;

/**
 * Class to connect to OSDb and retrieve subtitles
 * @author César Rodríguez <kesarr@gmail.com>
 */
class SubtitlesManager
{
    const SEARCH_URL = 'http://api.opensubtitles.org/xml-rpc';

    const DEFAULT_LANG = 'en_US';

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $lang = self::DEFAULT_LANG;

    /**
     * @var string
     */
    private $userAgent;

    /**
     * @var string
     */
    private $userToken;

    /**
     * @param string $username
     * @param string $password
     * @param string $lang
     * @param string $userAgent
     */
    public function __construct($username, $password, $lang = self::DEFAULT_LANG, $userAgent = 'OSTestUserAgent')
    {
        $this->username = $username;
        $this->password = $password;
        $this->lang = $lang;
        $this->userAgent = $userAgent;
    }

    /**
     * @param  string  $query
     * @param  boolean $all
     */
    public function get($query, $all = false)
    {
        $subtitlesUrls = [];

        if (is_file($query)) {
            $subtitles = $this->getSubtitlesFromMovieHash($fileHash, filesize($query));
        } elseif (is_int($query)) {
            $subtitles =  $this->getSubtitleUrlsFromImdbId($query);
        } else {
            $subtitles = $this->getSubtitleUrlsFromQuery($query);
        }

        $urlGenerator = new UrlGenerator();

        return $urlGenerator->getSubtitleUrls($subtitles, $all);
    }

    /**
     * @return string
     */
    private function getUserToken()
    {
        if (null !== $this->userToken) {
            return $this->userToken;
        }

        return $this->logIn();
    }

    /**
     * @return string|bool
     */
    private function logIn()
    {
        $request  = xmlrpc_encode_request(
            "LogIn",
            [$this->username, $this->password, $this->lang, $this->userAgent]
        );

        $response = $this->generateResponse($request);
        if (($response && xmlrpc_is_fault($response))) {
            trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
        } else {
            if ($this->isWrongStatus($response)) {
                trigger_error('no login');
            } else {
                $this->userToken = $response['token'];

                return $this->$this->userToken;
            }
        }

        return false;
    }

    /**
     * @param  array $response
     *
     * @return boolean
     */
    private function isWrongStatus($response)
    {
        return (empty($response['status']) || $response['status'] != '200 OK');
    }

    /**
     * @param string $movieFileName
     * @param int    $fileSize
     *
     * @return array
     */
    private function getSubtitlesFromMovieHash($movieFileName, $fileSize)
    {
        $hashGenerator = new HashGenerator($movieFileName);
        $fileHash = $hashGenerator->get();

        $request  = $this->generateXmlRpcSearchRequest([
            'moviehash' => $fileHash,
            'moviebytesize' => (int) $fileSize,
        ]);

        $response = $this->handleSearchRequest($request);

        return null === $response ? [] : $response;
    }

    private function getSubtitleUrlsFromImdbId($imdbId)
    {
        $request = $this->generateXmlRpcSearchRequest([
            'imdbid' => (int) $imdbId,
        ]);

        $response = $this->handleSearchRequest($request);

        return null === $response ? [] : $response;
    }

    private function getSubtitleUrlsFromQuery($query)
    {
        $request = $this->generateXmlRpcSearchRequest([
            'query' => (int) $query,
        ]);

        $response = $this->handleSearchRequest($request);

        return null === $response ? [] : $response;
    }

    /**
     * @param $request
     *
     * @return mixed
     */
    private function generateResponse($request)
    {
        $context  = stream_context_create(
            [
                'http' => [
                    'method'  => 'POST',
                    'header'  => 'Content-Type: text/xml',
                    'content' => $request
                ]
            ]
        );
        $file = file_get_contents(self::SEARCH_URL, false, $context);
        $response = xmlrpc_decode($file);

        return $response;
    }

    /**
     * @param  array $args
     */
    private function generateXmlRpcSearchRequest($args = [])
    {
        return xmlrpc_encode_request(
            "SearchSubtitles",
            [
                $this->getUserToken(),
                array_merge(
                    [
                        'sublanguageid' => $this->lang,
                    ],
                    $args
                )
            ]
        );
    }

    private function handleSearchRequest($request)
    {
        $response = $this->generateResponse($request);
        if (($response && xmlrpc_is_fault($response))) {
            trigger_error("xmlrpc: $response[faultString] ($response[faultCode])");
        } else {
            if ($this->isWrongStatus($response)) {
                trigger_error('no login');
            } else {
                return $response;
            }
        }
    }
}
