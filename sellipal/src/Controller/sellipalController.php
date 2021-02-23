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
namespace Drupal\sellipal\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Language\LanguageInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UnexpectedValueException;

/**
 * The Selligent content renderer template controller.
 */
class sellipalController extends ControllerBase {

	/**
	* Renders the content from the selected Selligent page
	*
	* @return array
	*/
	public function render($page_name) {
		// Avoid caching pages
		\Drupal::service('page_cache_kill_switch')->trigger();
		$result = $this->fetchContent($page_name, 0);
		return $this->renderArray($result);	
	}

	/**
	* Renders the content from the selected Selligent block
	*
	* @return array
	*/
	public function renderBlock($page_name) {
		// Avoid caching pages
		\Drupal::service('page_cache_kill_switch')->trigger();
		$result = $this->fetchContent($page_name, 1);
		return $this->renderArray($result);	
	}

	/**
	* Returns array for theme / Block
	*
	* @return array
	*/
	public function renderArray($result) {
		return [
			'#title' => '',
			'#theme' => 'sellipal',
			'#head' => $result['head'],
			'#head_class' => $result['head_class'],
			'#head_show' => $result['head_show'],
      		'#body' => $result['body'],
      		'#body_class' => $result['body_class'],
      		'#body_show' => $result['body_show'],
		];
	}

	/**
	* Fetch settings from the settings file
	*
	* @return array
	*/
	public function getSettings() {
		$default_config = \Drupal::config('sellipal.settings');

		return [
		  'xml_url' => $default_config->get('sellipal.xml_url'),
		  'get_param' => $default_config->get('sellipal.get_param'),
		  'default_hash' => $default_config->get('sellipal.default_hash'),
		  'default_div_class_head' => $default_config->get('sellipal.default_div_class_head'),
		  'default_div_class_body' => $default_config->get('sellipal.default_div_class_body'),
		  'get_lang_param' => $default_config->get('sellipal.get_lang_param'),
		];
	}

	/**
	* Fetch record from the database based on the page name (page URL)
	*
	* @return array
	*/
	public function getRecord($page_name, $d_type) {
		$this->page_name = $page_name;
		$conn = Database::getConnection();
   		$transaction = $conn->startTransaction();
	    try {
	      	$record = array();
	      	// If we're working with a page use the unique page_url to fetch the data from the database
	      	// Only select Pages
	      	if($d_type == 0) {
	    		$record = $conn->select('sellipal', 'm')
	                  ->condition('page_url', $this->page_name)
	                  ->condition('cr_type', 0)
	                  ->fields('m')
	    	 		  ->execute()
	    	 		  ->fetchAssoc();
	    	 }
	    	 // If we're working with a block use the ID to fetch the data from the database
	    	 // Only select Blocks
	    	 else {
	    		$record = $conn->select('sellipal', 'm')
	                  ->condition('id', $this->page_name)
	                  ->condition('cr_type', 1)
	                  ->fields('m')
	    	 		  ->execute()
	    	 		  ->fetchAssoc();
	    	 }
	    	return $record;
	    }
	    catch (\Exception $e) {
	      // Something went wrong, we're going to roll back the query
	      $conn->rollBack();
	      // Log the exception to watchdog.
	      \Drupal::logger('type')->error($e->getMessage());
	      // Show information to the user that something went wrong
	      drupal_set_message("Something went wrong",'error');
	    }
	}
	
	/**
	* Loop over incomming data and concatinate them
	*
	* @return string
	*/
	public function loopParameters($data,$selli_param){
		$param = "";
		foreach($data as $name => $value) {
			if(strtoupper($name)<>$selli_param) {
				$param = $param . "&" . $name . "=" . urlencode($value);
			}
		}
		return $param;
	}

	
	/**
	* Create an array for output
	*
	* @return string
	*/
	public function createArray($h,$hc,$hs,$b,$bc,$bs){
		return array(
			'head' => $h,
			'head_class' => $hc,
			'head_show' => $hs,
			'body' => $b,
			'body_class' => $bc,
			'body_show' => $bs,
		);
	}

