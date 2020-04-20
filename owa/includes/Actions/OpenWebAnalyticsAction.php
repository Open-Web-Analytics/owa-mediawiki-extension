<?php
/**
 * This file is part of the owa-mediawiki-extension project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author     Lauser, Nicolai (WinHelp GmbH) <n.lauser@winhelp.eu>
 * @copyright  2020 WinHelp GmbH
 * @version    $Id$
 */

/**
 * Class OpenWebAnalyticsAction
 *
 * @author     Lauser, Nicolai (WinHelp GmbH) <n.lauser@winhelp.eu>
 * @copyright  2020 WinHelp GmbH
 * @version    $Id$
 */
class OpenWebAnalyticsAction extends Action
{
    public function getName()
    {
        return 'owa';
    }

    public function show()
    {
        $this->getOutput()->disable();
        $owa = OpenWebAnalyticsInstance::get();
        $owa->handleSpecialActionRequest();
    }
}