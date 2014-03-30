<?php
/**
 * Admin status view.
 *
 * @package DspaceRestapiHarvester
 * @subpackage Views
 */



$head = array('body_class' => 'dspace-restapi-harvester content',
              'title'      => 'Dspace Restapi Harvester | Status');
echo head($head);
?>

<?php echo flash(); ?>
<table>
    <tr>
        <td>ID</td>
        <td><?php echo html_escape($this->harvest->id); ?></td>
    </tr>
    <tr>
        <td>Collection Spec</td>
        <td><?php echo html_escape($this->harvest->collection_spec); ?></td>
    </tr>
    <tr>
        <td>Collection Name</td>
        <td><?php echo html_escape($this->harvest->collection_name); ?></td>
    </tr>
    <tr>
        <td>Collection Handle</td>
        <td><?php echo html_escape($this->harvest->collection_handle); ?></td>
    </tr>
    <tr>
        <td>Base URL</td>
        <td><?php echo html_escape($this->harvest->base_url); ?></td>
    </tr>
    <tr>
        <td>Status</td>
        <td><?php echo html_escape(ucwords($this->harvest->status)); ?></td>
    </tr>
    <tr>
        <td>Initiated</td>
        <td><?php echo html_escape($this->harvest->initiated); ?></td>
    </tr>
    <tr>
        <td>Completed</td>
        <td><?php echo $this->harvest->completed ? html_escape($this->harvest->completed) : html_escape('[not completed]'); ?></td>
    </tr>
    <tr>
        <td>Status Messages</td>
        <td><?php echo html_escape($this->harvest->status_messages); ?></td>
    </tr>
</table>

<?php if ($this->harvest->status != DspaceRestapiHarvester_Harvest::STATUS_DELETED): ?>
<p><strong>Warning:</strong> Clicking the following link will delete all items created for this harvest. 
<?php //echo link_to($this->harvest, 'delete-confirm', 'Delete Items', array('class' => 'delete-button')); ?>
<a href="<?php echo url(array('id' => $this->harvest->id, 'action' => 'delete'), 'default'); ?>" class="delete-button">Delete Items</a>

<?php endif; ?>

<?php echo foot(); ?>