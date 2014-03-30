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
 * Model class for a harvest table.
 *
 * @package DspaceRestapiHarvester
 * @subpackage Models
 */
class Table_DspaceRestapiHarvester_Harvest extends Omeka_Db_Table
{
    /**
     * Return all harvests.
     * 
     * @return array An array of all DspaceRestapiHarvester_Harvest objects, ordered by
     * ID.
     */
    public function findAll()
    {
        $tableAlias = $this->getTableAlias();
        $select = $this->getSelect()->order("$tableAlias.id DESC");
        return $this->fetchObjects($select);
    }

    /**
     * Find a harvest by base URL and collectionHandle.  These are the components
     * required to make a harvest unique.
     *
     * @param string $baseUrl Base URL of the harvest
     * @param string $collectionHandle collectionHandle of the harvest
     * @return DspaceRestapiHarvester_Harvest Record of existing harvest.
     */
    public function findUniqueHarvest($baseUrl, $collectionHandle)
    {
        $tableAlias = $this->getTableAlias();
        $select = $this->getSelect()->where("$tableAlias.base_url = ?", $baseUrl);

        if ($collectionHandle)
            $select->where("$tableAlias.collection_handle = ?", $collectionHandle);
        else
            $select->where("$tableAlias.collection_handle IS NULL");
                
        return $this->fetchObject($select);
    }

    public function findByHarvestId($harvestId)
    {
        $tableAlias = $this->getTableAlias();
        $select = $this->getSelect()->where("$tableAlias.id = ?", $harvestId);

        return $this->fetchObject($select);
    }
}
