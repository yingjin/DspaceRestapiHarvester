<?php
/**
 * @package DspaceRestapiHarvester
 * @subpackage Models
 * @copyright Copyright 2008-2013 Fondren Library at Rice University
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @author Ying Jin ying.jin@rice.edu
 */

/**
 * Model class for a record table.
 *
 * @package DspaceRestapiHarvester
 * @subpackage Models
 * @copyright Copyright 2008-2013 Fondren Library at Rice University
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @author Ying Jin ying.jin@rice.edu
 * @modified from OaipmhHarvester
 */
class Table_DspaceRestapiHarvester_Record extends Omeka_Db_Table
{
    /**
     * Return records by harvest ID.
     * 
     * @param int $harvsetId
     * @return array An array of DspaceRestapiHarvester_Record objects.
     */
    public function findByHarvestId($harvestId)
    {
        $tableAlias = $this->getTableAlias();
        $select = $this->getSelect();
        $select->where("$tableAlias.harvest_id = ?");
        return $this->fetchObjects($select, array($harvestId));
    }
    

    /**
     * Return records by item ID.
     * 
     * @param mixes $itemId Item ID
     * @return DspaceRestapiHarvester_Record Record corresponding to item id.
     */
    public function findByItemId($itemId)
    {
        $tableAlias = $this->getTableAlias();
        $select = $this->getSelect();
        $select->where("$tableAlias.item_id = ?");
        return $this->fetchObject($select, array($itemId));
    }

    public function applySearchFilters($select, $params)
    {
        $tableAlias = $this->getTableAlias();
        $harvestTableAlias = $this->_db->getTable('DspaceRestapiHarvester_Harvest')->getTableAlias();
        $harvestKeys = array(
            'base_url',
            'collection_handle'
        );
        if (array_intersect($harvestKeys, array_keys($params))) {
            $this->_join($select, 'Harvest');
            foreach ($harvestKeys as $key) {
                if (array_key_exists($key, $params)) {
                    if ($params[$key] === null) {
                        $select->where("$harvestTableAlias.$key IS NULL");
                    } else {
                        $select->where("$harvestTableAlias.$key = ?", $params[$key]);
                    }
                }
            }
        }
        if (array_key_exists('identifier', $params))
        {
            $select->where("$tableAlias.identifier = ?", $params['identifier']);
        }
    }

    private function _join($select, $tableName)
    {
        $tableAlias = $this->getTableAlias();
        $harvestTable = $this->_db->getTable('DspaceRestapiHarvester_Harvest');
        $harvestTableAlias = $harvestTable->getTableAlias();
        switch ($tableName) {
            case 'Harvest':
                $select->joinInner(
                    array($harvestTableAlias => $harvestTable->getTableName()),
                    "$harvestTableAlias.id = $tableAlias.harvest_id",
                    array()
                );
                break;
            default:
                break;
        }
    }
}