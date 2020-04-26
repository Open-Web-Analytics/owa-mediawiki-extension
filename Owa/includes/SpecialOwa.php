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
 * Class SpecialOwa
 *
 * @author     Peter Adams <peter@openwebanalytics.com>
 * @copyright  2020 Peter Adams <peter@openwebanalytics.com>
 * @version    $Id$
 */
class SpecialOwa extends SpecialPage
{
    /**
     * SpecialOwa constructor.
     */
    public function __construct()
    {
        global $wgOwaEnableSpecialPage;

        parent::__construct('Owa', 'owa-view', $wgOwaEnableSpecialPage);
    }

    public function doesWrites() {
        return true;
    }

    /**
     * @param string|null $par
     * @throws PermissionsError
     */
    public function execute($par)
    {
        global $wgSitename,
               $wgScriptPath,
               $wgServer,
               $wgDBname,
               $wgDBserver,
               $wgDBuser,
               $wgDBpassword,
               $wgOwaSiteId;

        $output = $this->getOutput();
        $this->setHeaders();

        $output->disallowUserJs();
        $this->checkPermissions();

        $owa = OpenWebAnalyticsInstance::get();
        $params = [];

        $action = owa_coreAPI::getRequestParam('action');

        // if no action is found...
        if (!($action || empty($action))) {
            $output->addHTML($owa->handleRequestFromURL());
            return;
        }

        // check to see that owa is installed.
        if ($owa->getSetting('base', 'install_complete')) {
            $output->addHTML($owa->handleRequest($params));
            return;
        }

        define('OWA_INSTALLING', true);

        $siteUrl = $wgServer . $wgScriptPath;

        if (!$wgOwaSiteId) {
            $wgOwaSiteId = md5($siteUrl);
        }

        $params = [
            'site_id'       => $wgOwaSiteId,
            'name'          => $wgSitename,
            'domain'        => $siteUrl,
            'description'   => '',
            'action'        => 'base.installEmbedded',
            'db_type'       => 'mysql',
            'db_name'       => $wgDBname,
            'db_host'       => $wgDBserver,
            'db_user'       => $wgDBuser,
            'db_password'   => $wgDBpassword,
            'public_url'    => $wgServer . $wgScriptPath . '/extensions/Owa/instance/',
        ];

        $output->addHTML($owa->handleRequest($params));
    }
}