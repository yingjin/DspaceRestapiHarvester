<?php
/**
 * DspaceRestapiHarvesterPlugin class - represents the DSpace Connector plugin
 *
 * @package DspaceRestapiHarvester
 * @copyright Copyright 2008-2013 Fondren Library at Rice University
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @author Ying Jin ying.jin@rice.edu
 * @modified from OaipmhHarvester
 */

/** Path to plugin directory */
defined('DSPACE_RESTAPI_HARVESTER_PLUGIN_DIR')
    or define('DSPACE_RESTAPI_HARVESTER_PLUGIN_DIR', dirname(__FILE__));

require_once dirname(__FILE__) . '/functions.php';

/**
 * Dspace Connector plugin.
 */
class DspaceRestapiHarvesterPlugin extends Omeka_Plugin_AbstractPlugin
{
    
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array('install', 
                              'uninstall',
                              'admin_items_show',
                              'public_items_show',
                              'public_items_browse_each'
                            );

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array('admin_navigation_main');

    /**
     * @var array Options and their default values.
     */
    protected $_options = array();

    private $_elementSets;
    private $_elements;

    public function __construct(){
           parent::__construct();

           $ini_array = parse_ini_file("elements.ini");
           foreach($ini_array as $setKey => $setValue) {
               if(strpos($setKey,'.') == false){
               // this is for the elementSet label
                  $elementSets[$setKey] = $setValue;

               }else{
                  $elements[$setKey] = $setValue;
               }
            }
           // Set the elements.
           $this->_elementSets = $elementSets;
           $this->_elements = $elements;
       }

    /**
     * read in element ini file
     */

