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
   * A page listing Bene Feature modules and providing control links.
   *
   * @return array
   *   Render array for the config page.
   */
  public function configPage() {
    $modules = \Drupal::service('extension.list.module')->getList();

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
   * A CSRF-protected route that performs the module enable/disable actions.
   *
   * @param string $action
   *   Either "remove" or "install".
   * @param string $module
   *   The name of a bene features module, with the "bene_" prefix removed.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object to go back to the config page.
   */
  public function setModule(string $action, string $module) {
    $modules = \Drupal::service('extension.list.module')->getList();
    switch ($action) {
      case 'remove':
        // Validate the uninstall string before attempting uninstall:
        if (isset($modules['bene_' . $module]) && $modules['bene_' . $module]->status) {
          if (\Drupal::service('module_installer')
            ->uninstall(['bene_' . $module])) {
            \Drupal::messenger()->addMessage(
              t('%module has been disabled. You may re-enable it below.', ['%module' => $modules['bene_' . $module]->info['name']]),
              MessengerInterface::TYPE_STATUS
            );
          }
        }
        break;

      case 'install':
        // Validate the install string before attempting install:
        if (isset($modules['bene_' . $module]) && !$modules['bene_' . $module]->status) {
          if (\Drupal::service('module_installer')
            ->install(['bene_' . $module])) {
            \Drupal::messenger()->addMessage(
              t('%module has been enabled. Return to this page when you are ready to disable it.', ['%module' => $modules['bene_' . $module]->info['name']]),
              MessengerInterface::TYPE_STATUS
            );
          }
        }
        break;

    }
    return $this->redirect('bene.feature.config');
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
    $row['description']['description'] = [
      '#markup' => $module->info['description'],
      '#suffix' => '<br/>',
    ];
    // Generate link for module's configuration page, if it has one.
    if (isset($module->info['configure'])) {
      $route_parameters = isset($module->info['configure_parameters']) ? $module->info['configure_parameters'] : [];
      $row['description']['configure'] = [
        '#type' => 'link',
        '#title' => $this->t('Configure <span class="visually-hidden">the @module module</span>', ['@module' => $module->info['name']]),
        '#url' => Url::fromRoute($module->info['configure'], $route_parameters),
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
    // Generate link for module's help page. Assume that if a hook_help()
    // implementation exists then the module provides an overview page, rather
    // than checking to see if the page exists, which is costly.
    if (\Drupal::service('module_handler')->moduleExists('help') && $module->status && in_array($module->getName(), \Drupal::service('module_handler')->getImplementations('help'))) {
      $row['description']['help'] = [
        '#type' => 'link',
        '#title' => $this->t('Help'),
        '#url' => Url::fromRoute('help.page', ['name' => $module->getName()]),
        '#options' => [
          'attributes' => [
            'class' => ['module-link', 'module-link-help'],
            'title' => $this->t('Help'),
          ],
        ],
      ];
    }
    $validation_reasons = \Drupal::service('module_installer')->validateUninstall([$module->getName()]);
    if (!$validation_reasons) {
      $row['options'] = [
        '#type' => 'link',
        '#title' => $this->t('Disable <span class="visually-hidden">the @module module</span>', ['@module' => $module->info['name']]),
        '#url' => Url::fromRoute(
          'bene.feature.set',
          ['action' => 'remove', 'module' => substr($module->getName(), 5)]
        ),
        '#options' => [
          'attributes' => [
            'class' => ['button', 'button--small'],
          ],
        ],
      ];
    }
    else {
      $row['options'] = [
        '#type' => 'markup',
        '#markup' => t('Cannot Disable'),
      ];
    }
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
      '#url' => Url::fromRoute(
        'bene.feature.set',
        ['action' => 'install', 'module' => substr($module->getName(), 5)]
      ),
      '#options' => [
        'attributes' => [
          'class' => ['button', 'button--primary', 'button--small'],
        ],
      ],
    ];

    return $row;
  }

}
