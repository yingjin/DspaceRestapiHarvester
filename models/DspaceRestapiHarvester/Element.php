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
class DspaceRestapiHarvester_Element extends Omeka_Record_AbstractRecord
{
    public $id;
    public $element_id;
    public $elementset_id;
    public $element_name;
    public $element_label;

}
