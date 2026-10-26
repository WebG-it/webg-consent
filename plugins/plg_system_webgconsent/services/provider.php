<?php

/**
 * @package     WebG.Consent
 * @copyright   (C) 2026 Marco Galassi / WebG
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use WebG\Plugin\System\Webgconsent\Extension\Webgconsent;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $factory = function (Container $container) {
            // Joomla 4.4 e 5.0/5.1 vogliono il dispatcher nel costruttore, 5.2+ e 6 solo la configurazione.
            $first  = (new \ReflectionMethod(Webgconsent::class, '__construct'))->getParameters()[0] ?? null;
            $config = (array) PluginHelper::getPlugin('system', 'webgconsent');

            if ($first !== null && $first->getName() !== 'config') {
                $plugin = new Webgconsent($container->get(DispatcherInterface::class), $config);
            } else {
                $plugin = new Webgconsent($config);
            }

            $plugin->setApplication(Factory::getApplication());
            $plugin->setDatabase($container->get(DatabaseInterface::class));

            return $plugin;
        };

        $container->set(
            PluginInterface::class,
            method_exists($container, 'lazy') ? $container->lazy(Webgconsent::class, $factory) : $factory
        );
    }
};
