<?php if (!$testForm->isValid()): ?>
  <h1>Run tests on staging</h1>
  <p>To run tests on the code currently in the staging area type 'test' in the box below and click the button.</p>
  <?php include_partial('sbDeploy/form', array('form' => $testForm)) ?>
<?php else: ?>
 
  <?php slot('javascript', get_partial('sbDeploy/javascript', array('formName' => 'test'))); ?>

  <h1>Running tests on staging</h1>
  <ol id="taskList">
    <li>Running tests on staging... <img src="/images/ajax-loader.gif" id="ajaxLoader"></li>
  </ol>

  <h2 id="finalFeedback" ></h2>

<?php endif ?>