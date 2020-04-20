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
 * Class OpenWebAnalyticsHooks
 *
 * @author     Peter Adams <peter@openwebanalytics.com>
 * @copyright  2020 Peter Adams <peter@openwebanalytics.com>
 * @version    $Id$
 */
class OpenWebAnalyticsHooks
{
    /**
     * @var string[]
     */
    private static $_roleLookup = [
        '*'                 => 'everyone',
        'user'              => 'viewer',
        'autoconfirmed'     => 'viewer',
        'emailconfirmed'    => 'viewer',
        'bot'               => 'viewer',
        'sysop'             => 'admin',
        'bureaucrat'        => 'admin',
        'developer'         => 'admin',
    ];

    /**
     * @var int[]
     */
    private static $_roleHierarchy = [
        'everyone' => 10,
        'viewer' => 20,
        'admin' => 30,
    ];

    /**
     * @param $article
     * @param $row
     */
    public static function onArticlePageDataAfter($article, $row) {
        $owa = OpenWebAnalyticsInstance::get();
        $owa->setPageTitle($article->getTitle()->getText());
        $owa->setPageType('Article');
    }

    /**
     * @param $special
     * @param $subPage
     */
    public static function onSpecialPageAfterExecute($special, $subPage) {
        $owa = OpenWebAnalyticsInstance::get();
        $owa->setPageTitle($special->getTitle()->getText());
        $owa->setPageType('Article');
    }

    /**
     * @param $categoryArticle
     */
    public static function onCategoryPageView(&$categoryArticle) {
        $owa = OpenWebAnalyticsInstance::get();
        $owa->setPageTitle($categoryArticle->getTitle()->getText());
        $owa->setPageType('Category');
    }

    /**
     * @param OutputPage $out
     * @param Skin $skin
     */
    public static function onBeforePageDisplay(OutputPage $out, Skin $skin) {
        global $wgRequest, $wgOwaThirdPartyCookies, $wgOwaCookieDomain;

        if ($wgRequest->getVal('action') === 'edit' || $wgRequest->getVal('title') === 'Special:Owa') {
            return;
        }

        $owa = OpenWebAnalyticsInstance::get();

        if (!$owa->getSetting('base', 'install_complete')) {
            return;
        }

        $cmds  = "";

        if ($wgOwaThirdPartyCookies) {
            $cmds .= "owa_cmds.push( ['setOption', 'thirdParty', true] );";
        }

        if ($wgOwaCookieDomain) {
            $cmds .= "owa_cmds.push( ['setCookieDomain', '$wgOwaCookieDomain'] );";
        }

        $page_properties = $owa->getAllEventProperties($owa->pageview_event);

        if ($page_properties) {
            $page_properties_json = json_encode($page_properties);
            $cmds .= "owa_cmds.push( ['setPageProperties', $page_properties_json] );";
        }

        $options = ['cmds' => $cmds];

        $tags = $owa->placeHelperPageTags(false, $options);
        $out->addHTML($tags);
    }

    /**
     * @param $wikiPage
     * @param User $user
     * @param $content
     * @param $summary
     * @param $isMinor
     * @param $isWatch
     * @param $section
     * @param $flags
     * @param Revision $revision
     */
    public static function onPageContentInsertComplete(&$wikiPage, User &$user, $content, $summary, $isMinor, $isWatch, $section, &$flags, Revision $revision) {
        $label = $wikiPage->getTitle()->getText();
        self::trackAction('Article Created', $label);
    }

    /**
     * @param $wikiPage
     * @param $user
     * @param $mainContent
     * @param $summaryText
     * @param $isMinor
     * @param $isWatch
     * @param $section
     * @param $flags
     * @param $revision
     * @param $status
     * @param $originalRevId
     * @param $undidRevId
     */
    public static function onPageContentSaveComplete($wikiPage, $user, $mainContent, $summaryText, $isMinor, $isWatch, $section, $flags, $revision, $status, $originalRevId, $undidRevId) {
        if (!($flags & EDIT_UPDATE)) {
            return;
        }

        $label = $wikiPage->getTitle()->getText();
        self::trackAction('Article Edit', $label);
    }

    /**
     * @param $article
     * @param User $user
     * @param $reason
     * @param $id
     * @param $content
     * @param LogEntry $logEntry
     * @param $archivedRevisionCount
     */
    public static function onArticleDeleteComplete(&$article, User &$user, $reason, $id, $content, LogEntry $logEntry, $archivedRevisionCount) {
        $label = $article->getTitle()->getText();
        self::trackAction('Article Deleted', $label);
    }

    /**
     * @param $user
     * @param $autocreated
     */
    public static function onLocalUserCreated($user, $autocreated) {
        self::trackAction('User Account Added');
    }

    /**
     * @param $image
     */
    public static function onUploadComplete(&$image) {
        $label = $image->getLocalFile()->getMimeType();
        self::trackAction('File Upload', $label);
    }

    /**
     * @param User $user
     * @param $inject_html
     * @param $direct
     */
    public static function onUserLoginComplete(User &$user, &$inject_html, $direct) {
        self::trackAction('Login');
    }

    /**
     * @param $actionName
     * @param string $label
     * @return bool
     */
    private static function trackAction($actionName, $label = '') {
        $owa = OpenWebAnalyticsInstance::get();

        if (!$owa->getSetting( 'base', 'install_complete')) {
            return false;
        }

        $owa->trackAction( 'mediawiki', $actionName, $label );
        owa_coreAPI::debug( "logging action event " . $actionName );

        return true;
    }

    /**
     * @param $authStatus
     * @return bool
     */
    public static function authUser($authStatus)
    {
        global $wgUser, $wgOwaSiteId;

        if (!$wgUser->isLoggedIn()) {
            return false;
        }

        $cu = owa_coreAPI::getCurrentUser();
        $cu->setAuthStatus(true);

        $cu->setUserData('user_id', $wgUser->getName());
        $cu->setUserData('email_address', $wgUser->getEmail());
        $cu->setUserData('real_name', $wgUser->getRealName());
        $cu->setRole(self::lookupRole( $wgUser->getGroups()));

        // set list of allowed sites. In this case it's only this wiki.

        $domains = [$wgOwaSiteId];
        // load assigned sites list by domain
        $cu->loadAssignedSitesByDomain($domains);
        $cu->setInitialized();

        return true;
    }

    /**
     * @param array $groups
     * @return string
     */
    private static function lookupRole($groups = [])
    {
        $hierarchyHelper = 0;
        $owaRole = 'everyone';

        foreach ($groups as $group) {
            // Role not found just continue
            if(!in_array($group, self::$_roleLookup)) {
               continue;
            }

            $currentOwaRole = self::$_roleLookup[$group];
            $currentHierarchy = self::$_roleHierarchy[$currentOwaRole];

            // Has the current owa role less permission than the previous -> continue
            if ($currentHierarchy <= $hierarchyHelper) {
               continue;
            }

            $owaRole = $currentOwaRole;

            // Hierarchy is already max, stop execution else continue the loop
            if (max(array_values(self::$_roleHierarchy)) == $currentHierarchy) {
               break;
            }
        }

        return $owaRole;
    }
}