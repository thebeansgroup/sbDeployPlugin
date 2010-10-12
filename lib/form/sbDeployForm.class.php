<?php

/**
 * Base form used by all actions
 *
 * @author al
 */
class sbDeployForm extends sfForm
{

  public function configure()
  {
    $type = $this->getOption('type');

    if (empty($type))
    {
      throw new RuntimeException("Error, mandatory 'type' option not set");
    }

    $this->disableCSRFProtection();

    $this->setWidgets(
            array(
                'confirmation' => new sfWidgetFormInput()
            )
    );

    $this->widgetSchema['confirmation']->setAttribute('value', '');
    $this->widgetSchema['confirmation']->setAttribute('autocomplete', 'off');

    $this->widgetSchema->setNameFormat("{$type}[%s]");

    $this->setValidators(
            array(
                'confirmation' => new sfValidatorRegex(
                        array(
                            'required' => true,
                            'pattern' => "/^$type$/"
                        ),
                        array(
                            'invalid' => "You must enter '$type' to run the tests"
                        )
                )
            )
    );

    if ($type == 'staging')
    {
      $this->widgetSchema['repo_uri'] = new sfWidgetFormInput(array(), array('size' => '35'));
      $this->widgetSchema['repo_uri']->setAttribute('value', '');
      $this->widgetSchema['repo_uri']->setAttribute('autocomplete', 'off');
      $this->validatorSchema['repo_uri'] = new sfValidatorRegex(
                      array(
                          'required' => false,
                          'pattern' => "/(((branches){1}\/[A-Za-z]+\/[0-9]{4}-[0-9]{2}-[0-9]{2}_[A-Za-z0-9-_]+)|((tags){1}\/REL-[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}$))/"
                      ),
                      array(
                          'invalid' => "Please make sure you enter a valid repo uri"
                      )
      );
    }
  }

}

