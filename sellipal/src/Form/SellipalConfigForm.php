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
/**
 
 * @file
 
 * Contains \Drupal\sellipal\Form\SellipalConfigForm.
 
 */
 
namespace Drupal\sellipal\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
 
class SellipalConfigForm extends ConfigFormBase {
 
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sellipal_config_form';
  }
 
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
 
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('sellipal.settings');

    /* DEFAULT SETTINGS */
    $form['default_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Default settings'),
      '#open' => TRUE
    );
 
    $form['default_settings']['xml_url'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Install URL from Selligent'),
      '#default_value' => $config->get('sellipal.xml_url'),
      '#description' => t('Example: https://clientname.slgnt.eu or https://clientname.emsecure.net'),
      '#required' => TRUE,
    );

    $form['default_settings']['get_param'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Parameter used to pass the Selligent hash'),
      '#default_value' => $config->get('sellipal.get_param'),
      '#description' => t('Example: ID or SIMID'),
      '#required' => TRUE,
    );

    $form['default_settings']['get_lang_param'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Parameter used to pass the language to Selligent'),
      '#default_value' => $config->get('sellipal.get_lang_param'),
      '#description' => t('Example: LANG or LANGUAGE'),
      '#required' => FALSE,
    );

    $form['default_settings']['default_hash'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Default Selligent hash used for the form'),
      '#default_value' => $config->get('sellipal.default_hash'),
      '#description' => t('This hash will be used to display in case there is an error'),
      '#required' => TRUE,
    );

    $form['default_settings']['default_div_class_head'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Default head class'),
      '#default_value' => $config->get('sellipal.default_div_class_head'),
      '#description' => t('This class-name will be placed on the div that will wrap the head content from the Selligent page'),
      '#required' => TRUE,
    );

    $form['default_settings']['default_div_class_body'] = array(
      '#type' => 'textfield',
      '#size' => 100,
      '#title' => $this->t('Default body class'),
      '#default_value' => $config->get('sellipal.default_div_class_body'),
      '#description' => t('This class-name will be placed on the div that will wrap the body content from the Selligent page'),
      '#required' => TRUE,
    );

    /* PAGE SETTINGS */
    $form['page_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Content rendering pages'),
      '#open' => TRUE
    );

    $form['page_settings']['urls'] = array(
      '#type' => 'link',
      '#title' => $this->t('+ Insert a new hash<br/><br/>'),
      '#attributes' => [
        'class' => [
          'sellipal-add',
        ],
      ],
      '#url' => Url::fromUserInput('/admin/config/sellipal/add'),
    );

    /* TABLE WITH HASHES AND PAGE REFERENCES */
    
    //select records from table
    $conn = \Drupal::database();
    // We're going to use a transaction so that we can do a roll back if needed
    $transaction = $conn->startTransaction();
    try {
       $results = $conn->select('sellipal', 'm')
          ->fields('m', ['id','name','hash','page_url', 'cr_type','enabled'])
          ->execute()->fetchAll();

      $rows=array();

      foreach($results as $data){
        $delete = Url::fromUserInput('/admin/config/sellipal/delete/'.$data->id, [
          'attributes' => [
            'class' => [
              'sellipal-delete',
            ],
            'title' => $this->t('Delete record ' . $data->id),
          ],
        ]);
        $edit   = Url::fromUserInput('/admin/config/sellipal/edit/'.$data->id, [
          'attributes' => [
            'class' => [
              'sellipal-edit',
            ],
            'title' => $this->t('Edit record ' . $data->id),
          ],
        ]);
        //print the data from table, restricted the hash to 50 characters to win screen space
        $rows[] = array(
          'id' =>$data->id,
          'name' => $data->name,
          'type' => ($data->cr_type == 0) ? 'Page':'Block',
          'page_url' => ($data->cr_type == 1) ? 'N/A':'/page/' . strtolower($data->page_url),
          'hash' => substr($data->hash,0,50) . '...',
          'enabled' => ($data->enabled == 1) ? 'Enabled':'Disabled',
          \Drupal::l('E', $edit),
          \Drupal::l('D', $delete),
        );
      }

      //create table header
      $header_table = array(
        'id'=>    t('ID'),
        'name' => t('Name'),
        'type' => ('Type'),
        'page_url' => t('Routing'),
        'hash' => t('Hash'),
        'enabled' => t('Status'),
        'opt' => t('Edit'),
        'opt1' => t('Delete'),
      );

      //display data in site
      $form['page_settings']['table'] = [
        '#type' => 'table',
        '#header' => $header_table,
        '#rows' => $rows,
        '#empty' => t('No users found'),
      ];
    }
    catch (\Exception $e) {
      // Something went wrong, we're going to roll back the query
      $conn->rollBack();
      // Log the exception to watchdog.
      \Drupal::logger('type')->error($e->getMessage());
      // Show information to the user that something went wrong
      drupal_set_message("Something went wrong during the data gathering",'error');
    }
 
    return $form;
  }
 
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
 
    /* Store the default settings */
    $config = $this->config('sellipal.settings');
    $config->set('sellipal.xml_url', $form_state->getValue('xml_url'));
    $config->set('sellipal.get_param', $form_state->getValue('get_param'));
    $config->set('sellipal.get_lang_param', $form_state->getValue('get_lang_param'));
    $config->set('sellipal.default_hash', $form_state->getValue('default_hash'));
    $config->set('sellipal.default_div_class_head', $form_state->getValue('default_div_class_head'));
    $config->set('sellipal.default_div_class_body', $form_state->getValue('default_div_class_body'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }
 
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sellipal.settings',
    ];
 
  }
 
}