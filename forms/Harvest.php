<?php

/**
 * @package DspaceRestapiHarvester
 * @subpackage Forms
 * @copyright Copyright 2008-2013 Fondren Library at Rice University
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @author Ying Jin ying.jin@rice.edu
 * @modified from OaipmhHarvester
 */

class DspaceRestapiHarvester_Form_Harvest extends Omeka_Form
{
    public function init()
    {
        parent::init();
        $this->addElement('text', 'base_url', array(
            'label' => 'Base URL',
            'description' => 'The base URL of the Dspace REST API provider.',
            'size' => 60,
        ));
        
 /*       $this->addElement('select', 'rest_api', array(
            'label' => 'REST API',
            'description' => __('Select REST API Implementation'),
            'multiOptions'  => $this->getRestapiForSelect()
        ));
   */
        $this->applyOmekaStyles();
        $this->setAutoApplyOmekaStyles(false);
        
        $this->addElement('submit', 'submit_view_collections', array(
            'label' => 'View Collections',
            'class' => 'submit submit-medium',
            'decorators' => (array(
                'ViewHelper', 
                array('HtmlTag', array('tag' => 'div', 'class' => 'field'))))
        ));
    }

     /**
     * Get the list of Rest API Implementation.
     *
     * @return array $rest_api.
     */
  /*  public function getRestapiForSelect()
    {
        $rest_api = array();

        $rest_api[0] = 'wijiti';
        $rest_api[1] = 'hedtek';

        return $rest_api;

    } */
}
