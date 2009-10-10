<?php
/**
 * crispBB Forum Module
 *
 * @package modules
 * @copyright (C) 2008-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage crispBB Forum Module
 * @link http://xaraya.com/index.php/release/970.html
 * @author crisp <crisp@crispcreations.co.uk>
 */
/**
 * Modify an item
 *
 * This is a standard function that is called whenever an user
 * wishes to modify an existing module item
 *
 * @author crisp <crisp@crispcreations.co.uk>
 * @param array  $args An array containing all the arguments to this function.
 */
function crispbb_admin_modify($args)
{
    extract($args);
    if (!xarVarFetch('sublink', 'str:1:', $sublink, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'enum:form:update', $phase, 'form', XARVAR_NOT_REQUIRED)) return;
    // allow return url to be over-ridden
    if (!xarVarFetch('return_url', 'str:1:', $return_url, '', XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('fid', 'id', $fid, NULL, XARVAR_NOT_REQUIRED)) return;
    $data = xarMod::apiFunc('crispbb', 'user', 'getforum', array('fid' => $fid, 'privcheck' => true));
    if ($data == 'NO_PRIVILEGES' || empty($data['addforumurl'])) {
        $errorMsg['message'] = xarML('You do not have the privileges required for this action');
        $errorMsg['return_url'] = xarModURL('crispbb', 'user', 'main');
        $errorMsg['type'] = 'NO_PRIVILEGES';
        $errorMsg['pageTitle'] = xarML('No Privileges');
        xarTPLSetPageTitle(xarVarPrepForDisplay($errorMsg['pageTitle']));
        return xarTPLModule('crispbb', 'user', 'error', $errorMsg);
    }

    $userLevel = $data['forumLevel'];
    $secLevels = $data['fprivileges'];

    $invalid = array();
    $pageTitle = $data['fname'];
    $now = time();
    $tracking = xarMod::apiFunc('crispbb', 'user', 'tracking', array('now' => $now));
    // End Tracking
    if (!empty($tracking)) {
        xarVarSetCached('Blocks.crispbb', 'tracking', $tracking);
        xarModUserVars::set('crispbb', 'tracking', serialize($tracking));
    }
    switch($sublink) {
        case 'edit':
            sys::import('modules.dynamicdata.class.objects.master');
            $data['forum'] = DataObjectMaster::getObject(array('name' => 'crispbb_forums'));
            //$data['forum']->setCategories();
            $fieldlist = array('fname','fdesc','fstatus','ftype','category');
            $data['forum']->setFieldlist($fieldlist);
            $data['forum']->getItem(array('itemid' => $data['fid']));
            //$data['fname'] = $data['forum']->properties['fname']->value;
            //$data['fdesc'] = $data['forum']->properties['fdesc']->value;

            if (!xarVarFetch('fname', 'str:1:', $fname, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('fdesc', 'str:1:', $fdesc, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('fstatus', 'int:0:', $fstatus, 0, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('ftype', 'int:0:', $ftype, 0, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('confirm', 'checkbox', $confirm, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('fowner', 'id', $fowner, NULL, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('catid', 'id', $catid, NULL, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('cids', 'list:id', $cids, NULL, XARVAR_DONT_SET)) return;
            if (!xarVarFetch('modify_cids', 'list:id', $cids, NULL, XARVAR_DONT_SET)) return;
            if (!xarVarFetch('redirecturl', 'str:1:255', $redirecturl, '', XARVAR_NOT_REQUIRED)) return;
            if (empty($data['editforumurl'])) {
                $errorMsg['message'] = xarML('You do not have the privileges required for this action');
                $errorMsg['return_url'] = xarModURL('crispbb', 'user', 'main');
                $errorMsg['type'] = 'NO_PRIVILEGES';
                $errorMsg['pageTitle'] = xarML('No Privileges');
                xarTPLSetPageTitle(xarVarPrepForDisplay($errorMsg['pageTitle']));
                return xarTPLModule('crispbb', 'user', 'error', $errorMsg);
            }
            $presets = xarMod::apiFunc('crispbb', 'user', 'getpresets',
                array('preset' => 'forumstatusoptions,topicsortoptions,sortorderoptions,pagedisplayoptions,ftransfields,ttransfields,ptransfields,ftypeoptions'));
            if (!$confirm) {
                if ($phase == 'update') {
                    $data['ftype'] = $ftype;
                }
                $phase = 'form';
            }

            // handle update
            if ($phase == 'update') {

                $settings = array();
                if (!xarVarFetch('topicsperpage', 'int:1:100', $settings['topicsperpage'], $data['topicsperpage'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('topicsortorder', 'enum:ASC:DESC', $settings['topicsortorder'], $data['topicsortorder'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('topicsortfield', 'enum:ptime', $settings['topicsortfield'], $data['topicsortfield'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('postsperpage', 'int:1:100', $settings['postsperpage'], $data['postsperpage'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('postsortorder', 'enum:ASC:DESC', $settings['postsortorder'], $data['postsortorder'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('hottopicposts', 'int:0:100', $settings['hottopicposts'], $data['hottopicposts'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('hottopichits', 'int:0:100', $settings['hottopichits'], $data['hottopichits'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('hottopicratings', 'int:0', $settings['hottopicratings'], $data['hottopicratings'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('topictitlemin', 'int:0:254', $settings['topictitlemin'], $data['topictitlemin'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('topictitlemax', 'int:0:254', $settings['topictitlemax'], $data['topictitlemax'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('topicdescmin', 'int:0:100', $settings['topicdescmin'], $data['topicdescmin'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('topicdescmax', 'int:0:100', $settings['topicdescmax'], $data['topicdescmax'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('topicpostmin', 'int:0:65535', $settings['topicpostmin'], $data['topicpostmin'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('topicpostmax', 'int:0:65535', $settings['topicpostmax'], $data['topicpostmax'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('showstickies', 'int:0:1', $settings['showstickies'], $data['showstickies'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('showannouncements', 'int:0:1', $settings['showannouncements'], $data['showannouncements'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('showfaqs', 'int:0:1', $settings['showfaqs'], $data['showfaqs'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('iconfolder', 'str:0', $settings['iconfolder'], $data['iconfolder'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('icondefault', 'str:0', $settings['icondefault'], $data['icondefault'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('floodcontrol', 'int:0:3600', $settings['floodcontrol'], $data['floodcontrol'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('postbuffer', 'int:0:60', $settings['postbuffer'], $data['postbuffer'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('topicapproval', 'checkbox', $settings['topicapproval'], $data['topicapproval'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('replyapproval', 'checkbox', $settings['replyapproval'], $data['replyapproval'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('ftransforms', 'list', $settings['ftransforms'], array(), XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('ttransforms', 'list', $settings['ttransforms'], array(), XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('ptransforms', 'list', $settings['ptransforms'], array(), XARVAR_NOT_REQUIRED)) return;
                foreach ($presets['ftransfields'] as $field => $option) {
                    if (!isset($settings['ftransforms'][$field]))
                        $settings['ftransforms'][$field] = array();
                }
                foreach ($presets['ttransfields'] as $field => $option) {
                    if (!isset($settings['ttransforms'][$field]))
                        $settings['ttransforms'][$field] = array();
                }
                foreach ($presets['ptransfields'] as $field => $option) {
                    if (!isset($settings['ptransforms'][$field]))
                        $settings['ptransforms'][$field] = array();
                }
                $isvalid = $data['forum']->checkInput();

                switch ($ftype) {
                    case '0': // regular forum type
                    default:
                    break;
                    case '1': // redirected forum
                        if (strlen($redirecturl) < 1 || strlen($redirecturl) > 100) {
                            $invalid['redirecturl'] = xarML('URL must be 255 characters or less');
                        }
                        if (empty($invalid)) {
                            if (strstr($redirecturl,'://')) {
                                if (!ereg("^http://|https://|ftp://", $redirecturl)) {
                                    $invalid['redirecturl'] = 'URLs of this type are not allowed';
                                }
                            } elseif (substr($redirecturl,0,1) == '/') {
                                $server = xarServerGetHost();
                                $protocol = xarServerGetProtocol();
                                $redirecturl = $protocol . '://' . $server . $redirecturl;
                            } else {
                                $baseurl = xarServer::getBaseURL();
                                $redirecturl = $baseurl . $redirecturl;
                            }
                        }
                    break;
                }
                if (empty($invalid) && $isvalid) {
                    if (!xarSecConfirmAuthKey()) return;
                    $redirected = !empty($data['redirected']['redirecturl']) ? $data['redirected']['redirecturl'] : '';
                    if ($redirected != $redirecturl) {
                        $settings['redirected'] = array('redirecturl' => $redirecturl);
                    } else {
                        $settings['redirected'] = $data['redirected'];
                    }
                    $data['forum']->properties['fsettings']->setValue(serialize($settings));
                     // keep counts in synch when updating the forum
                    $numtopics = xarMod::apiFunc('crispbb', 'user', 'counttopics',
                        array(
                            'fid' => $fid,
                            'tstatus' => array(0,1)
                        ));
                    $data['forum']->properties['numtopics']->setValue($numtopics);


                    $numreplies = xarMod::apiFunc('crispbb', 'user', 'countposts',
                        array(
                            'fid' => $fid,
                            'tstatus' => array(0,1),
                            'pstatus' => 0
                        ));
                    $numreplies = !empty($numreplies) ? $numreplies - $numtopics : 0;
                    $data['forum']->properties['numreplies']->setValue($numreplies);


                    $numtopicsubs = xarMod::apiFunc('crispbb', 'user', 'counttopics',
                        array(
                            'fid' => $fid,
                            'tstatus' => 2
                        ));
                    $data['forum']->properties['numtopicsubs']->setValue($numtopicsubs);


                    $numtopicdels = xarMod::apiFunc('crispbb', 'user', 'counttopics',
                        array(
                            'fid' => $fid,
                            'tstatus' => 5
                        ));
                    $data['forum']->properties['numtopicdels']->setValue($numtopicdels);


                    $numreplysubs = xarMod::apiFunc('crispbb', 'user', 'countposts',
                        array(
                            'fid' => $fid,
                            'tstatus' => array(0,1),
                            'pstatus' => 2
                        ));
                    $data['forum']->properties['numreplysubs']->setValue($numreplysubs);


                    $numreplydels = xarMod::apiFunc('crispbb', 'user', 'countposts',
                        array(
                            'fid' => $fid,
                            'tstatus' => array(0,1),
                            'pstatus' => 5
                        ));
                    $data['forum']->properties['numreplydels']->setValue($numreplydels);
                    $data['forum']->updateItem();

                    xarSessionSetVar('crispbb_statusmsg', xarML('#(1) settings updated', $fname));
                    if (empty($return_url)) {
                        $return_url = xarModURL('crispbb', 'admin', 'modify',
                            array('fid' => $fid, 'sublink' => 'edit'));
                    }
                    xarMod::apiFunc('crispbb', 'user', 'getitemtypes');
                    xarResponse::Redirect($return_url);
                }
                // failed validation, pass back the input
                $data['fname'] = $fname;
                $data['fdesc'] = $fdesc;
                $data['fstatus'] = $fstatus;
                $data['fowner'] = $fowner;
                foreach ($settings as $k => $v) {
                    if (!isset($data[$k])) continue;
                    $data[$k] = $v;
                }
            }
            // handle form
            $item = array();
            $item['module'] = 'crispbb';
            $item['itemtype'] = $data['itemtype'];
            $item['itemid'] = $fid;
            $hooks = xarModCallHooks('item', 'modify', $fid, $item);
            if (xarVarIsCached('Hooks.dynamicdata','withupload') || xarModIsHooked('uploads', 'crispbb', $data['itemtype'])) {
                $data['withupload'] = 1;
            } else {
                $data['withupload'] = 0;
            }
            // change categories display to a dropdown list
            if (isset($hooks['categories'])) $hooks['categories'] = '';

            switch ($data['ftype']) {
                case '0':
                default:
                break;
                case '1':
                    if (empty($redirecturl)) {
                        $redirecturl = !empty($data['redirected']['redirecturl']) ? $data['redirected']['redirecturl'] : '';
                    }
                break;
            }
            $ftypes = array();
            $ftypes[0] = array('id' => 0, 'name' => xarML('Normal Forum'));
            $ftypes[1] = array('id' => 1, 'name' => xarML('Redirected Forum'));
            $data['ftypeoptions'] = $ftypes; // $presets['ftypeoptions'];
            $data['redirecturl'] = !empty($redirecturl) ? $redirecturl : '';

            if (!empty($data['iconfolder'])) {
                $iconlist = xarMod::apiFunc('crispbb', 'user', 'gettopicicons',
                    array('iconfolder' => $data['iconfolder'], 'shownone' => true));
                $data['iconlist'] = $iconlist;
            } else {
                $data['iconlist'] = array();
            }

            $forumtype = $data['itemtype'];
            $topicstype = xarMod::apiFunc('crispbb', 'user', 'getitemtype',
                array('fid' => $fid, 'component' => 'topics'));
            $poststype = xarMod::apiFunc('crispbb', 'user', 'getitemtype',
                array('fid' => $fid, 'component' => 'posts'));
            $data['ftranshooks'] = xarModGetHookList('crispbb', 'item', 'transform', $forumtype);
            $data['ttranshooks'] = xarModGetHookList('crispbb', 'item', 'transform', $topicstype);
            $data['ptranshooks'] = xarModGetHookList('crispbb', 'item', 'transform', $poststype);
            $data['statusoptions'] = $presets['forumstatusoptions'];
            $tsortoptions = $presets['topicsortoptions'];
            if (!xarModIsAvailable('ratings') || !xarModIsHooked('ratings', 'crispbb', $topicstype)) {
                unset($tsortoptions['numratings']);
            }
            $data['topicfields'] = $tsortoptions;
            $data['orderoptions'] = $presets['sortorderoptions'];
            $data['pageoptions'] = $presets['pagedisplayoptions'];
            $pageTitle = 'Edit ' . $data['fname'];
            xarMod::apiFunc('crispbb', 'user', 'getitemtypes');
        break;

        case 'forumhooks':
        case 'topichooks':
        case 'posthooks':
            if ( ($sublink == 'forumhooks' && empty($data['hooksforumurl'])) ||
                ($sublink == 'topichooks' && empty($data['hookstopicsurl'])) ||
                ($sublink == 'posthooks' && empty($data['hookspostsurl'])) ) {
                $errorMsg['message'] = xarML('You do not have the privileges required for this action');
                $errorMsg['return_url'] = xarModURL('crispbb', 'user', 'main');
                $errorMsg['type'] = 'NO_PRIVILEGES';
                $errorMsg['pageTitle'] = xarML('No Privileges');
                xarTPLSetPageTitle(xarVarPrepForDisplay($errorMsg['pageTitle']));
                return xarTPLModule('crispbb', 'user', 'error', $errorMsg);
            }
            if ($sublink == 'forumhooks') {
                $component = 'forum';
                $label = 'forums';
                // make sure cats are available and hooked to forums
            } elseif ($sublink == 'topichooks') {
                $component = 'topics';
                $label = 'topics';
                // make sure hitcount is available and hooked to topics
            } elseif ($sublink == 'posthooks') {
                $component = 'posts';
                $label = 'posts';
            }
            $itemtype = xarMod::apiFunc('crispbb', 'user', 'getitemtype',
                array('fid' => $fid, 'component' => $component));
            $mastertype = xarMod::apiFunc('crispbb', 'user', 'getitemtype',
                array('fid' => 0, 'component' => $component));
            // get all the hooks available
            $hooklist = xarMod::apiFunc('modules', 'admin', 'gethooklist');
            // hook modules must have at least one of these hook functions
            $hooktypes = array('new','create','modify','update','display','delete','transform');
            $hooksettings = array();
            foreach ($hooklist as $hookMod => $hookData) {
                // make sure we only get modules with useful hook functions
                $hashooktypes = false;
                foreach ($hookData as $hooktype => $hookedto) {
                    foreach ($hooktypes as $comparetype) {
                        if (strstr($hooktype, $comparetype) !== false) {
                            // only need to find one for this to be true
                            $hashooktypes = true;
                            break;
                        }
                    }
                }
                if (!$hashooktypes) continue;
                if ($hookMod == 'categories') {
                    $ishooked = false;
                    $hookStatus = 2;
                    $hookMessage = xarML('Categories hooks are disabled for all items in crispBB', $label);
                } elseif ($hookMod == 'hitcount') {
                    if ($component == 'topics') {
                        $ishooked = true;
                        $hookStatus = 2;
                        $hookMessage = xarML('Hitcount is always hooked to all topics in crispBB');
                    } else {
                        $ishooked = false;
                        $hookStatus = 2;
                        $hookMessage = xarML('Hitcount hooks are disabled for #(1) in crispBB', $label);
                    }
                } elseif ($hookMod == 'crispsubs') {
                    if ($component == 'topics') {
                        if (xarModIsHooked($hookMod, 'crispbb', 0)) {
                            $ishooked = true;
                            $hookStatus = 0;
                            $hookMessage = xarML('This module is hooked to all itemtypes in crispBB');
                        } elseif (xarModIsHooked($hookMod, 'crispbb', $mastertype)) {
                            $ishooked = true;
                            $hookStatus = 0;
                            $hookMessage = xarML('This module is hooked to all #(1) in crispBB', $label);
                        } else {
                            $ishooked = xarModIsHooked($hookMod, 'crispbb', $itemtype);
                            $hookStatus = 1;
                            $hookMessage = xarML('Hook this module to #(1) #(2)', $data['fname'], $component != 'forum' ? $label : '');
                        }
                    } else {
                        $ishooked = false;
                        $hookStatus = 2;
                        $hookMessage = xarML('crispSubs hooks are disabled for #(1) in crispBB', $label);
                    }
                } else {
                    if (xarModIsHooked($hookMod, 'crispbb', 0)) {
                        $ishooked = true;
                        $hookStatus = 0;
                        $hookMessage = xarML('This module is hooked to all itemtypes in crispBB');
                    } elseif (xarModIsHooked($hookMod, 'crispbb', $mastertype)) {
                        $ishooked = true;
                        $hookStatus = 0;
                        $hookMessage = xarML('This module is hooked to all #(1) in crispBB', $label);
                    } else {
                        $ishooked = xarModIsHooked($hookMod, 'crispbb', $itemtype);
                        $hookStatus = 1;
                        $hookMessage = xarML('Hook this module to #(1) #(2)', $data['fname'], $component != 'forum' ? $label : '');
                    }
                }
                $hookModid = xarModGetIdFromName($hookMod);
                $hookModinfo = xarMod::getInfo($hookModid);
                $hooksettings[$hookMod] = array(
                    'status' => $hookStatus,
                    'output' => '',
                    'message' => $hookMessage,
                    'ishooked' => $ishooked,
                    'displayname' => $hookModinfo['displayname']
                );
            }
            if ($phase == 'update') {
                if (empty($invalid)) {
                    if (!xarSecConfirmAuthKey()) return;
                    $isupdated = false;
                    foreach ($hooksettings as $checkmod => $checkvals) {
                        // skip hooks that can't be changed from here
                        if ($checkvals['status'] <> 1) continue;
                        xarVarFetch("hooked_" . $checkmod,'isset',$ishooked,'',XARVAR_DONT_REUSE);
                        // Explicit setting to hook module to all items in this component
                        if (!empty($ishooked) && isset($ishooked[1]) && !empty($ishooked[1])) {
                            // only hook if not already hooked
                            if (!$checkvals['ishooked']) {
                                xarMod::apiFunc('modules','admin','enablehooks',
                                    array(
                                        'callerModName' => 'crispbb',
                                        'callerItemType' => $itemtype,
                                        'hookModName' => $checkmod
                                    ));
                                $isupdated = true;
                            }
                        // No setting
                        } else {
                            // unhook if currently hooked
                            if ($checkvals['ishooked']) {
                                xarMod::apiFunc('modules','admin','disablehooks',
                                    array(
                                        'callerModName' => 'crispbb',
                                        'callerItemType' => $itemtype,
                                        'hookModName' => $checkmod
                                    ));
                                $isupdated = true;
                            }
                        }
                        xarMod::apiFunc('crispbb', 'user', 'getitemtypes');
                    }
                    xarMod::apiFunc('crispbb', 'user', 'getitemtypes');
                    // call updateconfig hooks
                    $hookargs['module'] = 'crispbb';
                    $hookargs['itemtype'] = $itemtype;
                    xarModCallHooks('module','updateconfig','crispbb', $hookargs);
                    // update the status message
                    xarSessionSetVar('crispbb_statusmsg', xarML('#(1) hooks for #(2) updated', ucfirst($component), $data['fname']));
                    // if no returnurl specified, return to forumconfig, this sublink
                    if (empty($return_url)) {
                        $return_url = xarModURL('crispbb', 'admin', 'modify',
                            array('fid' => $fid, 'sublink' => $sublink));
                    }
                    xarResponse::Redirect($return_url);
                    return true;
                }
            }
            $hooks = xarModCallHooks('module', 'modifyconfig', 'crispbb',
                            array('module' => 'crispbb', 'itemtype' => $itemtype));
            if (isset($hooks['categories'])) unset($hooks['categories']);
            foreach ($hooks as $hookmodname => $hookvals) {
                $hooksettings[$hookmodname]['output'] = $hookvals;
            }
            $data['hooksettings'] = $hooksettings;
            $pageTitle .= $component == 'forum' ? ' Hooks' : ' ' . ucfirst($component) . ' Hooks';
            xarMod::apiFunc('crispbb', 'user', 'getitemtypes');
        break;

        case 'privileges':
            if (empty($data['privsforumurl'])) {
                $errorMsg['message'] = xarML('You do not have the privileges required for this action');
                $errorMsg['return_url'] = xarModURL('crispbb', 'user', 'main');
                $errorMsg['type'] = 'NO_PRIVILEGES';
                $errorMsg['pageTitle'] = xarML('No Privileges');
                xarTPLSetPageTitle(xarVarPrepForDisplay($errorMsg['pageTitle']));
                return xarTPLModule('crispbb', 'user', 'error', $errorMsg);
            }
            if (!xarVarFetch('privs', 'list', $privs, array(), XARVAR_NOT_REQUIRED)) return;
            $presets = xarMod::apiFunc('crispbb', 'user', 'getpresets',
                array('preset' => 'privactionlabels,fprivileges,privleveloptions'));
            $defaults = $presets['fprivileges'];
            if (empty($privs)) {
                $privs = $data['fprivileges'];
            } else {
                // format privs for storage
                foreach ($defaults as $level => $actions) {
                    foreach ($actions as $key => $value) {
                        if (!isset($privs[$level][$key])) {
                            $privs[$level][$key] = 0;
                        }
                    }
                }
            }
            if ($phase == 'update') {
                // check for factory reset
                if (!xarVarFetch('resetprivs', 'checkbox', $resetprivs, false, XARVAR_NOT_REQUIRED)) return;
                // perform factory reset
                if ($resetprivs) {
                    $privs = $defaults;
                }
                if (empty($invalid)) {
                    if (!xarSecConfirmAuthKey()) return;
                    if (!xarMod::apiFunc('crispbb', 'admin', 'update',
                        array(
                            'fid' => $fid,
                            'fprivileges' => $privs,
                            'nohooks' => true
                        ))) return;
                    // update the status message
                    xarSessionSetVar('crispbb_statusmsg', xarML('Forum privileges updated'));
                    // if no returnurl specified, return to forumconfig
                    if (empty($return_url)) {
                        $return_url = xarModURL('crispbb', 'admin', 'modify', array('fid' => $fid, 'sublink' => 'privileges'));
                    }
                    xarResponse::Redirect($return_url);
                    return true;
                }
            }
            // format privs for form display
            foreach ($privs as $level => $actions) {
                foreach ($actions as $key => $value) {
                    if ($level < 300) {
                        $privs[$level][$key] = 2;
                    } elseif (!empty($privs[$level-100][$key])) {
                        $privs[$level][$key] = 2;
                    } elseif ($level == 600 && $key != 'editforum' && $key != 'addforum') {
                        $privs[$level][$key] = 2;
                    } elseif ($level == 700 && $key != 'deleteforum' && $key != 'editforum') {
                        $privs[$level][$key] = 2;
                    } elseif ($level == 800) {
                        $privs[$level][$key] = 2;
                    } else {
                        $privs[$level][$key] = $value;
                    }
                }
            }
            $data['actions'] = $presets['privactionlabels'];
            $data['levels'] = $presets['privleveloptions'];
            $data['privs'] = $privs;
            $pageTitle .= ' Privileges';
        break;
        default:
            $presets = xarMod::apiFunc('crispbb', 'user', 'getpresets',
                array('preset' => 'privactionlabels,privleveloptions'));
            $data['actions'] = $presets['privactionlabels'];
            $data['levels'] = $presets['privleveloptions'];
        break;
    }

    $data['invalid'] = $invalid;
    $data['sublink'] = $sublink;
    $data['menulinks'] = xarMod::apiFunc('crispbb', 'admin', 'getmenulinks',
        array('current_mod' => 'crispbb',
            'current_type' => 'admin',
            'current_func' => 'modify',
            'current_sublink' => $sublink,
            'fid' => $fid,
            'catid' => $data['catid'],
            'secLevels' => $secLevels
    ));
    $data['pageTitle'] = $pageTitle;
    $data['hookoutput'] = !empty($hooks) ? $hooks : '';

    xarTPLSetPageTitle(xarVarPrepForDisplay(xarML($pageTitle)));

    return $data;
}
?>