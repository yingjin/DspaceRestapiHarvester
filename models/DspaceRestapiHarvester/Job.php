<?php
/**
 * @package DspaceRestapiHarvester
 * @subpackage Models
 * @copyright Copyright 2008-2013 Fondren Library at Rice University
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @author Ying Jin ying.jin@rice.edu
 * @modified from OaipmhHarvester
 */

class DspaceRestapiHarvester_Job extends Omeka_Job_AbstractJob
{
    private $_memoryLimit;
    private $_harvestId;

    public function perform()
    {
        if ($memoryLimit = dspace_restapi_harvester_config('memoryLimit')) {
            ini_set('memory_limit', $memoryLimit); 
        }
        // Set the set.
        $harvest = $this->_db->getTable('DspaceRestapiHarvester_Harvest')
                             ->find($this->_harvestId);
        if (!$harvest) {
            throw new UnexpectedValueException(
                "Harvest with id = '$this->_harvestId' does not exist.");
        }

        // Resent jobs can remain queued after all the items themselves have 
        // been deleted. Skip if that's the case.
        if ($harvest->status == DspaceRestapiHarvester_Harvest::STATUS_DELETED) {
            _log("Queued harvest with ID = {$harvest->id} was deleted prior "
               . "to running this job.");
            return;
        }

        require_once 'DspaceRestapiHarvester/Harvest/RestHarvester.php';
        $harvester = new DspaceRestapiHarvester_Harvest_RestHarvester($harvest);
        $harvester->harvest();

    }

    public function setHarvestId($id)
    {
        $this->_harvestId = $id;
    }
}
