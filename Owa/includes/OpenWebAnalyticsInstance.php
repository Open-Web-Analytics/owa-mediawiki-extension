<?php
/**
 * This file is part of the owa-mediawiki-extension project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Peter Adams <peter@openwebanalytics.com>
 * @copyright  2020 Peter Adams <peter@openwebanalytics.com>
 * @version    $Id$
 */

# Protect against web entry
if (!defined('MEDIAWIKI')) {
    exit;
}

/**
 * Class OpenWebAnalyticsInstance
 *
 * @author     Peter Adams <peter@openwebanalytics.com>
 * @copyright  2020 Peter Adams <peter@openwebanalytics.com>
 * @version    $Id$
 */
class OpenWebAnalyticsInstance
{
    /**
     * @return owa_mw
     */
    public static function get()
    {
        if (isset($GLOBALS['OpenWebAnalytics']) && $GLOBALS['OpenWebAnalytics']) {
            return $GLOBALS['OpenWebAnalytics'];
        }

        return self::init();
    }

    /**
     * @return owa_php
     */
    private static function init()
    {
        global $wgServer,
               $wgScriptPath,
               $wgMainCacheType,
               $wgMemCachedServers,
               $wgOwaSiteId;

        require_once(__DIR__ . '/../instance/owa_env.php');
        require_once(OWA_BASE_CLASSES_DIR.'owa_php.php');

        /* OWA CONFIGURATION OVERRIDES */
        $owa_config = [];
        // check for memcache. these need to be passed into OWA to avoid race condition.
        if ($wgMainCacheType === CACHE_MEMCACHED) {
            $owa_config['cacheType'] = 'memcached';
            $owa_config['memcachedServers'] = $wgMemCachedServers;
        }

        if (!$wgOwaSiteId) {
            $wgOwaSiteId = md5($wgServer . $wgScriptPath);
        }

        $owa = new owa_php($owa_config);
        $owa->setSiteId($wgOwaSiteId);
        $owa->setSetting('base', 'report_wrapper', 'wrapper_mediawiki.tpl');
        $owa->setSetting('base', 'link_template', '%s&%s');
        $owa->setSetting('base', 'main_url', $wgScriptPath . '/index.php?title=Special:Open_Web_Analytics');
        $owa->setSetting('base', 'main_absolute_url', $wgServer . $owa->getSetting('base', 'main_url'));
        $owa->setSetting('base', 'action_url', $wgServer . $wgScriptPath . '/index.php?action=owa&owa_specialAction');
        $owa->setSetting('base', 'rest_api_url', $owa->getSetting( 'base', 'rest_api_url' ).'?');
        $owa->setSetting('base', 'is_embedded', true);
        $owa->setSetting('base', 'query_string_filters', 'returnto');

        // filter authentication
        $dispatch = owa_coreAPI::getEventDispatch();
        // alternative auth method, sets auth status, role, and allowed sites list.
        $dispatch->attachFilter('auth_status', 'OpenWebAnalyticsHooks::authUser', 0);

        $GLOBALS['OpenWebAnalytics'] = $owa;

        return $owa;
    }
}