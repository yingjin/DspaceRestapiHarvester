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
 *
 * @package DspaceRestapiHarvester
 * @subpackage Models
 */
class DspaceRestapiHarvester_Harvest_RestHarvester
{
    /**
     * Notice message code, used for status messages.
     */
    const MESSAGE_CODE_NOTICE = 1;
    
    /**
     * Error message code, used for status messages.
     */
    const MESSAGE_CODE_ERROR = 2;
    

    /**
     * @var DspaceRestapiHarvester_Harvest The DspaceRestapiHarvester_Harvest object model.
     */
    private $_harvest;
    
    /**
     * @var json The current, cached json record object.
     */
    private $_record;

    private $_options = array(
            'public' => true,
            'featured' => false,
    );

    private $_elementSets;
    private $_elements;

    /**
     * Class constructor.
     * 
     * Prepares the harvest process.
     * 
     * @param DspaceRestapiHarvester_Harvest $harvest The DspaceRestapiHarvester_Harvest object
     * model
     * @return void
     */
    public function __construct($harvest)
    {   
        // Set an error handler method to record run-time warnings (non-fatal 
        // errors).
        set_error_handler(array($this, 'errorHandler'), E_WARNING);
        
        $this->_harvest = $harvest;


    }

    public function setOption($key, $value)
    {
        $this->_options[$key] = $value;
    }

    public function getOption($key)
    {
        return $this->_options[$key];
    }
    
    /**
     *
     * @param $record The current record object
     */
    protected function _harvestRecord($record){

        $itemMetadata = array(
                   'item_type_name' => 'Oral History',
                   'collection_id' => $this->_collection->id,
                   'identifier'      => $record["id"],
                   'modified'  => $record["lastModified"],
                    'public'        => $this->getOption('public'),
                    'featured'      => $this->getOption('featured'),
               );
        // go through each metadata field
        $elementName = "";
        $elementTexts = array();
        $metadataEntries = $this->_harvest->listMetadata($record['id']);
        foreach($metadataEntries as $metadataEntry){
           //check if the elements set (schema) is dublin core
            //$elementSet = "";
            //$elements = "";
            /*if($field["qualifier"]){
                $elementName = $field["schema"] . '.' . $field["element"] . "." . $field["qualifier"];
            }else{
                $elementName = $field["schema"] . '.' . $field["element"];
            }           */

            $key = $metadataEntry['key'];


            if(array_key_exists($key, $this->_elements)){
                $schema = substr($key, 0, strpos($key, '.'));

                //$elementTexts[$elementSet][$elements[$elementName]][] = array('text' => (string) $field["value"], 'html' => (boolean) true);
                $elementTexts = $this->_buildElementTexts($elementTexts, $this->_elementSets[$schema],$this->_elements[$key],(string) $metadataEntry["value"],true);
            }else{

                 //$this->_addStatusMessage("Elements Not IN !!!!!!!!!!!! $elementName");
            }
        }

        // harvest bitstream for the record

        $fileMetadata = array();
       /* $bundles = $this->_harvest->listBundles($record["id"]);
        $bundleCount = count($bundles);
        foreach($bundles as $bundle){
           if($bundle["name"] == "THUMBNAIL");
               $bt_id =  $bundle["bitstreams"][0]["id"];
               $bitstream = $this->_harvest->getBitstream($bt_id);
	       
               $fileMetadata['file_transfer_type'] = 'url';
               $fileMetadata['files'] = array(
                   'Upload' => null,
                   'Url' => "http://dspaceland.rice.edu:8080/bitstream/handle/1911/25/IMG_2003.JPG?sequence=1",
                   'source' => (string) "http://dspaceland.rice.edu:8080/bitstream/handle/1911/25/IMG_2003.JPG?sequence=1",
                   'name'   => (string) "IMG_2003.jpg",
                   'metadata' => array(),
           );

       } */

       return array('itemMetadata' => $itemMetadata,
                    'elementTexts' => $elementTexts,
                    'fileMetadata' => $fileMetadata);

    }
    
