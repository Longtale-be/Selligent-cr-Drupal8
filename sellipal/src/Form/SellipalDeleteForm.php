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
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;

/**
 * Class SellipalDeleteForm.
 *
 * @package Drupal\sellipal\Form
 */

class SellipalDeleteForm extends ConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_form';
  }

  public $hid;

  public function getQuestion() { 
    return t('Do you want to delete %hid?', array('%hid' => $this->hid));
  }

  public function getCancelUrl() {
    return new Url('sellipal.config');
  }

  public function getDescription() {
    return t('Are you sure you want to delete this record?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete it!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $hid = NULL) {
    $this->id = $hid;
    return parent::buildForm($form, $form_state);
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $conn = \Drupal::database();
     // We're going to use a transaction so that we can do a roll back if needed
    $transaction = $conn->startTransaction();
    try {
      $conn->delete('sellipal')
        ->condition('id',$this->id)
        ->execute();
      drupal_set_message("succesfully deleted");
      $form_state->setRedirect('sellipal.config');
    }
    catch (\Exception $e) {
      // Something went wrong, we're going to roll back the query
      $conn->rollBack();
      // Log the exception to watchdog.
      \Drupal::logger('type')->error($e->getMessage());
      // Show information to the user that something went wrong
      drupal_set_message("Something went wrong during the delete.",'error');
    }
  }
}