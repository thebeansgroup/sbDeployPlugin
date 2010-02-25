<?php
/*
 * Registers routes
 */
$this->dispatcher->connect('routing.load_configuration', 
  array('sbDeployRouting', 'listenToRoutingLoadConfigurationEvent')
);
