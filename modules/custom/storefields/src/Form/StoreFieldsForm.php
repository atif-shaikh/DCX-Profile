<?php

/**
 * @file
 * Contains \Drupal\storefields\Form\StoreFieldsForm.
 */

namespace Drupal\storefields\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Field;
use Drupal\Core\Language\LanguageManager;


class StoreFieldsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'csvimport_import_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['dsc'] = [
      '#type' => 'textarea',
      '#title' => t('About Us: '),
	   '#weight' => 2,
    ];
    $form['policy'] = [
      '#title' => t('Policy'),
      '#type' => 'textarea',  
	  '#weight' => 3,	  
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Check to make sure that the file was uploaded to the server properly
    $userInputValues = $form_state->getUserInput();
    
    $uri =  db_select('file_managed','f')
    ->condition('f.fid', $userInputValues['import']['fids'], '=')
    ->fields('f',array('uri'))
    ->execute()
    ->fetchField();
    
    if (!empty($uri)) {
      if (file_exists(\Drupal::service("file_system")->realpath($uri))) {
        // Open the csv
        $handle = fopen(\Drupal::service("file_system")->realpath($uri), "r");
        // Go through each row in the csv and run a function on it. In this case we are parsing by '|' (pipe) characters.
        // If you want commas are any other character, replace the pipe with it.

        while (($data = fgetcsv($handle, 0, ',', '"')) !== FALSE) {
          $operations[] = [
            'csvimport_import_batch_processing',[$data]
          ];
        }
         
        // Once everything is gathered and ready to be processed... well... process it!
        $batch = [
          'title' => t('Importing CSV...'),
          'operations' => $operations,
          // Runs all of the queued processes from the while loop above.
        'finished' => $this->csvimport_import_finished(),
          // Function to run when the import is successful
        'error_message' => t('The installation has encountered an error.'),
          'progress_message' => t('Imported @current of @total products.'),
        ];
        batch_set($batch);
        fclose($handle);
      }
      else {
         drupal_set_message(t('Not able to find file path.'), 'error');
      }
    }
    else {
      drupal_set_message(t('There was an error uploading your file. Please contact a System administator.'), 'error');
    }
  }

public function csvimport_import_finished() {
  drupal_set_message(t('Import Completed Successfully'));
}

}
