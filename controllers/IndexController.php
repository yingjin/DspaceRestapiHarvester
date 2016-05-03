<?php
/**
 * @package DspaceRestapiHarvester
 * @subpackage Controllers
 * @copyright Copyright 2008-2013 Fondren Library at Rice University
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @author Ying Jin ying.jin@rice.edu
 * @modified from OaipmhHarvester
 */

require_once dirname(__FILE__) . '/../forms/Harvest.php';



/**
 * Index controller
 *
 * @package DspaceRestapiHarvester
 * @subpackage Controllers
 */
class DspaceRestapiHarvester_IndexController extends Omeka_Controller_AbstractActionController
{
    public function init() 
    {
        $this->_helper->db->setDefaultModelName('DspaceRestapiHarvester_Harvest');
    }
    
    /**
     * Prepare the index view.
     * 
     * @return void
     */
    public function indexAction()
    {
        $harvests = $this->_helper->db->getTable('DspaceRestapiHarvester_Harvest')->findAll();
        $this->view->harvests = $harvests;
        $this->view->harvestForm = new DspaceRestapiHarvester_Form_Harvest();
        $this->view->harvestForm->setAction($this->_helper->url('collections'));
    }
    

    /**
     * Prepares the collections view.
     *
     * @return void
     */
    public function collectionsAction()
    {

        $waitTime = dspace_restapi_harvester_config('requestThrottleSecs', 5);
        if ($waitTime) {
            $request = new DspaceRestapiHarvester_Request_Throttler(
                new DspaceRestapiHarvester_Request($this->_getParam('base_url')),
                array('wait' => $waitTime)
            );
        } else {
            $request = new DspaceRestapiHarvester_Request(
                $this->_getParam('base_url')
            );
        }

        $response = $request->listCollections();

        $this->view->collections     = $response;
        $this->view->baseUrl         = $this->_getParam('base_url');
    }

     /**
     * Launch the harvest process.
     *
     * @return void
     */
    public function harvestAction()
    {
        // Only set on re-harvest
        $harvest_id = $this->_getParam('harvest_id');

        // If true, this is a re-harvest, all parameters will be the same
        if ($harvest_id) {
            $harvest = $this->_helper->db->getTable('DspaceRestapiHarvester_Harvest')->find($harvest_id);

            // Set vars for flash message
            $collectionId = $harvest->collection_id;
            $baseUrl = $harvest->base_url;

            // Only on successfully-completed harvests: use date-selective
            // harvesting to limit results.
            if ($harvest->status == DspaceRestapiHarvester_Harvest::STATUS_COMPLETED) {
                $harvest->start_from = $harvest->initiated;
            } else {
                $harvest->start_from = null;
            }
        } else {
            $baseUrl        = $this->_getParam('base_url');
            $sourceCollectionId = $this->_getParam('source_collection_id');
            $collectionId  = $this->_getParam('collection_id');
            $collectionName= $this->_getParam('collection_name');
            $collectionSpec = $this->_getParam('collection_spec');
            $collectionHandle = $this->_getParam('collection_handle');
            $collectionSize = $this->_getParam('collection_size');

            $harvest = $this->_helper->db->getTable('DspaceRestapiHarvester_Harvest')->findUniqueHarvest($baseUrl, $collectionHandle);

            if (!$harvest) {
                // There is no existing identical harvest, create a new entry.
                $harvest = new DspaceRestapiHarvester_Harvest;
                $harvest->base_url        = $baseUrl;
                $harvest->source_collection_id        = $sourceCollectionId;
                $harvest->collection_id        = $collectionId;
                $harvest->collection_name        = $collectionName;
                $harvest->collection_spec = $collectionSpec;
                $harvest->collection_handle = $collectionHandle;
                $harvest->collection_size = $collectionSize;

            }
        }

        // Insert the harvest.
        $harvest->status          = DspaceRestapiHarvester_Harvest::STATUS_QUEUED;
        $harvest->initiated       = date('Y:m:d H:i:s');
        $harvest->save();

        $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');
        $jobDispatcher->setQueueName('imports');

        try {
           $jobDispatcher->sendLongRunning('DspaceRestapiHarvester_Job', array('harvestId' => $harvest->id));

           /*** comments out below for job dispatcher */
           /* if ($memoryLimit = dspace_restapi_harvester_config('memoryLimit')) {
                ini_set('memory_limit', $memoryLimit);
            }
            // Set the set.
            $harvest = $this->_helper->db->getTable('DspaceRestapiHarvester_Harvest')
                             ->find($harvest->id);
            if (!$harvest) {
                throw new UnexpectedValueException(
                    "Harvest with id = '$harvest->id' does not exist.");
            }

            require_once 'DspaceRestapiHarvester/Harvest/RestHarvester.php';
            $harvester = new DspaceRestapiHarvester_Harvest_RestHarvester($harvest);
            $harvester->harvest();                  */

            /*** comments out above for job dispatcher */

        } catch (Exception $e) {
            $harvest->status = DspaceRestapiHarvester_Harvest::STATUS_ERROR;
            $harvest->addStatusMessage(
                get_class($e) . ': ' . $e->getMessage(),
                DspaceRestapiHarvester_Harvest_RestHarvester::MESSAGE_CODE_ERROR
            );
            throw $e;
        }

        if ($collectionId) {
            $message = "Collection \"$collectionId\" is being harvested and may take a while. Please check below for status.";
        } else {
            $message = "Repository \"$baseUrl\" is being harvested and may take a while. Please check below for status.";
        }
        if ($harvest->start_from) {
            $message = $message." Harvesting is continued from $harvest->start_from .";
        }
        $this->_helper->flashMessenger($message, 'success');
        return $this->_helper->redirector->goto('index');
    }

    /**
      * Prepare the status view.
      *
      * @return void
      */
     public function statusAction()
     {
         $harvestId = $this->_getParam('harvest_id');
         $harvest = $this->_helper->db->getTable('DspaceRestapiHarvester_Harvest')->find($harvestId);
         $this->view->harvest = $harvest;
     }

     /**
      * Delete all items created during a harvest.
      *
      * @return void
      */
     public function deleteAction()
     {
         // Throw if harvest does not exist or access is disallowed.
         $harvestId = $this->_getParam('id');
         $harvest = $this->_helper->db->getTable('DspaceRestapiHarvester_Harvest')->find($harvestId);
         $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');
         $jobDispatcher->setQueueName('imports');
         $jobDispatcher->sendLongRunning('DspaceRestapiHarvester_DeleteJob',
             array(
                 'harvestId' => $harvest->id,
             )
         );
         $msg = 'Harvest has been marked for deletion.';
         $this->_helper->flashMessenger($msg, 'success');
         return $this->_helper->redirector->goto('index');
     }

}
