<form action="<?php echo url_for('sbDeploy/' . $form->getOption('submitAction')) ?>" method="post">
<?php echo $form->renderGlobalErrors() ?>
<?php echo $form['confirmation']->renderError() ?>
<?php echo $form['confirmation']->render() ?>
<input type="submit" value="Type '<?php echo $form->getOption('type') ?>' and click here" onclick="this.disabled=true" />
</form>