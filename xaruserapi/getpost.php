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
 * Utility function to retrieve an itemtype id
 *
 * @author crisp <crisp@crispcreations.co.uk>
 * @return int id of the itemtype
 * @throws bad param
 */
function crispbb_userapi_getpost($args)
{
    $posts = xarMod::apiFunc('crispbb', 'user', 'getposts', $args);

    if (count($posts) <> 1) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = ['parameters', 'user', 'getpost', 'crispBB'];
        throw new BadParameterException($vars, $msg);
        return;
    }

    $post = reset($posts);

    return $post;
}
