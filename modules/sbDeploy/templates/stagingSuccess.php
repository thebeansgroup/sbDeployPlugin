<?php if (!$stagingForm->isValid()): ?>
  <h1>Stage</h1>
  <p>To copy the trunk to the staging area type 'staging' in the box below and click the button.</p>
  <?php include_partial('sbDeploy/form', array('form' => $stagingForm)) ?>
<?php else: ?>

<?php slot('javascript', get_partial('sbDeploy/javascript', array('formName' => 'staging'))); ?>

<h1>Deploying <?php echo sfConfig::get('app_site_name'); ?> to staging...</h1>
<ol id="taskList">
  <li>Updating deployment working copy with 'svn up'... <img src="/images/ajax-loader.gif" id="ajaxLoader"></li>
</ol>

<h2 id="finalFeedback" ></h2>
<?php include_partial('sbDeploy/testForm', array('form' => $testForm)) ?>

<?php endif ?>