	/**
	* Fetch data from the Selligent server based on the hash
	*
	* @return array
	*/
	public function fetchContent($page_name, $d_type) {
		$settings = $this->getSettings();
		$record = $this->getRecord($page_name, $d_type);

		if((string) $record['enabled'] == "0") {
			// Throw page not found in case the record is not enabled
			throw new NotFoundHttpException();
		}
		else {
			// If the record is enabled we're going to fetch the page from the Selligent platform
	    	$hash_ = (string) $settings['default_hash'];
	    	$language_param = (string) $settings['get_lang_param'];
	    	$selligentCustomer = (string) $settings['xml_url'];
	    	$head_class = (string) $record['head_class'];
	    	$body_class = (string) $record['body_class'];
	    	$renderer_url = "/renderers/xml.ashx";

	    	// Overwrite the default hash if needed
	    	if(!empty($record['hash'])){ $hash_ = (string) $record['hash']; }
	    	// Overwrite the default language parameter if needed
	    	if(!empty($record['lang_param'])){ $language_param = (string) $record['lang_param']; }
	    	// Overwrite the default customer install url if needed 
	    	if(!empty($record['client_url'])){ $selligentCustomer = (string) $record['client_url']; }
	    	// Set the default head div class if needed
			if(empty($head_class)){ $head_class = (string) $settings['default_div_class_head']; }
			// Set the default body div class if needed
	    	if(empty($body_class)){ $body_class = (string) $settings['default_div_class_body']; }

			$parameters = "";
			$selligentID = $hash_;
			$selligentGetParam = (string) $settings['get_param'];
			$head_show = (string) $record['head_show'];
			$body_show = (string) $record['body_show'];
			// Get Language
			$language = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

	    	// Check if we have all the information to do a call towards Selligent
			if(!empty($selligentCustomer) && !empty($selligentGetParam) && !empty($selligentID)) {
				// Loop over GET parameters
				$parameters = $parameters . $this->loopParameters($_GET,$selligentGetParam);
				// Loop over POST parameters
				$parameters = $parameters . $this->loopParameters($_POST,$selligentGetParam);
				// Check if ID is not empty
				(!isset($_GET[$selligentGetParam])) ? $selligentID = $selligentID : $selligentID =  urlencode($_GET[$selligentGetParam]);
				// Remove $temp_test_vars after testing
				$full_url = $selligentCustomer . $renderer_url . "?" . $selligentGetParam . "=". $selligentID . $parameters;
				// Check if LANG PARAM is not empty
				(empty($language_param)) ?$full_url = $full_url : $full_url =  $full_url . "&" . $language_param . "=" . $language;
				// Fetch XML from the Selligent server
				$selligentPage =  @simplexml_load_file($full_url);

				// Start redirect fix
				$location = "";
				foreach ($http_response_header as $hdr) {
				    if (preg_match('/^Location:\s*([^;]+)/', $hdr, $matches)) { $location = $matches[1]; }
				}
				// Check if the location field is present in the header, if so we need to redirect to this location. If not display content of page.
				if (!empty($location)) {
					header("Location: " . $location);
					exit();
				}
				else {
					// Check if we could reach te Selligent platform and/or the XML is empty
					if(empty($selligentPage)) {
						// Faulty return array, drupal will still show address lookup failure errors visible for public
						// We could also throw a page not found here
						return $this->createArray('',(string) $settings['default_div_class_head'],'1','We could not load the page, please check the config settings.',(string) $settings['default_div_class_body'],1);
					}
					else {
						// Found a specific character in the output, removing it from the data.
						$messagent_head = str_replace('&#8203;','',trim((string) $selligentPage->head));
						// No need for body attribute changes at the moment
						// $messagent_bodyattr = str_replace('&#8203;','',trim((string) $selligentPage->bodyattr));
						$messagent_body = str_replace('&#8203;','',trim((string) $selligentPage->body));
						// User now has the power to adjust this
						if((string) $record['dynamic_url'] === "1") {
							/// In case of SMC (replace all the URL to our own URL): 
							// Check the protocol of the domain
							$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
							// Remove the parameters from url
							$url_no_param = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
							// Change URLs with dynamic URLs
							$messagent_body = str_replace(rtrim($selligentCustomer,"/") . "/optiext/optiextension.dll",  $protocol . $_SERVER["HTTP_HOST"] . $url_no_param , $messagent_body);



							// Paths scripts
							$messagent_body = str_replace("../scripts/",rtrim($selligentCustomer,"/") . "/scripts/", $messagent_body);
							$messagent_head = str_replace("../scripts/",rtrim($selligentCustomer,"/") . "/scripts/", $messagent_head);
							// Path Images
							$messagent_body = str_replace("../images/",rtrim($selligentCustomer,"/") . "/images/", $messagent_body);
						}
						// Return information from Selligent
						return $this->createArray($messagent_head,$head_class,$head_show,$messagent_body . $selligentID,$body_class,$body_show);
					}	
				}
			}
			else {
				// Return empty page in case not all data was present.
				return $this->createArray('',(string) $settings['default_div_class_head'],'1','',(string) $settings['default_div_class_body'],'1');
			}
		}
	}
}
