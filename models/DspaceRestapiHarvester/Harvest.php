<?php
/**
 * @package DspaceRestapiHarvester
 * @subpackage Models
 * @copyright Copyright 2008-2013 Fondren Library at Rice University
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @author Ying Jin ying.jin@rice.edu
 * @modified from OaipmhHarvester
 */


/**
 * Model class for a harvest.
 *
 * @package DspaceRestapiHarvester
 * @subpackage Models
 */
class DspaceRestapiHarvester_Harvest extends Omeka_Record_AbstractRecord
{
    const STATUS_QUEUED      = 'queued';
    const STATUS_IN_PROGRESS = 'in progress';
    const STATUS_COMPLETED   = 'completed';
    const STATUS_ERROR       = 'error';
    const STATUS_DELETED     = 'deleted';
    const STATUS_KILLED      = 'killed';
    
    public $id;
    public $collection_id;
    public $base_url;
    public $source_collection_id;
    public $collection_spec;
    public $collection_name;
    public $collection_handle;
    public $status;
    public $status_messages;
    public $initiated;
    public $completed;
    public $start_from;

    private $_request;

    public function setRequest(DspaceRestapiHarvester_Request $request = null)
    {
        if ($request === null) {
            $request = new DspaceRestapiHarvester_Request();
        }
        $this->_request = $request;
    }

    public function getRequest()
    {
        if (!$this->_request) {
            $this->setRequest();
        }
        return $this->_request;
    }

    public function isError()
    {
        return ($this->status == self::STATUS_ERROR);
    }

    public function listRecords()
    {
        //$query = "collections/". $this->source_collection_id . "/items.json";
        $query = "collections/". $this->source_collection_id . "?expand=items";

        $client = $this->getRequest();
        $client->setBaseUrl($this->base_url);

        $response = $client->listRecords($query);

        $recordCount = count($response);
        if ($recordCount==0) {
                $this->addStatusMessage("The collection returned no records.");
        }

        //return $response;
        return $response['items'];
    }

 /**
    public function listBundles($item_id)
    {
        // Harvest an item bundle with given item id.
        //$query = "items/".$item_id . "/bundles.json";
        $query = "items/".$item_id . "/?expand=all";

        $client = $this->getRequest();
        $client->setBaseUrl($this->base_url);
        $response = $client->listRecords($query);
        $recordCount = count($response);
        if ($recordCount==0) {
                $this->addStatusMessage("The repository returned no records.");
        }

        return $response;
    }

    public function listBitstreams($item_id, $item_handle)
    {

        $bundles = $this->listBundles($item_id);
        $thumbList = array();
        $origList = array();

        //$bundleCount = count($bundles);
        foreach($bundles as $bundle){

               foreach($bundle["bitstreams"] as $bitstream){
                   $bt_name =  $bitstream["name"];
                   $bt_seq = $bitstream["sequenceId"];

                   // FIXME : getting dspace_url in a way might break in other DSpace with different config
                   // remove the last path of restapi

                   // trim out the trailing / in url
                   $trimed_url = rtrim($this->base_url, "/");
                   // remove the path after last /

                   $dspace_url = substr($trimed_url, 0, strrpos($trimed_url, "/"));

                   //$query = $dspace_url. "/bitstreams/" . $bitstream_id . "/download.json?user=email&pass=";
                   $bt_url = $dspace_url ."/bitstream/handle/" . $item_handle . "/" . $bt_name . "?sequence=" . $bt_seq;


                   if($bundle["name"] == "THUMBNAIL"){
                       $bt_name = substr($bt_name, 0, -4);
                       $thumbList[$bt_name] = $bt_url;
                   }else if($bundle["name"]=="ORIGINAL"){
                       $origList[$bt_name] = $bt_url;
                   }
               }
        }
        $listBt = array( "thumbnail" => $thumbList, "original" => $origList);
        return $listBt;
    }

  **/

    public function listRecord($item_id){
       $query = "items/".$item_id . "/?expand=metadata,bitstreams";
       $client = $this->getRequest();
       $client->setBaseUrl($this->base_url);
       $metadata = $client->listRecords($query);
       $recordCount = count($metadata);

        if ($recordCount==0) {
            $this->addStatusMessage("The item returned no metadata.");

        }

        return $metadata;
    }

    public function listBitstreams($item_id)
    {

        // Harvest an item bitstreams with given item id.
        $query = "items/".$item_id . "/?expand=bitstreams";

        $client = $this->getRequest();
        $client->setBaseUrl($this->base_url);
        $bitstreams = $client->listRecords($query);
        $recordCount = count($bitstreams);
        if ($recordCount==0) {
            $this->addStatusMessage("The item returned no bitstream.");
            return array("thumbnail" => null, "original" => null);
        }

        $thumbList = array();
        $origList = array();

        foreach($bitstreams['bitstreams'] as $bitstream){

           $bt_name =  $bitstream["name"];
           //$bt_seq = $bitstream["sequenceId"];
           $bundle_name = $bitstream["bundleName"];
           // FIXME : getting dspace_url in a way might break in other DSpace with different config
           // remove the last path of restapi

           // trim out the trailing / in url
           //$trimed_url = rtrim($this->base_url, "/");
           // remove the path after last /

           //$dspace_url = substr($trimed_url, 0, strrpos($trimed_url, "/"));

           $bt_url = $this->base_url .  $bitstream['retrieveLink'];

           if($bundle_name == "THUMBNAIL"){
               $bt_name = substr($bt_name, 0, -4);
               $thumbList[$bt_name] = $bt_url;
           }else if($bundle_name =="ORIGINAL"){
               $origList[$bt_name] = $bt_url;
           }
        }
        $listBt = array( "thumbnail" => $thumbList, "original" => $origList);
        return $listBt;

    }

    public function addStatusMessage($message, $messageCode = null, $delimiter = "\n\n")
    {
        if (0 == strlen($this->status_messages)) {
            $delimiter = '';
        }
        $date = $this->_getCurrentDateTime();
        $messageCodeText = $this->_getMessageCodeText($messageCode);
        
        $this->status_messages .= "$delimiter$messageCodeText: $message ($date)";
        $this->save();
    }


    /**
     * Return a message code text corresponding to its constant.
     * 
     * @param int $messageCode
     * @return string
     */
    private function _getMessageCodeText($messageCode)
    {
        switch ($messageCode) {
            case DspaceRestapiHarvester_Harvest_RestHarvester::MESSAGE_CODE_ERROR:
                $messageCodeText = 'Error';
                break;
            case DspaceRestapiHarvester_Harvest_RestHarvester::MESSAGE_CODE_NOTICE:
            default:
                $messageCodeText = 'Notice';
                break;
        }
        return $messageCodeText;
    }

    /**
     * Return the current, formatted date.
     *
     * @return string
     */
    private function _getCurrentDateTime()
    {
        return date('Y-m-d H:i:s');
    }
}