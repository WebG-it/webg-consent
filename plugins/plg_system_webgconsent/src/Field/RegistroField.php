<?php

/**
 * @package     WebG.Consent
 * @copyright   (C) 2026 Marco Galassi / WebG
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace WebG\Plugin\System\Webgconsent\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

/**
 * Scheda del registro: una breve spiegazione e il pulsante di esportazione CSV.
 * Nessuna lettura della tabella a video: la consultazione si fa dal CSV.
 */
class RegistroField extends FormField
{
    protected $type = 'Registro';

    protected function getLabel()
    {
        return '';
    }

    protected function getInput()
    {
        $e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $export = Uri::base() . 'index.php?option=com_ajax&plugin=webgconsent&group=system&format=raw&task=export&'
            . Session::getFormToken() . '=1';

        return '<div class="wgc-registro">'
            . '<p>' . $e(Text::_('PLG_SYSTEM_WEBGCONSENT_REG_INFO')) . '</p>'
            . '<p><a class="btn btn-primary" href="' . $e($export) . '">' . $e(Text::_('PLG_SYSTEM_WEBGCONSENT_REG_EXPORT')) . '</a></p>'
            . '<p class="text-muted"><small>' . $e(Text::_('PLG_SYSTEM_WEBGCONSENT_REG_EXPORT_HINT')) . '</small></p>'
            . '</div>';
    }
}
