<?php
/**
 * @package DspaceRestapiHarvester
 * @subpackage Models
 * @copyright Copyright 2008-2013 Fondren Library at Rice University
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @author Ying Jin ying.jin@rice.edu
 */

/**
 * Model class for a record.
 *
 * @package DspaceRestapiHarvester
 * @subpackage Models
 */
class DspaceRestapiHarvester_ElementSet extends Omeka_Record_AbstractRecord
{
    public $id;
    public $elementset_id;
    public $elementset_name;
    public $elementset_label;

}
