<?php if (!$productionForm->isValid()): ?>
  <h1>Build to production</h1>
  <p>To deploy the contents of the staging area to production type 'production' in the
    box below and click the button.</p>
  <?php include_partial('sbDeploy/form', array('form' => $productionForm)) ?>

<?php else: ?>

  <?php slot('javascript', get_partial('sbDeploy/javascript', array('formName' => 'production'))); ?>

  <h1>Deploying <?php echo sfConfig::get('app_site_name'); ?> to production...</h1>

  <ol id="taskList">
    <li>Prepping revision notes... <img src="/images/ajax-loader.gif" id="ajaxLoader"></li>
  </ol>

  <h2 id="finalFeedback" ></h2>

<?php endif ?>