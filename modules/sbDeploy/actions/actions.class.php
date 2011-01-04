<?php

/**
 * sbDeploy actions.
 *
 * @package    .
 * @subpackage sbDeploy
 * @author     Ally
 * @version    SVN: $Id: actions.class.php 12479 2008-10-31 10:54:40Z fabien $
 */
class sbDeployActions extends sfActions
{

  /**
   * Imposes restrictions on where this script can be used
   */
  public function preExecute()
  {

    if (strpos($_SERVER['HTTP_HOST'], 'testbox') === false)
    {
//      return $this->setTemplate('invalidServer');
    }

    $this->projectName = TaskUtils::getProjectName();

    $this->setLayout('sbDeployLayout');
    // the previous line isn't working on Symfony 1.4.6
    // $this->setLayout(false);
    if (strlen(trim($this->getRequest()->getParameter('staging_repo_uri', ''))) == 0)
    {
      $repoLocation = trim($this->getRequest()->getParameter('staging[repo_uri]', 'trunk'), '/');
      $this->repoUri = "svn://testbox.beans/projects/{$this->projectName}/" . (strlen($repoLocation) > 0 ? $repoLocation
                        : 'trunk');
    }
    else
    {
      $this->repoUri = $this->getRequest()->getParameter('staging_repo_uri');
    }
    $this->setupProductionFormActions();
    $this->setupStagingFormActions();
    $this->setupTestFormActions();
  }

  /**
   * Lets users deploy with different options
   *
   * @param sfWebRequest $request
   */
  public function executeIndex(sfWebRequest $request)
  {
    $this->setupAndHandleForm($request, 'staging');
  }

  /**
   * Runs tests on staging
   *
   * @param sfWebRequest $request
   */
  public function executeTest(sfWebRequest $request)
  {
    $this->setupAndHandleForm($request, 'test');
  }

  /**
   * Deploys code to the staging environment.
   *
   * @param sfWebRequest $request
   */
  public function executeStaging(sfWebRequest $request)
  {
    $this->setupAndHandleForm($request, 'test');
    $this->setupAndHandleForm($request, 'staging');
  }

  /**
   * Deploys code to the production environment.
   *
   * @param sfWebRequest $request
   */
  public function executeProduction(sfWebRequest $request)
  {
    $this->setupAndHandleForm($request, 'production');
  }

  /**
   * Receives ajax commands
   * 
   * @param sfWebRequest $request 
   */
  public function executeAjax(sfWebRequest $request)
  {
    //$this->forward404Unless($request->isXmlHttpRequest());

    $previousResult = $request->getParameter('previousResult');
    $previousResult = ($request->getParameter('step') == 0) ? true : $previousResult;

    $data = $this->performFormAction(
                    $request->getParameter('form'),
                    $request->getParameter('step'),
                    $previousResult
    );

    return $this->renderText(json_encode($data));
  }

  /**
   * Sets up the staging form
   *
   * @param sfWebRequest $request
   */
  protected function setupAndHandleForm(sfWebRequest $request, $name)
  {
    $formName = "{$name}Form";

    $this->$formName = new sbDeployForm(
                    array(),
                    array(
                        'type' => $name,
                        'submitAction' => $name
                    )
    );

    if ($request->isMethod(sfRequest::POST) && $request->getParameter($this->$formName->getName()))
    {
      $this->$formName->bind($request->getParameter($this->$formName->getName()));

      // we'll test for equality in the template. all actions will be performed with
      // ajax
    }
  }

