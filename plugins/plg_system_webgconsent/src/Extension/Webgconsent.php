<?php

/**
 * @package     WebG.Consent
 * @copyright   (C) 2026 Marco Galassi / WebG
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace WebG\Plugin\System\Webgconsent\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use WebG\Plugin\System\Webgconsent\Helper\Assets;

/**
 * Age gate con registro del consenso.
 *
 * - onAfterRender: inserisce subito dopo <body> un blocco identico per tutti i visitatori
 *   (CSS + JS in linea), quindi compatibile con qualunque cache di pagina. Il modale nasce
 *   solo lato browser, il contenuto della pagina resta nel DOM.
 * - onAjaxWebgconsent: endpoint di registrazione (task=token, task=log) e di esportazione (task=export).
 */
final class Webgconsent extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    protected $autoloadLanguage = true;

    private const TABLE = '#__webgconsent_log';

    private const MIN_TOKEN_AGE = 1;

    private const MAX_TOKEN_AGE = 1800;

    private const GLOBAL_LIMIT_10MIN = 2000;

    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRender'   => 'onAfterRender',
            'onAjaxWebgconsent' => 'onAjaxWebgconsent',
        ];
    }

    private function app()
    {
        return $this->getApplication() ?: Factory::getApplication();
    }

    /* ------------------------------------------------------------------ */
    /*  Parte pubblica: iniezione del modale                                */
    /* ------------------------------------------------------------------ */

    public function onAfterRender($event = null): void
    {
        $app = $this->app();

        if (!$app->isClient('site')) {
            return;
        }

        $doc = $app->getDocument();

        if (!$doc || $doc->getType() !== 'html') {
            return;
        }

        $input = $app->getInput();

        if ($input->getCmd('option') === 'com_ajax' || \in_array($input->getCmd('tmpl'), ['component', 'raw'], true)) {
            return;
        }

        // anteprima del customizer e del builder di YOOtheme: niente modale
        if ($input->get('customizer') !== null || $input->get('builder') !== null || $input->get('yootheme') !== null
            || $input->getCmd('p') === 'customizer') {
            return;
        }

        $body    = $app->getBody();
        $headEnd = stripos($body, '</head>');

        if ($headEnd === false || !preg_match('/<body\b[^>]*>/i', $body, $m, PREG_OFFSET_CAPTURE, $headEnd)) {
            return;
        }

        $pos = $m[0][1] + \strlen($m[0][0]);
        $cfg = json_encode(
            $this->clientConfig(),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
        );

        if ($cfg === false) {
            return;
        }

        $block = '<style id="wgc-css">:root{' . $this->cssVars() . '}' . Assets::css() . '</style>'
            . '<script id="wgc-js">' . $this->compact(str_replace('__CFG__', $cfg, Assets::js())) . '</script>';

        $app->setBody(substr($body, 0, $pos) . $block . substr($body, $pos));
    }

    private function compact(string $js): string
    {
        // toglie solo le righe di commento e l'indentazione: nessuna minificazione, nessuna trasformazione del codice
        $js = preg_replace('/^[ \t]*\/\/.*$/m', '', $js);

        return trim(preg_replace('/\n{2,}/', "\n", preg_replace('/^[ \t]+/m', '', $js)));
    }

    /**
     * Righe di testo per lingua, dalla scheda del plugin (sottoform).
     */
    private function textRows(): array
    {
        $raw = $this->params->get('texts', []);

        // il valore predefinito del manifest viene salvato come stringa JSON, dopo il primo salvataggio e' un oggetto
        if (\is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        $arr = json_decode((string) json_encode($raw), true);

        return \is_array($arr) ? array_values(array_filter($arr, 'is_array')) : [];
    }

    /**
     * Versione del testo: parte manuale + impronta automatica dei testi inseriti nel backend.
     * Se si modifica un testo dal backend la versione cambia da sola e il consenso viene richiesto di nuovo.
     */
    private function version(): string
    {
        $v = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $this->params->get('text_version', '1'));
        $v = $v === '' ? '1' : substr($v, 0, 24);
        $norm = static function ($x) use (&$norm) {
            if (\is_array($x)) {
                return array_map($norm, $x);
            }

            return trim(str_replace(["\r\n", "\r"], "\n", (string) $x));
        };
        $h = substr(md5((string) json_encode($norm([$this->textRows(), (string) $this->params->get('privacy_url', '')]))), 0, 6);

        return $v . '-' . $h;
    }

    /**
     * Testi nella lingua attiva della pagina: riga con lo stesso codice (it-IT), poi stessa lingua
     * primaria (it), poi la riga "Tutte" (*), poi le stringhe dei file lingua del plugin.
     * Ogni campo vuoto ricade sulla stringa predefinita del file lingua.
     */
    private function texts(): array
    {
        $app  = $this->app();
        $tag  = (string) $app->getLanguage()->getTag();
        $prim = strtolower(substr($tag, 0, 2));
        $pick = null;
        $rows = $this->textRows();

        foreach ([0, 1, 2] as $pass) {
            foreach ($rows as $r) {
                $l = (string) ($r['lang'] ?? '*');

                if (($pass === 0 && strcasecmp($l, $tag) === 0)
                    || ($pass === 1 && $l !== '*' && strtolower(substr($l, 0, 2)) === $prim)
                    || ($pass === 2 && $l === '*')) {
                    $pick = $r;
                    break 2;
                }
            }
        }

        $def = static function (string $key): string {
            return Text::_($key);
        };

        $get = static function (string $k, string $d) use ($pick): string {
            $v = trim((string) ($pick[$k] ?? ''));

            return $v !== '' ? $v : $d;
        };

        $privacyUrl = trim((string) ($pick['privacy_url'] ?? ''));

        return [
            'title' => $get('title', $def('PLG_SYSTEM_WEBGCONSENT_DEF_TITLE')),
            'body'  => $get('body', $def('PLG_SYSTEM_WEBGCONSENT_DEF_BODY')),
            'acc'   => $get('btn_accept', $def('PLG_SYSTEM_WEBGCONSENT_DEF_ACCEPT')),
            'rej'   => $get('btn_exit', $def('PLG_SYSTEM_WEBGCONSENT_DEF_EXIT')),
            'note'  => $get('footnote', $def('PLG_SYSTEM_WEBGCONSENT_DEF_NOTE')),
            'pl'    => $get('privacy_label', $def('PLG_SYSTEM_WEBGCONSENT_DEF_PRIVACY')),
            'pu'    => $privacyUrl !== '' ? $privacyUrl : (string) $this->params->get('privacy_url', ''),
        ];
    }

    private function hex(string $name, string $default): string
    {
        $v = trim((string) $this->params->get($name, $default));

        return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $default;
    }

    private function cssVars(): string
    {
        $ov  = ltrim($this->hex('c_overlay', '#0d0d12'), '#');
        $op  = max(50, min(100, (int) $this->params->get('overlay_opacity', 96))) / 100;
        $rgb = hexdec(substr($ov, 0, 2)) . ',' . hexdec(substr($ov, 2, 2)) . ',' . hexdec(substr($ov, 4, 2));

        return '--wgc-ov:rgba(' . $rgb . ',' . $op . ');'
            . '--wgc-pn:' . $this->hex('c_panel', '#1c1c24') . ';'
            . '--wgc-tx:' . $this->hex('c_text', '#f2f2f5') . ';'
            . '--wgc-ab:' . $this->hex('c_accept_bg', '#2ec664') . ';'
            . '--wgc-at:' . $this->hex('c_accept_text', '#06210f') . ';'
            . '--wgc-eb:' . $this->hex('c_exit_border', '#c9c9d1') . ';'
            . '--wgc-et:' . $this->hex('c_exit_text', '#f2f2f5') . ';';
    }

    private function cookieDomain(): string
    {
        $d = strtolower(trim((string) $this->params->get('cookie_domain', '')));

        return preg_match('/^\.?[a-z0-9-]+(\.[a-z0-9-]+)+$/', $d) ? $d : '';
    }

    private function safeUrl(string $u, bool $allowRelative): string
    {
        $u = trim($u);

        if ($u === '' || preg_match('/[\x00-\x1f\s]/', $u)) {
            return '';
        }

        if (preg_match('#^https?://[^/\s]+#i', $u)) {
            return $u;
        }

        if ($allowRelative && $u[0] === '/' && !str_starts_with($u, '//')) {
            return $u;
        }

        return '';
    }

    private function clientConfig(): array
    {
        $p  = $this->params;
        $tx = $this->texts();

        $body = [];

        foreach (preg_split('/\R/u', $tx['body']) as $line) {
            $line = trim($line);

            if ($line !== '') {
                $body[] = mb_substr($line, 0, 600);
            }
        }

        $ex = [];

        foreach (preg_split('/\R/u', (string) $p->get('exclude_paths', '')) as $line) {
            $line = trim($line);

            if ($line !== '' && $line[0] === '/' && !str_starts_with($line, '//')) {
                $ex[] = $line;
            }
        }

        $exit = $this->safeUrl((string) $p->get('exit_url', ''), false) ?: 'https://www.google.com';

        return [
            'v'    => $this->version(),
            'days' => max(1, min(730, (int) $p->get('cookie_days', 365))),
            'dom'  => $this->cookieDomain(),
            'ex'   => $ex,
            'exit' => $exit,
            'log'  => (bool) $p->get('log_enabled', 1),
            'ep'   => Uri::root(true) . '/index.php?option=com_ajax&plugin=webgconsent&group=system&format=raw',
            't'    => [
                'title' => mb_substr($tx['title'], 0, 200),
                'body'  => $body,
                'acc'   => mb_substr($tx['acc'], 0, 80),
                'rej'   => mb_substr($tx['rej'], 0, 80),
                'note'  => mb_substr($tx['note'], 0, 600),
                'pu'    => $this->safeUrl($tx['pu'], true),
                'pl'    => mb_substr($tx['pl'], 0, 80),
            ],
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Endpoint AJAX                                                       */
    /* ------------------------------------------------------------------ */

    public function onAjaxWebgconsent($event = null): void
    {
        $app  = $this->app();
        $task = $app->getInput()->getCmd('task', '');

        if ($task === 'export') {
            $this->export($event);

            return;
        }

        if (!$app->isClient('site')) {
            $this->reply($event, ['ok' => 0, 'err' => 'not_found'], 404);

            return;
        }

        $method = strtoupper($app->getInput()->getMethod());

        if ($task === 'token' && $method === 'GET') {
            $this->reply($event, ['t' => $this->makeToken()]);

            return;
        }

        if ($task === 'log' && $method === 'POST') {
            $this->log($event);

            return;
        }

        $this->reply($event, ['ok' => 0, 'err' => 'not_found'], 404);
    }

    private function reply($event, array $data, int $status = 200): void
    {
        $app = $this->app();

        if ($status !== 200) {
            $app->setHeader('status', (string) $status, true);
        }

        $doc = $app->getDocument();

        if ($doc && method_exists($doc, 'setMimeEncoding')) {
            $doc->setMimeEncoding('application/json');
        }

        $app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);

        if ($event !== null && method_exists($event, 'addResult')) {
            $event->addResult(json_encode($data));
        } else {
            echo json_encode($data);
        }
    }

    /* ---- token firmato senza stato ------------------------------------ */

    private function key(string $label): string
    {
        $secret = (string) $this->app()->get('secret');

        return hash_hmac('sha256', 'webgconsent|' . $label . '|' . (string) $this->params->get('salt', ''), $secret);
    }

    private function makeToken(): string
    {
        $ts  = time();
        $non = bin2hex(random_bytes(8));
        $sig = hash_hmac('sha256', $ts . '.' . $non, $this->key('token'));

        return $ts . '.' . $non . '.' . $sig;
    }

    private function checkToken(string $t): bool
    {
        if (!preg_match('/^(\d{9,11})\.([0-9a-f]{16})\.([0-9a-f]{64})$/', $t, $m)) {
            return false;
        }

        $age = time() - (int) $m[1];

        if ($age < self::MIN_TOKEN_AGE || $age > self::MAX_TOKEN_AGE) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $m[1] . '.' . $m[2], $this->key('token')), $m[3]);
    }

    /* ---- IP ------------------------------------------------------------ */

    private function clientIp(): string
    {
        $server = $this->app()->getInput()->server;

        if ((int) $this->params->get('trust_cf', 1) === 1) {
            $cf = (string) $server->get('HTTP_CF_CONNECTING_IP', '', 'raw');

            if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
                return $cf;
            }
        }

        $ip = (string) $server->get('REMOTE_ADDR', '', 'raw');

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    private function ipHash(string $ip): string
    {
        $packed = @inet_pton($ip);

        return hash_hmac('sha256', $packed === false ? $ip : bin2hex($packed), $this->key('ip'));
    }

    /* ---- registrazione ------------------------------------------------- */

    private function log($event): void
    {
        if (!(int) $this->params->get('log_enabled', 1)) {
            $this->reply($event, ['ok' => 1, 'logged' => 0]);

            return;
        }

        $in  = $this->app()->getInput()->post;
        $vid = (string) $in->get('vid', '', 'raw');
        $ver = (string) $in->get('ver', '', 'raw');
        $out = (string) $in->get('out', '', 'raw');
        $url = (string) $in->get('url', '', 'raw');
        $tok = (string) $in->get('t', '', 'raw');

        if (!preg_match('/^[0-9a-f]{32}$/', $vid) || !preg_match('/^[A-Za-z0-9_-]{1,32}$/', $ver)
            || !\in_array($out, ['accepted', 'rejected'], true)) {
            $this->reply($event, ['ok' => 0, 'err' => 'bad_request'], 400);

            return;
        }

        if (!$this->checkToken($tok)) {
            $this->reply($event, ['ok' => 0, 'err' => 'bad_token'], 403);

            return;
        }

        // solo il percorso, mai la query string (potrebbe contenere dati personali)
        $path = (string) parse_url($url, PHP_URL_PATH);
        $path = preg_replace('/[\x00-\x1f\x7f]/', '', $path);
        $path = mb_substr($path !== '' ? $path : '/', 0, 255);

        $ua = (string) $this->app()->getInput()->server->get('HTTP_USER_AGENT', '', 'raw');
        $ua = mb_substr(preg_replace('/[\x00-\x1f\x7f]/', '', $ua), 0, 160);

        $ipHash = $this->ipHash($this->clientIp());
        $db     = $this->getDatabase();

        try {
            $limit = max(3, (int) $this->params->get('rate_limit', 120));
            $since = gmdate('Y-m-d H:i:s', time() - 600);

            $q = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName(self::TABLE))
                ->where($db->quoteName('ip_hash') . ' = :ip')
                ->where($db->quoteName('created') . ' >= :since')
                ->bind(':ip', $ipHash)
                ->bind(':since', $since);

            if ((int) $db->setQuery($q)->loadResult() >= $limit) {
                $this->reply($event, ['ok' => 0, 'err' => 'rate_limited'], 429);

                return;
            }

            $q = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName(self::TABLE))
                ->where($db->quoteName('created') . ' >= :since')
                ->bind(':since', $since);

            if ((int) $db->setQuery($q)->loadResult() >= self::GLOBAL_LIMIT_10MIN) {
                $this->reply($event, ['ok' => 0, 'err' => 'rate_limited'], 429);

                return;
            }

            // stesso visitatore, stessa versione, stesso esito negli ultimi 30 secondi: un solo record
            $recent = gmdate('Y-m-d H:i:s', time() - 30);
            $q      = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName(self::TABLE))
                ->where($db->quoteName('visitor_id') . ' = :v')
                ->where($db->quoteName('text_version') . ' = :ver')
                ->where($db->quoteName('outcome') . ' = :o')
                ->where($db->quoteName('created') . ' >= :since')
                ->bind(':v', $vid)
                ->bind(':ver', $ver)
                ->bind(':o', $out)
                ->bind(':since', $recent);

            if ((int) $db->setQuery($q)->loadResult() > 0) {
                $this->reply($event, ['ok' => 1, 'logged' => 0]);

                return;
            }

            $row = (object) [
                'created'      => gmdate('Y-m-d H:i:s'),
                'visitor_id'   => $vid,
                'text_version' => $ver,
                'outcome'      => $out,
                'ip_hash'      => $ipHash,
                'user_agent'   => $ua,
                'entry_url'    => $path,
            ];

            $db->insertObject(self::TABLE, $row);

            if (random_int(1, 100) === 1) {
                $this->purge();
            }
        } catch (\Throwable $e) {
            $this->reply($event, ['ok' => 0, 'err' => 'server'], 500);

            return;
        }

        $this->reply($event, ['ok' => 1, 'logged' => 1]);
    }

    private function purge(): void
    {
        $days = max(30, (int) $this->params->get('retention_days', 730));
        $cut  = gmdate('Y-m-d H:i:s', time() - $days * 86400);
        $db   = $this->getDatabase();

        $q = $db->getQuery(true)
            ->delete($db->quoteName(self::TABLE))
            ->where($db->quoteName('created') . ' < :cut')
            ->bind(':cut', $cut);

        $db->setQuery($q)->execute();
    }

    /* ---- esportazione CSV (solo backend, super utente, token di sessione) -- */

    private function export($event): void
    {
        $app  = $this->app();
        $user = $app->getIdentity();

        if (!$app->isClient('administrator') || !$user || !$user->authorise('core.admin') || !Session::checkToken('get')) {
            $this->reply($event, ['ok' => 0, 'err' => 'forbidden'], 403);

            return;
        }

        $in   = $app->getInput();
        $from = (string) $in->get('from', '', 'raw');
        $to   = (string) $in->get('to', '', 'raw');
        $db   = $this->getDatabase();

        $q = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName(self::TABLE))
            ->order($db->quoteName('id') . ' ASC');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $f = $from . ' 00:00:00';
            $q->where($db->quoteName('created') . ' >= :f')->bind(':f', $f);
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $t = $to . ' 23:59:59';
            $q->where($db->quoteName('created') . ' <= :t')->bind(':t', $t);
        }

        $app->clearHeaders();
        $app->setHeader('Content-Type', 'text/csv; charset=utf-8', true);
        $app->setHeader('Content-Disposition', 'attachment; filename="webgconsent-registro-' . gmdate('Ymd-His') . '.csv"', true);
        $app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $app->setHeader('X-Robots-Tag', 'noindex, nofollow', true);
        $app->sendHeaders();

        $esc = static function ($v): string {
            $v = (string) $v;

            return ($v !== '' && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v;
        };

        $h = fopen('php://output', 'w');
        fwrite($h, "\xEF\xBB\xBF");
        fputcsv($h, ['id', 'created_utc', 'visitor_id', 'text_version', 'outcome', 'ip_hash', 'user_agent', 'entry_url'], ';');

        $offset = 0;

        do {
            $rows = $db->setQuery($q, $offset, 5000)->loadObjectList();

            foreach ($rows as $r) {
                fputcsv($h, [$r->id, $r->created, $r->visitor_id, $esc($r->text_version), $r->outcome, $r->ip_hash, $esc($r->user_agent), $esc($r->entry_url)], ';');
            }

            $offset += 5000;
        } while (\count($rows) === 5000);

        fclose($h);
        $app->close();
    }
}
