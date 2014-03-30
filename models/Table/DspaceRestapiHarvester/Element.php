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
 */
class Table_DspaceRestapiHarvester_Element extends Omeka_Db_Table
{
    /**
     * Return all harvests.
     *
     * @return array An array of all DspaceRestapiHarvester_Elementset objects, ordered by
     * ID.
     */
    public function findAll()
    {
        $tableAlias = $this->getTableAlias();
        $select = $this->getSelect()->order("$tableAlias.id DESC");
        return $this->fetchObjects($select);
    }

}