  /**
   * Executes the specified action step of the named form
   *
   * @param string $form The name of the form whose actions we should perform
   * @param int $step The step number of the action to perform
   * @return array An array of feedback and a label for the next message
   */
  protected function performFormAction($form, $step, $previousResult=true)
  {
    // execute the specified action if it exists
    if (isset($this->{"{$form}Actions"}[$step]))
    {
      $action = $this->{"{$form}Actions"}[$step];
    }

    $return = array();

    if (!$previousResult)
    {
      $return['feedback'] = 'Aborted due to previous failures';
      $return['success'] = false;
      return $return;
    }

    if (is_array($action))
    {
      // retrieve previous output if we need to
      if (isset($action['usePreviousCommandOutput']) && $action['usePreviousCommandOutput'] === true)
      {
        $previousOutput = $this->getUser()->getAttribute('previousCommandOutput');
        $this->logMessage("retrieved previous output from session: $previousOutput", 'info');
      }
      else
      {
        $previousOutput = '';
      }

      // execute the command
      if (isset($action['preg_match']))
      {
        $subject = $action['preg_match']['subject'];
        $subject = str_replace('%previousOutput%', $previousOutput, $subject);

        $regex = $action['preg_match']['regex'];
        $regex = str_replace('%previousOutput%', $previousOutput, $regex);

        $this->logMessage("running preg_match: regex: $regex, subject: $subject", 'info');

        $matches = array();
        preg_match($regex, $subject, $matches);

        $matchIndex = $action['preg_match']['matchIndex'];
        $result = false;

        if (isset($matches[$matchIndex]))
        {
          $output = $matches[$matchIndex];
          $result = true;
        }
      }
      elseif (isset($action['shell_exec']))
      {
        $this->logMessage("executing command with shell_exec: {$action['shell_exec']}", 'info');

        $command = str_replace('%previousOutput%', $previousOutput, $action['shell_exec']);

        $output = shell_exec($command);
        $result = true;
      }
      else
      {
        $output = 'No command specified';
        $result = false;
      }

      $this->logMessage("output: $output", 'info');

      // evaluate success
      if (isset($action['strpos']))
      {
        foreach ($action['strpos'] as $strPos)
        {
          $pos = strpos($output, $strPos['string']);

          if (is_numeric($strPos['value']))
          {
            $result = $result && eval("return $pos {$strPos['test']} '{$strPos['value']}';");
          }
          elseif (is_bool($strPos['value']))
          {
            $pos = ($pos === false) ? 'false' : $pos;
            $value = ($strPos['value']) ? 'true' : 'false';
            $result = $result && eval("return ($pos {$strPos['test']} $value);");
          }
          else
          {
            $result = false;
          }

          $this->logMessage("strpos test {$strPos['string']} returned " . (($result) ? 'true' : 'false'), 'info');
        }
      }

      if (isset($result) && $result === true)
      {
        $return['feedback'] = $action['messages']['success'];

        // now retrieve the label and number of the next step
        if (isset($this->{"{$form}Actions"}[$step + 1]))
        {
          $nextAction = $this->{"{$form}Actions"}[$step + 1];

          if (isset($nextAction['messages']['label']))
          {
            $return['nextLabel'] = $nextAction['messages']['label'];
            $return['nextStep'] = $step + 1;
          }
        }

        // if we need to store the output of the command to the session, do it
        if (isset($action['saveOutputToSession']) && $action['saveOutputToSession'] === true)
        {
          $this->logMessage("saving output to session", 'info');
          $this->getUser()->setAttribute('previousCommandOutput', $output);
        }
      }
      elseif (isset($result) && $result === false)
      {
        $return['feedback'] = $action['messages']['error'];
      }

      $status = $result && $previousResult;

      if (isset($this->{"{$form}Actions"}[$step + 1]['finalMessages']))
      {
        if ($status)
        {
          $return['finalMessage'] = $this->{"{$form}Actions"}[$step + 1]['finalMessages']['success'];
        }
        else
        {
          $return['finalMessage'] = $this->{"{$form}Actions"}[$step + 1]['finalMessages']['error'];
        }
      }

      // replace the output placeholder with the result of running the code
      $return['feedback'] = str_replace('%output%', $output, $return['feedback']);
      $return['feedback'] = str_replace('%previousOutput%', $previousOutput, $return['feedback']);
      $return['success'] = $status;
    }

    return $return;
  }

