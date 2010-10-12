<form action="<?php echo url_for('sbDeploy/' . $form->getOption('submitAction')) ?>" method="post">
  <?php echo $form->renderGlobalErrors() ?>
  <?php if (isset($form['repo_uri'])): ?>
  <?php echo $form['repo_uri']->renderError() ?>
  <?php echo $form['repo_uri']->renderLabel() ?>
  <?php echo $form['repo_uri']->render() ?>
  <?php endif ?>
  <br />
  <?php echo $form['confirmation']->renderError() ?>
  <?php echo $form['confirmation']->render() ?>
    <input type="submit" value="Type '<?php echo $form->getOption('type') ?>' and click here" onclick="this.disabled=true" />
</form>