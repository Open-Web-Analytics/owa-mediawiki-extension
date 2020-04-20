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
    public function __construct() {
        parent::__construct( 'Owa');
    }

    /**
     * @param string|null $par
     * @throws PermissionsError
     */
    public function execute($par) {
        global  $wgOut,
                $wgUser,
                $wgSitename,
                $wgScriptPath,
                $wgServer,
                $wgDBtype,
                $wgDBname,
                $wgDBserver,
                $wgDBuser,
                $wgDBpassword;

        //must be called after setHeaders for some reason or elsethe wgUser object is not yet populated.
        $this->setHeaders();

        if (!$this->userCanExecute($wgUser)) {
            $this->displayRestrictionError();
        }


        $owa = OpenWebAnalyticsInstance::get();
        $params = [];

        // if no action is found...
        $do = owa_coreAPI::getRequestParam('do');

        if (!($do || empty($do))) {
            return $wgOut->addHTML($owa->handleRequestFromURL());
        }

        // check to see that owa in installed.
        if ($owa->getSetting('base', 'install_complete')) {
            return $wgOut->addHTML($owa->handleRequest($params));
        }

        define('OWA_INSTALLING', true);

        $site_url = $wgServer.$wgScriptPath;

        $params = [
            'site_id'           => md5($site_url),
            'name'              => $wgSitename,
            'domain'            => $site_url,
            'description'       => '',
            'do'                => 'base.installStartEmbedded',
            'db_type'           => $wgDBtype,
            'db_name'           => $wgDBname,
            'db_host'           => $wgDBserver,
            'db_user'           => $wgDBuser,
            'db_password'       => $wgDBpassword,
            'public_url'        => $wgServer.$wgScriptPath.'/extensions/owa/instance/',
        ];

        return $wgOut->addHTML($owa->handleRequest($params));
    }
}