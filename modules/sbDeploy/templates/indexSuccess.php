<h1>Stage and Deploy <?php echo sfConfig::get('app_site_name'); ?></h1>
<p>Project name set to <span class="success"><?php echo $projectName ?></span>.</p>
<p>This script will deploy the trunk of <?php echo sfConfig::get('app_site_name'); ?> to staging on the production
      servers. It will perform the following actions:</p>

<ol>
  <li>Update a local copy of the trunk.</li>
  <!--li>Run back-ups on production machines.</li-->
  <li>Deploy code to staging.</li>
  <li>Run post-deployment tasks.</li>
  <li>Run automated tests.</li>
</ol>

<p>If all tests are successful, you will be given the option of pushing code through to
      production.</p>

<h2 class="separate">Jump to option:</h2>
<ul>
  <li>
    <?php echo link_to('Stage', url_for('sb_deploy_staging')) ?>
  </li>
  <li>
    <?php echo link_to('Test', url_for('sb_deploy_test')) ?>
  </li>
  <li>
    <?php echo link_to('Build production', url_for('sb_deploy_production')) ?>
  </li>
</ul>

<h2 class="separate">Stage</h2>

<p>To copy the trunk to the staging area type 'staging' in the box below and click the button.</p>

<?php include_partial('sbDeploy/form', array('form' => $stagingForm)) ?>

<?php //include_partial('sbDeploy/testForm', array('form' => $testForm)) ?>
