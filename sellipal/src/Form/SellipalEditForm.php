<?php
/*
 * ============LICENSE_START=============================================================================================================
 * Copyright (c) 2019 Longtale.
 * ===================================================================
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at
 *
 *        http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS
 * OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 * ============LICENSE_END===============================================================================================================
 * 
 */
namespace Drupal\sellipal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SellipalEditForm.
 *
 * @package Drupal\sellipal\Form
 */
class SellipalEditForm extends FormBase {
/**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sellipal_form';
  }

  public $hid;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $hid = NULL) {
    $this->id = $hid;

    $conn = Database::getConnection();
    $record = array();

    $query = $conn->select('sellipal', 'm')
                  ->condition('id', $this->id)
                  ->fields('m');
    $record = $query->execute()->fetchAssoc();

    $form['record_id'] = array(
      '#type' => 'hidden',
      '#value' => $this->id,
    );

    $form['page_name'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Name'),
      '#default_value' => (isset($record['name']) && $this->id) ? $record['name']:'',
      '#description' => t('<strong>Note:</strong> The name will be used to identify a block'), 
      '#required' => TRUE,
      '#maxlength' => 150,
    );

    $form['cr_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Display type'),
      '#default_value' => (isset($record['cr_type']) && $this->id) ? $record['cr_type']:'0',
      '#options' => array(
        '0' => $this->t('Page'),
        '1' => $this->t('Block'),
      ),
     '#description' => t('When selecting type <strong>"Block"</strong> don\'t forget to flush your cache.'), 
    );

    $form['client_url'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Install URL from Selligent'),
      '#description' => t('Example: https://clientname.slgnt.eu or https://clientname.emsecure.net<br/><strong>Note:</strong> This will overwrite the default setting.'), 
      '#default_value' => (isset($record['client_url']) && $this->id) ? $record['client_url']:'',
      '#required' => FALSE,
      '#maxlength' => 250,
    );

    $form['page_hash'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Selligent hash'),
      '#default_value' => (isset($record['hash']) && $this->id) ? $record['hash']:'',
      '#description' => $this->t('This hash will be used to fetch the Selligent page'),
      '#required' => TRUE,
      '#maxlength' => 250,
    );

    $form['lang_param'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Language parameter'),
      '#description' => t('Parameter used to pass the language to the Selligent platform'), 
      '#default_value' => (isset($record['lang_param']) && $this->id) ? $record['lang_param']:'',
      '#required' => FALSE,
      '#maxlength' => 150,
    );

     /* START: Added for Roles */
    $form["page_role_select"] = array(
      "#type" => "checkboxes", 
      "#title" => t("Select role access"), 
      '#default_value' => (isset($record['page_role_select']) && $this->id) ? explode("||", $record['page_role_select']):'',
      "#options" => user_role_names(),
      "#description" => t("The selected roles will have access to this Selligent page, when no option is selected all roles will have access"),
    );
    /* END: Added for Roles */

    $form['page_url'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Routing name for the page'),
      '#default_value' => (isset($record['page_url']) && $this->id) ? $record['page_url']:'',
      '#maxlength' => 150,
      '#description' => t('This name will be used for dynamic routing in Drupal. No need to add / before te name.<br/><strong>Note:</strong> URL will look like: /page/your_routing_name'),
      '#states' => [
        'visible' => [
          ':input[name="cr_type"]' => ['value' => '0'],
        ],
        'required' => [
          ':input[name="cr_type"]' => ['value' => '0'],
        ],
      ],
    );

    $form['head_class'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Head class-name'),
      '#default_value' => (isset($record['head_class']) && $this->id) ? $record['head_class']:'',
      '#description' => t('This class-name will be placed on the div that will wrap the head content from the Selligent page'),
      '#required' => FALSE,
      '#maxlength' => 250,
    );

    $form['body_class'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Body class-name'),
      '#default_value' => (isset($record['body_class']) && $this->id) ? $record['body_class']:'',
      '#description' => t('This class-name will be placed on the div that will wrap the body content from the Selligent page'),
      '#required' => FALSE,
      '#maxlength' => 250,
    );

    $form['head_show'] = array(
      '#type' => 'checkbox',
      '#size' => 100,
      '#title' => $this->t('Render head content'),
      '#default_value' => (isset($record['head_show']) && $this->id) ? $record['head_show']:'1',
      '#description' => t('Activating this option will make sure the head tag content from the Selligent page is  rendered'),
    );

    $form['body_show'] = array(
      '#type' => 'checkbox',
      '#size' => 100,
      '#title' => $this->t('Render body content'),
      '#default_value' => (isset($record['body_show']) && $this->id) ? $record['body_show']:'1',
      '#description' => t('Activating this option will make sure the body tag content from the Selligent page is  rendered'),
    );

    $form['dynamic_url'] = array(
      '#type' => 'checkbox',
      '#size' => 100,
      '#title' => $this->t('Dynamic URLs'),
      '#default_value' => (isset($record['dynamic_url']) && $this->id) ? $record['dynamic_url']:'0',
      '#description' => t('Activating this option will make sure all Selligent URLs in the body will be replaced with the current URL')
    );

    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#size' => 100,
      '#title' => $this->t('Enable'),
      '#default_value' => (isset($record['enabled']) && $this->id) ? $record['enabled']:'0',
      '#description' => t('Activating this option will make the page/block visible, disabled it will show a "Page not found" error'),
    );

    $form['submit'] = [
        '#type' => 'submit',
        '#value' => 'save',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $field=$form_state->getValues();
    $name=$field['page_name'];
    $hash=$field['page_hash'];
    $url=$field['page_url'];
    $dyn_url=$field['dynamic_url'];
    $head_class=$field['head_class'];
    $body_class=$field['body_class'];
    $head_show=$field['head_show'];
    $body_show=$field['body_show'];
    $cr_type=$field['cr_type'];
    $lang_param=$field['lang_param'];
    $record_id=$field['record_id'];
    $client_url=$field['client_url'];
    $enabled=$field['enabled'];

    /* START: Added for Roles */
    $results = implode('||', array_filter($field['page_role_select']));
    $roles=$results;
    /* END: Added for Roles */

    if(empty($url)) {
      $url = $record_id;
    }

    $fields  = array(
        'name'   => $name,
        'hash' =>  $hash,
        'page_url' =>  $url,
        'dynamic_url' => $dyn_url,
        'head_class' => $head_class,
        'body_class' => $body_class,
        'head_show' => $head_show,
        'body_show' => $body_show,
        'cr_type' => $cr_type,
        'lang_param' => $lang_param,
        'client_url' => $client_url,
        'enabled' => $enabled,
        /* START: Added for Roles */
        'page_role_select' => $roles,
        /* END: Added for Roles */
    );

    $conn = \Drupal::database();
    // We're going to use a transaction so that we can do a roll back if needed
    $transaction = $conn->startTransaction();
    try {
      $conn->update('sellipal')
          ->fields($fields)
          ->condition('id', $this->id)
          ->execute();
      drupal_set_message($name . " was succesfully updated.");
      $form_state->setRedirect('sellipal.config');
    }
    catch (\Exception $e) {
      // Something went wrong, we're going to roll back the query
      $conn->rollBack();
      // Log the exception to watchdog.
      \Drupal::logger('type')->error($e->getMessage());
      // Show information to the user that something went wrong
      drupal_set_message("Something went wrong during the update. Make sure you check the length of the fields and that the page URL is unique.",'error');
    }

  }
}