  /**
   * Sets up an array defining actions for the staging form
   */
  protected function setupStagingFormActions()
  {
    $this->stagingActions = array(
        // swtich to correct repo
        array(
            'messages' => array(
                'label' => "Switch to repo at $this->repoUri with 'svn switch'...",
                'success' => '%output%',
                'error' => 'Failed to switch to correct repo. Aborting.'
            ),
            'shell_exec' => "svn switch $this->repoUri ../../../ | tail -n 1",
            'strpos' => array(
                array(
                    'string' => 'revision',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // update svn
        array(
            'messages' => array(
                'label' => "Updating deployment working copy with 'svn up'...",
                'success' => '%output%',
                'error' => 'Failed to update deployment working copy. Aborting.'
            ),
            'shell_exec' => 'svn up ../../../ | tail -n 1',
            'strpos' => array(
                array(
                    'string' => 'revision',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // clear the symfony cache
        array(
            'messages' => array(
                'label' => "Clearing the symfony cache...",
                'success' => 'cache cleared',
                'error' => 'Failed to clear the symfony cache. Aborting.'
            ),
            'shell_exec' => '../symfony cc',
            'strpos' => array(
                array(
                    'string' => 'Clearing cache',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // compile less
        array(
            'messages' => array(
                'label' => "Compiling less...",
                'success' => 'less compiled',
                'error' => 'failed to compile less. Aborting.'
            ),
            'shell_exec' => '../symfony lc',
            'strpos' => array(
                array(
                    'string' => 'Less compiled and written to',
                    'test' => '!==',
                    'value' => false
                ),
                array(
                    'string' => 'succesfully minified.',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // combine and compile javascript
        array(
            'messages' => array(
                'label' => "Preparing javascript...",
                'success' => 'javascript ready',
                'error' => 'failed to prepare javascript. Aborting.'
            ),
            'shell_exec' => '../symfony jb',
            'strpos' => array(
                array(
                    'string' => '0 error(s)',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // re-build the model
        array(
            'messages' => array(
                'label' => "Rebuilding model classes... ",
                'success' => 'done',
                'error' => 'Failed to rebuild model classes. Aborting.'
            ),
            'shell_exec' => '../symfony propel:build-model',
            'strpos' => array(
                array(
                    'string' => 'autoload',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // clear the symfony cache
        array(
            'messages' => array(
                'label' => "Clearing the symfony cache...",
                'success' => 'cache cleared',
                'error' => 'Failed to clear the symfony cache. Aborting.'
            ),
            'shell_exec' => '../symfony cc',
            'strpos' => array(
                array(
                    'string' => 'Clearing cache',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // set up staging prior to uploading
        array(
            'messages' => array(
                'label' => 'Setting up staging environment prior to upload... ',
                'success' => 'success',
                'error' => 'failed. Message was: %output%'
            ),
            'shell_exec' => "ssh upload@web1 /var/www/html/staging/{$this->projectName}/symfony site:setup staging",
            'strpos' => array(
                array(
                    'string' => 'Clearing the Symfony cache...',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // copy code to staging
        array(
            'messages' => array(
                'label' => 'Uploading code to staging environment... ',
                'success' => 'success',
                'error' => 'failed. Output was: %output%'
            ),
            'shell_exec' => "../symfony site:deploy-to-staging",
            'strpos' => array(
                // all of these will be combined with &&. All must be true for the execution to have
                // been successful
                array(
                    'string' => 'rsync',
                    'test' => '!==',
                    'value' => false
                ),
                array(
                    'string' => 'PHP Stack trace:',
                    'test' => '===',
                    'value' => false
                ),
                array(
                    'string' => 'Call Stack:',
                    'test' => '===',
                    'value' => false
                )
            )
        ),
        // running set-up staging on web1 again
        array(
            'messages' => array(
                'label' => 'Setting up staging environment after upload... ',
                'success' => 'success',
                'error' => 'Failed to set-up staging environment after uploading. Message was: %output%'
            ),
            'shell_exec' => "ssh upload@web1 /var/www/html/staging/{$this->projectName}/symfony site:build-staging",
            'strpos' => array(
                array(
                    'string' => 'Staging build completed successfully.',
                    'test' => '!==',
                    'value' => false
                ),
                array(
                    'string' => 'error',
                    'test' => '===',
                    'value' => false
                )
            )
        ),
        // rsynching across staging servers
        array(
            'messages' => array(
                'label' => 'Synching to other staging servers... ',
                'success' => 'success',
                'error' => 'Failed to synch to other staging servers. Message was: %output%'
            ),
            'shell_exec' => "ssh upload@web1 sudo /usr/local/bin/rsync" .
            ucfirst($this->projectName) . "Staging.sh"
        ),
        array(
            'finalMessages' => array(
                'success' => 'Staging deployment successful. Now run the tests.',
                'error' => 'Staging build did not complete successfully.'
            )
        )
    );
//            var_dump($this->stagingActions);exit;
  }

  /**
   * Sets up an array defining actions for the production form
   */
  protected function setupProductionFormActions()
  {
    $this->productionActions = array(
        // get revision notes to apply for this build
        array(
            'messages' => array(
                'label' => 'Prepping revision notes... ',
                'success' => 'Revision notes to apply are: <br/><pre>%output%</pre>',
                'error' => 'Failed to retrieve revision notes: <pre>%output%</pre>'
            ),
            'shell_exec' => "../symfony site:display-revision-notes"
        ),
        // back up web1
        array(
            'messages' => array(
                'label' => 'Backing up web1... ',
                'success' => 'success',
                'error' => 'Failed to backup web1. Aborting.'
            ),
            'shell_exec' => "ssh testbox@web1 sudo /usr/local/bin/backup.sh",
            'strpos' => array(
                array(
                    'string' => 'Back up finished',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // back up db server file system
        array(
            'messages' => array(
                'label' => 'Backing up database server file system... ',
                'success' => 'success',
                'error' => 'Failed to backup database server file system. Aborting.'
            ),
            'shell_exec' => "ssh testbox@dbserver sudo /usr/local/bin/backup.sh",
            'strpos' => array(
                array(
                    'string' => 'Back up finished',
                    'test' => '!==',
                    'value' => false
                ),
                array(
                    'string' => 'Permission denied',
                    'test' => '===',
                    'value' => false
                )
            )
        ),
        // back up db server databases
        array(
            'messages' => array(
                'label' => 'Backing up database server databases... ',
                'success' => 'success',
                'error' => 'Failed to backup database server databases. Aborting.'
            ),
            'shell_exec' => "ssh testbox@dbserver sudo /usr/local/bin/backupDBs.php",
            'strpos' => array(
                array(
                    'string' => 'All done',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // build to production
        array(
            'messages' => array(
                'label' => 'Copying code to the production servers... ',
                'success' => 'success',
                'error' => 'Failed to copy files to production. Aborting.'
            ),
            'shell_exec' => "ssh upload@web1 /var/www/html/staging/{$this->projectName}/symfony site:build-production",
            'strpos' => array(
                array(
                    'string' => 'Production build complete.',
                    'test' => '!==',
                    'value' => false
                )
            ),
            // save the output of this command to the user's session because we'll need it in the
            // next step
            'saveOutputToSession' => true
        ),
        // clear the symfony cache with humpty
        array(
            'messages' => array(
                'label' => 'Clearing symfony caches on all web servers... ',
                'success' => 'success',
                'error' => 'Failed to clear the symfony caches. Aborting.'
            ),
            'shell_exec' => "ssh upload@web1 /usr/local/humpty/humpty " .
            " -p {$this->projectName} -a symfony-clear-cache",
            'strpos' => array(
                array(
                    'string' => 'task complete',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // clear the minify cache with humpty
        array(
            'messages' => array(
                'label' => 'Clearing minify caches on all web servers... ',
                'success' => 'success',
                'error' => 'Failed to clear the minify caches. Aborting.'
            ),
            'shell_exec' => "ssh upload@web1 /usr/local/humpty/humpty " .
            " -p {$this->projectName} -a symfony-clear-minify-cache",
            'strpos' => array(
                array(
                    'string' => 'task complete',
                    'test' => '!==',
                    'value' => false
                )
            )
        ),
        // discover the revision to tag a release against
        array(
            'messages' => array(
                'label' => 'Discovering the revision to tag the trunk against... ',
                'success' => 'success. The trunk will be tagged against revision %output%',
                'error' => 'Failed to calculate the revision to tag a release against. Tag the trunk manually.'
            ),
            'usePreviousCommandOutput' => true,
            'preg_match' => array(
                'regex' => '/ NOW TAG THE RELEASE AGAINST REVISION (\d+) /',
                'subject' => '%previousOutput%',
                'matchIndex' => 1
            ),
            'saveOutputToSession' => true
        ),
        // now tag the release
        array(
            'messages' => array(
                'label' => 'Tagging a release... ',
                'success' => 'success. Tagged the trunk against revision %previousOutput%',
                'error' => 'Failed to tag a release. Tag the trunk manually against revision %previousOutput%.'
            ),
            'usePreviousCommandOutput' => true,
            'shell_exec' => "svn cp svn://testbox.beans/projects/{$this->projectName}/trunk@%previousOutput% " .
            "svn://testbox.beans/projects/{$this->projectName}/tags/`date +'REL-%Y-%m-%d_%H-%M'` -m 'Tagging a release'",
            'strpos' => array(
                array(
                    'string' => 'Committed revision ',
                    'test' => '!==',
                    'value' => false
                )
            ),
        ),
        // final messages
        array(
            'finalMessages' => array(
                'success' => 'Successfully deployed to production. Our work here is done.',
                'error' => 'Production deployment failed'
            )
        )
    );

    // disabling part of the process temporarily
    $this->productionActions = array_slice($this->productionActions, 0, 4);
  }

  /**
   * Sets up an array defining actions for the test form
   */
  protected function setupTestFormActions()
  {
    $this->testActions = array(
        array(
            'messages' => array(
                'label' => 'Running tests on staging... ',
                'success' => 'All tests successful',
                'error' => 'Some tests failed: %output%'
            ),
            'shell_exec' => "ssh upload@web1 /var/www/html/staging/{$this->projectName}/symfony test:all",
            'strpos' => array(
                array(
                    'string' => 'All tests successful',
                    'test' => '!==',
                    'value' => false
                )
            )
        )
    );
  }

}
