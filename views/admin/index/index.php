<?php
/**
 * Admin index view.
 * 
 * @package DSpaceConnector
 * @subpackage Views
 * @copyright Copyright 2008-2013 Fondren Library at Rice University
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @author Ying Jin ying.jin@rice.edu
 * @modified from OaipmhHarvester
 */

$head = array('title'      => 'Dspace Restapi Harvester',
              'body_class' => 'primary dspace-restapi-harvester');
echo head($head);
?>
<script>
  $(function() {
    $( "#tabs" ).tabs();
  });
  </script>
<style type="text/css">
.base-url, .harvest-status {
    white-space: nowrap;
}

.base-url div{
    max-width: 18em;
    overflow: hidden;
    text-overflow: ellipsis;
}

.harvest-status input[type="submit"] {
    margin: .25em 0 0 0;
}
</style>



<div id="primary">
  <div id="tabs-1">
        <?php echo flash(); ?>

    <h2>Data Provider</h2>
    <?php echo $this->harvestForm; ?>
    <br/>
    <div id="harvests">
    <h2>Harvests</h2>
    <?php if (empty($this->harvests)): ?>
    <p>There are no harvests.</p>
    <?php else: ?>
    <table>
       <thead>
            <tr>
                <th>Base URL</th>
                <th>Collection Name</th>
                <th>Collection Handle</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($this->harvests as $harvest): ?>
            <tr>
                <td title="<?php echo html_escape($harvest->base_url); ?>" class="base-url">
                    <div><?php echo html_escape($harvest->base_url); ?></div>
                </td>
                <td>
                    <?php
                    if ($harvest->collection_spec):
                        echo html_escape($harvest->collection_name)
                            . ' (' . html_escape($harvest->collection_spec) . ')';
                    else:
                        echo html_escape($harvest->collection_name);
                    endif;
                    ?>
                </td>
                <td><?php echo $harvest->collection_handle; ?></td>
                <td class="harvest-status">
                    <a href="<?php echo url("dspace-restapi-harvester/index/status?harvest_id={$harvest->id}"); ?>"><?php echo html_escape(ucwords($harvest->status)); ?></a>
                    <?php if ($harvest->status == DspaceRestapiHarvester_Harvest::STATUS_COMPLETED): ?>
                        <br>
                        <form method="post" action="<?php echo url('dspace-restapi-harvester/index/harvest');?>">
                        <?php echo $this->formHidden('harvest_id', $harvest->id); ?>
                        <?php echo $this->formSubmit('submit_reharvest', 'Re-Harvest'); ?>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    </div>
  </div>
</div>

</div>
<?php echo foot(); ?>
