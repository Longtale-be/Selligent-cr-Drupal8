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
namespace Drupal\sellipal\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

class SellipalBlock extends DeriverBase {

/**
 * {@inheritdoc}
 */
	public function getDerivativeDefinitions($base_plugin_definition) {
		$block_urls = array();
		//select records from table
	    $conn = \Drupal::database();
	    // We're going to use a transaction so that we can do a roll back if needed
	    $transaction = $conn->startTransaction();
	    try {
	       $results = $conn->select('sellipal', 'm')
	          ->fields('m', ['id','name','hash','page_url', 'dynamic_url'])
	          ->condition('cr_type', '1')
	          ->execute()->fetchAll();

	      foreach($results as $data){
	      	 $block_urls[] = array(
	          'id' =>$data->id,
	          'name' => $data->name,
	          'page_url' => $data->page_url,
	        );
	      }
	    }
	    catch (\Exception $e) {
	      // Something went wrong, we're going to roll back the query
	      $conn->rollBack();
	      // Log the exception to watchdog.
	      \Drupal::logger('type')->error($e->getMessage());
	      // Show information to the user that something went wrong
	      drupal_set_message("Something went wrong during the data gathering",'error');
	    }

		foreach ($block_urls as $block_url) {
			$this->derivatives[$block_url['id']] = $base_plugin_definition;
			$this->derivatives[$block_url['id']]['admin_label'] = 'Sellipal Block: ' . $block_url['name'];
		}

		return $this->derivatives;
	}
}