    /**
     * Install the plugin.
     */
    public function hookInstall()
    {
        $db = $this->_db;

        $sql = "
        CREATE TABLE IF NOT EXISTS `{$db->prefix}dspace_restapi_harvester_harvests` (
          `id` int unsigned NOT NULL auto_increment,
          `collection_id` int unsigned default NULL,
          `source_collection_id` text NOT NULL,
          `base_url` text NOT NULL,
          `collection_spec` text,
          `collection_name` text,
          `collection_handle` text,
          `collection_size` int unsigned default NULL,
          `status` enum('queued','in progress','completed','error','deleted','killed') NOT NULL default 'queued',
          `status_messages` text,
          `resumption_token` text,
          `initiated` datetime default NULL,
          `completed` datetime default NULL,
          `start_from` datetime default NULL,
          PRIMARY KEY  (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

        $sql = "
        CREATE TABLE IF NOT EXISTS `{$db->prefix}dspace_restapi_harvester_records` (
          `id` int unsigned NOT NULL auto_increment,
          `harvest_id` int unsigned NOT NULL,
          `item_id` int unsigned default NULL,
          `identifier` text NOT NULL,
          `handle` text NOT NULL,
          `datestamp` tinytext NOT NULL,
          PRIMARY KEY  (`id`),
          KEY `identifier_idx` (identifier(255)),
          UNIQUE KEY `item_id_idx` (item_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

        $sql = "
        CREATE TABLE IF NOT EXISTS `{$db->prefix}dspace_restapi_harvester_elementsets` (
          `id` int unsigned NOT NULL auto_increment,
          `elementset_id` int unsigned NOT NULL,
          `elementset_name` text NOT NULL,
          `elementset_label` text NOT NULL,
          PRIMARY KEY  (`id`),
          KEY `elementset_name_idx` (elementset_name(255)),
          UNIQUE KEY `elementset_id_idx` (elementset_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

        $sql = "
        CREATE TABLE IF NOT EXISTS `{$db->prefix}dspace_restapi_harvester_elements` (
          `id` int unsigned NOT NULL auto_increment,
          `element_id` int unsigned NOT NULL,
          `elementset_id` int unsigned NOT NULL,
          `element_name` text NOT NULL,
          `element_label` text NOT NULL,
          PRIMARY KEY  (`id`),
          KEY `element_name_idx` (element_name(255)),
          UNIQUE KEY `element_id_idx` (element_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

        /** prepare the elements if they are not there */
        // Add the new element set.
        if ($this->_elementSets){
            $setIds = array();
            while ((list($setKey, $setValue) = each($this->_elementSets))){

                $findSet = $db->getTable('ElementSet')->findByName($setValue);
                if(!$findSet){
                    // insert the set to omeka elementset first if can't find it in the table
                    $sql = "
                    INSERT INTO `{$db->ElementSet}` (`name`)
                    VALUES (?)";
                    $db->query($sql, $setValue);

                    $findSet = $db->getTable('ElementSet')->findByName($setValue);
                }
                // insert into dspace_restapi_harvester elementsets too
                $sql = "
                INSERT INTO `{$db->DspaceRestapiHarvester_Elementset}` (`elementset_id`, `elementset_name`, `elementset_label`)
                VALUES (?, ?, ?)";
                $db->query($sql, array($findSet->id, $setKey, $setValue));

                $setIds[$setKey] = $findSet->id;

            }
            // go through the elements
            if($this->_elements){
                while (list($elementKey, $elementValue) = each($this->_elements)){

                    $setKey = substr($elementKey, 0, strpos($elementKey, '.'));
                    $setId = $setIds[$setKey];

                    $findElement = $db->getTable('Element')->findByElementSetNameAndElementName($this->_elementSets[$setKey], $elementValue);

                if (!$findElement) {
                    // insert into omeka elements table
                    $sql = "
                    INSERT INTO `{$db->Element}` (`element_set_id`, `name`)
                    VALUES (?, ?)";
                    $db->query($sql, array($setId, $elementValue));

                    $findElement = $db->getTable('Element')->findByElementSetNameAndElementName($this->_elementSets[$setKey], $elementValue);
                }
                // insert into dspace_restapi_harvester elements table too
                $sql = "
                INSERT INTO `{$db->DspaceRestapiHarvester_Element}` (`elementset_id`, `element_id`, `element_name`, `element_label`)
                VALUES (?, ?, ?, ?)";
                $db->query($sql, array($setId, $findElement->id, $elementKey, $elementValue));

              }
            }
        }
    }

    /**
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {
        $db = $this->_db;
        
        // drop the tables        
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}dspace_restapi_harvester_harvests`;";
        $db->query($sql);
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}dspace_restapi_harvester_records`;";
        $db->query($sql);
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}dspace_restapi_harvester_elements`;";
        $db->query($sql);
        $sql = "DROP TABLE IF EXISTS `{$db->prefix}dspace_restapi_harvester_elementsets`;";
        $db->query($sql);

        $this->_uninstallOptions();
    }



    /**
     * Render the datastream on public show page.
     *
     * @return void.
     */
    public function hookPublicItemsShow()
    {
    
    	$this->showItemBitstream();
        /*// Get the record.
        $record = get_current_record('item');
        $id = $record->id; // this is the item_id from omeka
        $existRecord = $this->_db->getTable('DspaceRestapiHarvester_Record')->findByItemId($id);
        $source_item_id = $existRecord->identifier;

        if ($existRecord) {
	    // get the harvest info and then complie the bitstream list from it
	    $harvest_id = $existRecord->harvest_id;
	    $handle = $existRecord->handle;
            $harvester = $this->_db->getTable('DspaceRestapiHarvester_Harvest')->findByHarvestId($harvest_id);
	    $bitstreams = $harvester->listBitstreams($source_item_id, $handle);
	    $thumbList = $bitstreams["thumbnail"];
	    $origList = $bitstreams["original"];
     	$html = "";

	    if($origList){
	        foreach($origList as $key => $value){
	            if(array_key_exists($key, $thumbList)){
        	        // Construct HTML.
        	        $html .= "<a href='{$value}'><img alt='{$key}' src='{$thumbList[$key]}' /></a><br/>";
		    }else{
        	        $html .= "<a href='$value'>{$key}</a><br/>";
		    }
		}
                echo $html;
	    }else{
	        echo "No original Files !!!";
	    }
        }else{echo "No existing Records!!!";}*/
     }

    /**
      * Render the datastream on public show page.
      *
      * @return void.
      */
     public function hookAdminItemsShow()
     {
            $this->showItemBitstream();
      }

    public function hookPublicItemsBrowseEach()
    {
          $this->showItemBitstream();
     }

    /**
     * Add the DSpace Connector link to the admin main navigation.
     * 
     * @param array Navigation array.
     * @return array Filtered navigation array.
     */
    public function filterAdminNavigationMain($nav)
    {            
        $nav[] = array(
            'label' => __('Dspace Restapi Harvester'),
            'uri' => url('dspace-restapi-harvester'),
            'privilege' => 'index'
        );
        return $nav;
    }

    public function showItemBitstream(){

                // Get the record.
        $record = get_current_record('item');
        $id = $record->id; // this is the item_id from omeka
        $existRecord = $this->_db->getTable('DspaceRestapiHarvester_Record')->findByItemId($id);
        $source_item_id = $existRecord->identifier;

        if ($existRecord) {
	   		// get the harvest info and then complie the bitstream list from it
	    	$harvest_id = $existRecord->harvest_id;
    	    $handle = $existRecord->handle;
            $harvester = $this->_db->getTable('DspaceRestapiHarvester_Harvest')->findByHarvestId($harvest_id);
		    $bitstreams = $harvester->listBitstreams($source_item_id, $handle);
	        $thumbList = $bitstreams["thumbnail"];
	    	$origList = $bitstreams["original"];
     		
		    if($origList){
		        foreach($origList as $key => $value){
	    	        if(array_key_exists($key, $thumbList)){
        		        // Construct HTML.
        	    	    $html .= "<a href='{$value}'><img alt='{$key}' src='{$thumbList[$key]}' /></a><br/>";
        	        	
			    	}else{
    	    	       $html .= "<a href='$value'>{$key}</a><br/>";
        		       
		    		}
				}
            	echo $html;
	    	}else{
 				echo "No original Files !!!";
	    	}
        }else{
			echo "No existing Records!!!";
        }	
        
    } 
}
