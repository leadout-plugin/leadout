<?php

include_once('Config/LI_ConfigReaderInterface.php');
include_once('Config/LI_JsonConfigReader.php');

class LI_Parser
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /**
     * @var string[]
     */
    private $internalHosts = array();

    public function __construct(LI_ConfigReaderInterface $configReader = null, array $internalHosts = array() )
    {
        $this->configReader = $configReader ? $configReader : self::createDefaultConfigReader();
        $this->internalHosts = $internalHosts;
    }

    /**
     * Parse referer URL
     *
     * @param string $refererUrl
     * @param string $pageUrl
     * @return Referer
     */
    public function parse($refererUrl, $pageUrl = null)
    {
        $refererParts = $this->parseUrl($refererUrl);
        if (!$refererParts) {
            return LI_Referer::createInvalid();
        }

        $pageUrlParts = $this->parseUrl($pageUrl);

        //print_r($refererParts);

        if ($pageUrlParts
            && $pageUrlParts['host'] === $refererParts['host']
            || in_array($refererParts['host'], $this->internalHosts)) {
            return LI_Referer::createInternal();
        }

        $referer = $this->lookup($refererParts['host'], $refererParts['path']);

        if (!$referer) {
            return LI_Referer::createUnknown();
        }

        $searchTerm = null;

        if (is_array($referer['parameters'])) {
            parse_str($refererParts['query'], $queryParts);

            //foreach ($queryParts as $key => $parameter) {
            $searchTerm = isset($queryParts['q']) ? $queryParts['q'] : $searchTerm;
            //}
        }

        return LI_Referer::createKnown($referer['medium'], $referer['source'], $searchTerm);
    }

    private static function parseUrl($url)
    {
        if ($url === null) {
            return null;
        }

        $parts = parse_url($url);
        if (!isset($parts['scheme']) || !in_array(strtolower($parts['scheme']), array('http', 'https'))) {
            return null;
        }

        return array_merge(array('query' => null, 'path' => '/'), $parts);
    }

    private function lookup($host, $path)
    {
        $referer = $this->lookupPath($host, $path);

        if ($referer) {
            return $referer;
        }

        return $this->lookupHost($host);
    }

    private function lookupPath($host, $path)
    {
        $referer = $this->lookupHost($host, $path);

        if ($referer) {
            return $referer;
        }

        $path = substr($path, 0, strrpos($path, '/'));

        if (!$path) {
            return null;
        }

        return $this->lookupPath($host, $path);
    }

    private function lookupHost($host, $path = null)
    {
        do {
            $referer = $this->configReader->lookup($host . $path);
            $host = substr($host, strpos($host, '.') + 1);
        } while (!$referer && substr_count($host, '.') > 0);

        return $referer;
    }

    private static function createDefaultConfigReader()
    {
        return new LI_JsonConfigReader(LEADIN_PLUGIN_DIR . '/admin/inc/sources/referers.json');
    }
}