    /**
     * Checks whether the current record has already been harvested, and
     * returns the record if it does.
     *
     * @param SimpleXMLIterator record to be harvested
     * @return DspaceRestapiHarvester_Record|false The model object of the record,
     *      if it exists, or false otherwise.
     */
    private function _recordExists($json)
    {   
        $handle = $json["handle"];
        $identifier = $json["id"];
        
        /* Ideally, the handle would be globally-unique, but for
           poorly configured servers that might not be the case.  However,
           the identifier is always unique for that repository, so given
           already-existing identifiers, check against the base URL.
        */
        $table = get_db()->getTable('DspaceRestapiHarvester_Record');
        $record = $table->findBy(
            array(
                'base_url' => $this->_harvest->base_url,
                'collection_id' => $this->_harvest->collection_id,
                'identifier' => $identifier,
                'handle' => $handle,
            ),
            1,
            1
        );
        

        if ($record) {
            $record = $record[0];
        }
        return $record;
    }

    private function _isIterable($var)
    {
        return (is_array($var) || $var instanceof Traversable);
    }

    /**
     * Recursive method that loops through all requested records
     * 
     */
    private function _harvestRecords()
    {
	// harvest all items in a given collection
        $response = $this->_harvest->listRecords();
        $resultsCount = count($response);

        _log("Number of harvested Records: ". $resultsCount, Zend_Log::INFO);

        if ($resultsCount>0) {
            // find out the element label and element set label
            $table = get_db()->getTable('DspaceRestapiHarvester_Elementset');
            $records = $table->findAll();
            foreach ($records as $record){

                $elementSets[$record->elementset_name] = $record->elementset_label;
            }

            $table = get_db()->getTable('DspaceRestapiHarvester_Element');
            $records = $table->findAll();
            foreach ($records as $record){

                $elements[$record->element_name] = $record->element_label;
            }

            $this->_elementSets = $elementSets;
            $this->_elements = $elements;

            for ($x=0;$x<$resultsCount;$x++) {
                $this->_harvestLoop($response[$x]);
            }
        } else {
            $this->_addStatusMessage("No records were found.");
        }
        
        return true;
    }

    /**
     * loop through the records for update and ingestion
     */
    private function _harvestLoop($record)
    {
        // Ignore (skip over) records not archived.
        //if (!$this->isArchivedRecord($record)) {
        //    return;
        //}
        $existingRecord = $this->_recordExists($record);


        $harvestedRecord = $this->_harvestRecord($record);
        
        // Cache the record for later use.
        $this->_record = $record;
        
        // Record has already been harvested
        if ($existingRecord) {
            // If datestamp has changed, update the record, otherwise ignore.
            //if($existingRecord->datestamp != $record['lastModified']) {
                $this->_updateItem($existingRecord,
                                  $harvestedRecord['elementTexts'],
                                  $harvestedRecord['fileMetadata']);
            //}
            //release_object($existingRecord);
        } else {
            $this->_insertItem(
                $harvestedRecord['itemMetadata'],
                $harvestedRecord['elementTexts'],
                $harvestedRecord['fileMetadata']
            );

        }
    }
    
    /**
     * Return whether the record is archived
     * 
     * @param SimpleXMLIterator The record object
     * @return bool
     */
    public function isArchivedRecord($record)
    {
        return ($record["isArchived"]);
    }
    
    /**
     * Insert a record into the database.
     * 
     * @param Item $item The item object corresponding to the record.
     * @return void
     */
    private function _insertRecord($item)
    {
        $record = new DspaceRestapiHarvester_Record;
        
        $record->harvest_id = $this->_harvest->id;
        $record->item_id    = $item->id;
        $record->identifier = (string) $this->_record["id"];
        $record->handle = (string) $this->_record["handle"];
        $record->datestamp  = (string) $this->_record["lastModified"];
        $record->save();
        
        release_object($record);
    }
    
