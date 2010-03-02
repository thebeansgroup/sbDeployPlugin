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
  }
}

