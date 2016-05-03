<?php
/**
 * @package DspaceRestapiHarvester
 * @subpackage Models
 * @copyright Copyright 2008-2013 Fondren Library at Rice University
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @author Ying Jin ying.jin@rice.edu
 * @modified from OaipmhHarvester
 */

require_once 'vendor/autoload.php';
use Guzzle\Http\Client;


class DspaceRestapiHarvester_Request
{

    /**
     * @var string
     */
    private $_baseUrl;


    /**
     * @var Guzzle\Http\Client
     */
    private $_client;

   
    /**
     * Constructor.
     *
     * @param string $baseUrl
     */
    public function __construct($baseUrl = null) 
    {
        if ($baseUrl) {
            $this->setBaseUrl($baseUrl);

        }
    }

    public function setBaseUrl($baseUrl)
    {
        $this->_baseUrl = $baseUrl;
    }

    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * List all records for a given request.
     *
     * @param array $query
     */
    public function listRecords($query)
    {
        //$queryReq = $query['query'];
        $response = $this->_makeRequest($query);

        return $response;
    }

    /**
     * List all available Collections from the provider.
     */
    public function listCollections()
    {
        $query = "collections?limit=99999999";  // just limit a big number for safe
        $retVal = array();
        try {
            $json = $this->_makeRequest($query);
        
            // Handle returned errors
            if ($json == null) {
                    $collections = array();
            } else {

                $collections = $json;

            }

        } catch(Exception $e) {
            // Try to continue with no collections.
            $collections = array();
        }

        $retVal = $collections;
        return $retVal;
    }

    public function getClient()
    {
        if ($this->_client === null) {
            $this->setClient();
        }
        return $this->_client;
    }

    public function setClient(Guzzle\Http\Client $client = null)
    {
        if ($client === null) {
            $client = new Guzzle\Http\Client();

        }        
        $this->_client = $client;
    }

    private function _makeRequest($query)
    {

        $client = $this->getClient();

	// the baseUrl for the guzzle client is the rest Url
        $client->setBaseUrl($this->_baseUrl);
        $client->setUserAgent($this->_getUserAgent(), false);

        $request = $client->get($query);
        //$request->setHeader("Accept", "application/json");
        // Send the request and get the response

        $response = $request->send();

        if ($response->isSuccessful() && !$response->isRedirect()) {
            if(strpos($query,'retrieve') !== false)
            {
                return $response;
            }else{
                return $response->json();
            }
        }else{
	        return null;
        }
    }

    private function _getUserAgent()
    {
        try {
            $version = get_plugin_ini('DspaceRestapiHarvester', 'version');
        } catch (Zend_Exception $e) {
            $version = '';
        }
        return 'Omeka Dspace Restapi Harvester/' . $version;
    }
}