    /**
     * Update a record in the database with information from this harvest.
     * 
     * @param DspaceRestapiHarvester_Record The model object corresponding to the record.
     */
    private function _updateRecord(DspaceRestapiHarvester_Record $record)
    {   
        $record->datestamp  = (string) $this->_record["lastModified"];
        $record->save();
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
    
    /**
     * Template method.
     * 
     * May be overwritten by classes that extend of this one. This method runs 
     * once, prior to record iteration.
     * 
     * @see self::__construct()
     */
    protected function _beforeHarvest()
    {
        $harvest = $this->_getHarvest();

        $collectionMetadata = array(
            'metadata' => array(
                'public' => $this->getOption('public'),
                'featured' => $this->getOption('featured'),
            ),);
        $collectionMetadata['elementTexts']['Dublin Core']['Title'][]
            = array('text' => (string) $harvest->collection_name, 'html' => false);
        $collectionMetadata['elementTexts']['Dublin Core']['Description'][]
            = array('text' => (string) $harvest->collection_spec, 'html' => false);

        $this->_collection = $this->_insertCollection($collectionMetadata);

    }
    
    /**
     * Template method.
     * 
     * May be overwritten by classes that extend of this one. This method runs 
     * once, after record iteration.
     * 
     * @see self::__construct()
     */
    protected function _afterHarvest()
    {
    }
    
    /**
     * Insert a collection.
     * 
     * @see insert_collection()
     * @param array $metadata
     * @return Collection
     */
    final protected function _insertCollection($metadata = array())
    {
        // If collection_id is not null, use the existing collection, do not
        // create a new one.
        if (($collection_id = $this->_harvest->collection_id)) {
            $collection = get_db()->getTable('Collection')->find($collection_id);
        }
        else {
            // There must be a collection name, so if there is none, like when the 
            // harvest is repository-wide, set it to the base URL.
            if (!isset($metadata['elementTexts']['Dublin Core']['Title']['text']) || 
                    !$metadata['elementTexts']['Dublin Core']['Title']['text']) {
                $$metadata['elementTexts']['Dublin Core']['Title']['text'] = $this->_harvest->base_url;
            }

            $collection = insert_collection($metadata['metadata'],$metadata['elementTexts']);
        
            // Remember to set the harvest's collection ID once it has been saved.
            $this->_harvest->collection_id = $collection->id;
            $this->_harvest->save();
        }
        return $collection;
    }
    
    /**
     * Convenience method for inserting an item and its files.
     * 
     * Method used by map writers that encapsulates item and file insertion. 
     * Items are inserted first, then files are inserted individually. This is 
     * done so Item and File objects can be released from memory, avoiding 
     * memory allocation issues.
     * 
     * @see insert_item()
     * @see insert_files_for_item()
     * @param mixed $metadata Item metadata
     * @param mixed $elementTexts The item's element texts
     * @param mixed $fileMetadata The item's file metadata
     * @return true
     */
    final protected function _insertItem(
        $metadata = array(), 
        $elementTexts = array(), 
        $fileMetadata = array()
    ) {
        // Insert the item.
        $item = insert_item($metadata, $elementTexts);
        
        // Insert the record after the item is saved. The idea here is that the 
        // DspaceRestapiHarvester_Records table should only contain records that have
        // corresponding items.
        $this->_insertRecord($item);
        
        // If there are files, insert one file at a time so the file objects can 
        // be released individually.
        if (isset($fileMetadata['files'])) {
            
            // The default file transfer type is URL.
            $fileTransferType = isset($fileMetadata['file_transfer_type'])
                              ? $fileMetadata['file_transfer_type'] 
                              : 'Url';

            // The default option is ignore invalid files.
            $fileOptions = isset($fileMetadata['file_ingest_options'])
                         ? $fileMetadata['file_ingest_options'] 
                         : array('ignore_invalid_files' => true);

            // Prepare the files value for one-file-at-a-time iteration.
            $files = array($fileMetadata['files']);

            foreach ($files as $file) {
                $fileOb = insert_files_for_item(
                    $item, 
                    $fileTransferType, 
                    $file, 
                    $fileOptions);   
                   _log($fileOb);
                   $fileObject= $fileOb;//$fileOb[0];
                   if(!empty($file['metadata'])){
                       $fileObject->addElementTextsByArray($file['metadata']);
                   $fileObject->save();
                   }
                  
                // Release the File object from memory. 
                release_object($fileObject);
            }
        }
        
        // Release the Item object from memory.
        release_object($item);
        
        return true;
    }
    
    /**
     * Convenience method for inserting an item and its files.
     * 
     * Method used by map writers that encapsulates item and file insertion. 
     * Items are inserted first, then files are inserted individually. This is 
     * done so Item and File objects can be released from memory, avoiding 
     * memory allocation issues.
     * 
     * @see insert_item()
     * @see insert_files_for_item()
     * @param DspaceRestapiHarvester_Record $itemId ID of item to update
     * @param mixed $elementTexts The item's element texts
     * @param mixed $fileMetadata The item's file metadata
     * @return true
     */
    final protected function _updateItem(
        $record, 
        $elementTexts = array(), 
        $fileMetadata = array()
    ) {
        // Update the item
        $item = update_item(
            $record->item_id, 
            array('overwriteElementTexts' => true), 
            $elementTexts, 
            $fileMetadata
        );
        
        // Update the datestamp stored in the database for this record.
        $this->_updateRecord($record);

        // Release the Item object from memory.
        release_object($item);
        
        return true;
    }
    
    /**
     * Adds a status message to the harvest.
     * 
     * @param string $message The error message
     * @param int|null $messageCode The message code
     * @param string $delimiter The string dilimiting each status message
     */
    final protected function _addStatusMessage(
        $message, 
        $messageCode = null, 
        $delimiter = "\n\n"
    ) {
        $this->_harvest->addStatusMessage($message, $messageCode, $delimiter);
    }
    
    /**
     * Return this instance's DspaceRestapiHarvester_Harvest object.
     * 
     * @return DspaceRestapiHarvester_Harvest
     */
    final protected function _getHarvest()
    {
        return $this->_harvest;
    }
    
    /**
     * Convenience method that facilitates the building of a correctly formatted 
     * elementTexts array.
     * 
     * @see insert_item()
     * @param array $elementTexts The previously build elementTexts array
     * @param string $elementSet This element's element set
     * @param string $element This element text's element
     * @param mixed $text The text
     * @param bool $html Flag whether this element text is HTML
     * @return array
     */
    protected function _buildElementTexts(
        array $elementTexts = array(), 
        $elementSet, 
        $element, 
        $text, 
        $html
    ) {
        $elementTexts[$elementSet][$element][] 
            = array('text' => (string) $text, 'html' => (bool) $html);
        return $elementTexts;
    }
    
    /**
     * Error handler callback.
     * 
     * @see self::__construct()
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (!error_reporting()) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /**
     * Harvest records from the Dspace repository.
     */
    final public function harvest()
    {
        try {
            $this->_harvest->status = 
                DspaceRestapiHarvester_Harvest::STATUS_IN_PROGRESS;

            $this->_beforeHarvest();

            // This method does most of the actual work.
            $this->_harvestRecords();


                $this->_afterHarvest();
                $this->_harvest->status = 
                    DspaceRestapiHarvester_Harvest::STATUS_COMPLETED;
                $this->_harvest->completed = $this->_getCurrentDateTime();


            $this->_harvest->save();
        
        } catch (Zend_Http_Client_Exception $e) {
            $this->_stopWithError($e);
        } catch (Exception $e) {
            $this->_stopWithError($e);
            // For real errors need to be logged and debugged.
            _log($e, Zend_Log::ERR);
        }
    
        $peakUsage = memory_get_peak_usage();
        _log("[DspaceRestapiHarvester] Peak memory usage: $peakUsage", Zend_Log::INFO);
    }

    private function _stopWithError($e)
    {
        $this->_addStatusMessage($e->getMessage(), self::MESSAGE_CODE_ERROR);
        $this->_harvest->status = DspaceRestapiHarvester_Harvest::STATUS_ERROR;
        // Reset the harvest start_from time if an error occurs during 
        // processing. Since there's no way to know exactly when the 
        // error occured, re-harvests need to start from the beginning.
        $this->_harvest->start_from = null;
        $this->_harvest->save();
    }


}