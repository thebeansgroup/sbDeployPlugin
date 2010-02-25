<?php

/**
 * Adds routes for the plugin
 * 
 * @package    symfony
 * @subpackage plugin
 * @author     Al
 */
class sbDeployRouting
{
  /**
   * Listens to the routing.load_configuration event.
   *
   * @param sfEvent An sfEvent instance
   */
  static public function listenToRoutingLoadConfigurationEvent(sfEvent $event)
  {
    $r = $event->getSubject();

    // preprend our routes
    $r->prependRoute('sb_deploy',
      new sfRoute('/deploy',
        array(
          'module' => 'sbDeploy',
          'action' => 'index'
        )
      )
    );
    $r->prependRoute('sb_deploy_staging',
      new sfRoute('/deploy/staging',
        array(
          'module' => 'sbDeploy',
          'action' => 'staging'
        )
      )
    );
    $r->prependRoute('sb_deploy_test',
      new sfRoute('/deploy/test',
        array(
          'module' => 'sbDeploy',
          'action' => 'test'
        )
      )
    );
    $r->prependRoute('sb_deploy_production',
      new sfRoute('/deploy/production',
        array(
          'module' => 'sbDeploy',
          'action' => 'production'
        )
      )
    );
    $r->prependRoute('sb_deploy_ajax_command',
      new sfRoute('/deploy/ajax/:form/:step',
        array(
          'module' => 'sbDeploy',
          'action' => 'ajax'
        )
      )
    );
  }
}
