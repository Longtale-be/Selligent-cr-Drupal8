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
namespace Drupal\sellipal\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\sellipal\Controller\sellipalController;

/**
 * Provides a 'SellipalBlock' block.
 *
 * @Block(
 *  id = "SellipalBlock",
 *  admin_label = @Translation("SellipalBlock"),
 *  deriver = "Drupal\sellipal\Plugin\Derivative\SellipalBlock",
 *  category = @Translation("Selligent"),
 * )
 */

class SellipalBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $id = $this->getDerivativeId();
    $render = new sellipalController;
    $content = $render->renderBlock($id);
    return $content;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'view sellipal');
  }

}