<?php

/**
 * @package     WebG.Consent
 * @copyright   (C) 2026 Marco Galassi / WebG
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

return new class () implements InstallerScriptInterface {
    private const MIN_PHP = '8.1.0';

    private const MIN_JOOMLA = '4.4.0';

    private function loadLanguage(): void
    {
        $lang = Factory::getApplication()->getLanguage();
        $lang->load('plg_system_webgconsent', JPATH_ADMINISTRATOR);
        $lang->load('plg_system_webgconsent', JPATH_PLUGINS . '/system/webgconsent');
    }

    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Il registro del consenso e' una prova: alla disinstallazione la tabella
     * resta, salvo che il parametro "delete_on_uninstall" sia stato messo su Si.
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        try {
            $params = $db->setQuery(
                $db->getQuery(true)
                    ->select($db->quoteName('params'))
                    ->from($db->quoteName('#__extensions'))
                    ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                    ->where($db->quoteName('element') . ' = ' . $db->quote('webgconsent'))
                    ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            )->loadResult();

            $reg = new Registry((string) $params);

            if ((int) $reg->get('delete_on_uninstall', 0) === 1) {
                $db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName('#__webgconsent_log'))->execute();
            }
        } catch (\Throwable $e) {
            // mai bloccare la disinstallazione
        }

        return true;
    }

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        if (version_compare(PHP_VERSION, self::MIN_PHP, '<')) {
            $this->loadLanguage();
            Factory::getApplication()->enqueueMessage(Text::sprintf('PLG_SYSTEM_WEBGCONSENT_PREFLIGHT_PHP', self::MIN_PHP), 'error');

            return false;
        }

        if (version_compare(JVERSION, self::MIN_JOOMLA, '<')) {
            $this->loadLanguage();
            Factory::getApplication()->enqueueMessage(Text::sprintf('PLG_SYSTEM_WEBGCONSENT_PREFLIGHT_JOOMLA', self::MIN_JOOMLA), 'error');

            return false;
        }

        return true;
    }

    /**
     * Alla prima installazione il plugin resta DISABILITATO: lo si abilita a mano
     * dopo aver controllato testo e parametri.
     */
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'install') {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            try {
                $db->setQuery(
                    $db->getQuery(true)
                        ->update($db->quoteName('#__extensions'))
                        ->set($db->quoteName('enabled') . ' = 0')
                        ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                        ->where($db->quoteName('element') . ' = ' . $db->quote('webgconsent'))
                        ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                )->execute();
            } catch (\Throwable $e) {
                // niente
            }

            // il file lingua del plugin non e' ancora caricato durante l'installazione: lo carico a mano
            $this->loadLanguage();
            Factory::getApplication()->enqueueMessage(Text::_('PLG_SYSTEM_WEBGCONSENT_POSTINSTALL'), 'notice');
        }

        return true;
    }
};
