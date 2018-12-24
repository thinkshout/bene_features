<?php

namespace Drupal\bene_features\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;

/**
 * Provides feature installation interface for Bene.
 *
 * @internal
 */
class BeneFeaturesController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function configPage() {
    $modules = system_rebuild_module_data();
    $uninstall = \Drupal::request()->query->get('remove') ?: FALSE;
    if ($uninstall) {
      // Validate the uninstall string before attempting uninstall:
      if (isset($modules['bene_' . $uninstall]) && $modules['bene_' . $uninstall]->status) {
        if (\Drupal::service('module_installer')->uninstall(['bene_' . $uninstall])) {
          \Drupal::messenger()->addMessage(
            t('%module has been disabled. You may re-enable it below.', ['%module' => $modules['bene_' . $uninstall]->info['name']]),
            MessengerInterface::TYPE_STATUS
          );
        }
      }
      // Clean up the URL and refresh the module status list in the process.
      return $this->redirect('bene.feature.config');
    }
    $install = \Drupal::request()->query->get('install') ?: FALSE;
    if ($install) {
      // Validate the install string before attempting install:
      if (isset($modules['bene_' . $install]) && !$modules['bene_' . $install]->status) {
        if (\Drupal::service('module_installer')->install(['bene_' . $install])) {
          \Drupal::messenger()->addMessage(
            t('%module has been enabled. Return to this page when you are ready to disable it.', ['%module' => $modules['bene_' . $install]->info['name']]),
            MessengerInterface::TYPE_STATUS
          );
        }
      }
      // Clean up the URL and refresh the module status list in the process.
      return $this->redirect('bene.feature.config');
    }

    $page = [
      'enabled' => [
        '#type' => 'table',
        '#header' => [
          t('Enabled Features'),
          t('About'),
          t('Options'),
        ],
        '#empty' => t('You do not have any Bene Features enabled. Enable any Bene Features you wan to use below.'),
      ],
      'disabled' => [
        '#type' => 'table',
        '#header' => [
          t('Disabled Features'),
          t('About'),
          t('Options'),
        ],
        '#empty' => t('You do not have any disabled Bene Features, but if new Bene Features are added to Bene they will appear here. (You might want to disable "Features Example" as it doesn\'t really do anything)'),
      ],
    ];

    // Find all the Bene Features modules.
    foreach ($modules as $filename => $module) {
      if (empty($module->info['hidden'])) {
        if ($module->info['package'] === 'Bene Features' && $module->getName() != 'bene_features') {
          if ($module->status) {
            $page['enabled'][] = $this->buildEnabledRow($module);
          }
          else {
            $page['disabled'][] = $this->buildDisabledRow($module);
          }
        }
      }
    }

    return $page;
  }

  /**
   * Builds a table row for the Bene Features page for an Enabled module.
   *
   * @param \Drupal\Core\Extension\Extension $module
   *   The module for which to build the form row.
   *
   * @return array
   *   The form row for the given module.
   */
  protected function buildEnabledRow(Extension $module) {
    $row['title'] = [
      '#markup' => $module->info['name'],
    ];
    $row['description'][] = [
      '#markup' => $module->info['description'],
    ];
    // Generate link for module's configuration page, if it has one.
    if (isset($module->info['configure'])) {
      $route_parameters = isset($module->info['configure_parameters']) ? $module->info['configure_parameters'] : [];
      $row['description'][] = [
        '#type' => 'link',
        '#title' => $this->t('Configure <span class="visually-hidden">the @module module</span>', ['@module' => $module->info['name']]),
        '#url' => Url::fromRoute($module->info['configure'], $route_parameters),
        '#prefix' => '<br/>',
        '#options' => [
          'attributes' => [
            'class' => ['module-link', 'module-link-configure'],
          ],
          'query' => [
            'destination' => Url::fromRoute('bene.feature.config')->toString(),
          ],
        ],
      ];
    }
    $row['options'] = [
      '#type' => 'link',
      '#title' => $this->t('Disable <span class="visually-hidden">the @module module</span>', ['@module' => $module->info['name']]),
      '#url' => Url::fromRoute('bene.feature.config', [], ['query' => ['remove' => substr($module->getName(), 5)]]),
      '#options' => [
        'attributes' => [
          'class' => ['button', 'button--small'],
        ],
      ],
    ];

    return $row;
  }

  /**
   * Builds a table row for the Bene Features page for a disabled module.
   *
   * @param \Drupal\Core\Extension\Extension $module
   *   The module for which to build the form row.
   *
   * @return array
   *   The form row for the given module.
   */
  protected function buildDisabledRow(Extension $module) {
    $row['title'] = [
      '#markup' => $module->info['name'],
    ];
    $row['description'] = [
      '#markup' => $module->info['description'],
    ];
    $row['options'] = [
      '#type' => 'link',
      '#title' => $this->t('Enable <span class="visually-hidden">the @module module</span>', ['@module' => $module->info['name']]),
      '#url' => Url::fromRoute('bene.feature.config', [], ['query' => ['install' => substr($module->getName(), 5)]]),
      '#options' => [
        'attributes' => [
          'class' => ['button', 'button--primary', 'button--small'],
        ],
      ],
    ];

    return $row;
  }

}
