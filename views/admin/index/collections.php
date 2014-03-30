<?php
/**
 * Admin index view.
 *
 * @package DSpaceRestapiHarvester
 * @subpackage Views
 * @copyright Copyright 2008-2013 Fondren Library at Rice University
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 * @author Ying Jin ying.jin@rice.edu
 * @modified from OaipmhHarvester
 */

$head = array('body_class' => 'dspace-restapi-harvester content',
              'title'      => 'Dspace Restapi Harvester | Harvest');
echo head($head);
?>
<div id="primary">
    <?php echo flash(); ?>
    <h2>Data provider: <?php echo html_escape($this->baseUrl); ?></h2>

    <h3>Harvest a collection:</h3>
    <?php if ($this->collections): ?>
    <table>
        <thead>
            <tr>
                <th>Collection Name</th>
                <th>Collection Handle</th>
                <th>Harvest</th>
            </tr>
        </thead>
        <tbody>

    <?php foreach ($this->collections as $collection): ?>
    <?php 
    if ($collection['shortDescription']):
        $collectionSpec = $collection['shortDescription'];
    else:
        $collectionSpec = null;
    endif; ?>
            <tr>
                <td>
                    <?php if ($collection['name']): ?>
                    <strong><?php echo html_escape($collection['name']); ?></strong>
                    <?php endif; ?>
                    <?php if ($collectionSpec): ?>
                    <p><?php echo html_escape($collectionSpec); ?></p>
                    <?php endif; ?>
                </td>
                <td><?php echo html_escape($collection['handle']); ?></td>
                <td style="white-space: nowrap"><form method="post" action="<?php echo url('dspace-restapi-harvester/index/harvest'); ?>">
                <?php echo $this->formHidden('base_url', $this->baseUrl); ?>
                <?php echo $this->formHidden('source_collection_id', $collection['id']); ?>
                <?php echo $this->formHidden('collection_name', $collection['name']); ?>
                <?php echo $this->formHidden('collection_spec', @ $collectionSpec); ?>
                <?php echo $this->formHidden('collection_handle', $collection['handle']); ?>
                <?php echo $this->formSubmit('submit_harvest', 'Go'); ?>
                </form></td>
            </tr>
    <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>This repository does not allow you to harvest individual collections.</p>
    <?php endif; ?>
</div>
<?php echo foot(); ?>
