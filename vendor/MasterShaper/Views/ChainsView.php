<?php

/**
 *
 * This file is part of MasterShaper.

 * MasterShaper, a web application to handle Linux's traffic shaping
 * Copyright (C) 2015 Andreas Unterkircher <unki@netshadow.net>

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.

 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace MasterShaper\Views;

class ChainsView extends DefaultView
{
    protected static $view_default_mode = 'list';
    protected static $view_class_name = 'chains';
    private $chains;

    public function __construct()
    {
        try {
            $this->chains = new \MasterShaper\Models\ChainsModel;
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load ChainsModel!', false, $e);
            return false;
        }

        parent::__construct();
    }

    public function showList($pageno = null, $items_limit = null)
    {
        global $session, $tmpl;

        if (!isset($pageno) || empty($pageno) || !is_numeric($pageno)) {
            if (($current_page = $this->getSessionVar("current_page")) === false) {
                $current_page = 1;
            }
        } else {
            $current_page = $pageno;
        }

        if (!isset($items_limit) || is_null($items_limit) || !is_numeric($items_limit)) {
            if (($current_items_limit = $this->getSessionVar("current_items_limit")) === false) {
                $current_items_limit = -1;
            }
        } else {
            $current_items_limit = $items_limit;
        }

        if (!$this->chains->hasItems()) {
            return parent::showList();
        }

        try {
            $pager = new \MasterShaper\Controllers\PagingController(array(
                'delta' => 2,
            ));
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load PagingController!', false, $e);
            return false;
        }

        if (!$pager->setPagingData($this->chains->getItems())) {
            $this->raiseError(get_class($pager) .'::setPagingData() returned false!');
            return false;
        }

        if (!$pager->setCurrentPage($current_page)) {
            $this->raiseError(get_class($pager) .'::setCurrentPage() returned false!');
            return false;
        }

        if (!$pager->setItemsLimit($current_items_limit)) {
            $this->raiseError(get_class($pager) .'::setItemsLimit() returned false!');
            return false;
        }

        global $tmpl;
        $tmpl->assign('pager', $pager);

        if (($data = $pager->getPageData()) === false) {
            $this->raiseError(get_class($pager) .'::getPageData() returned false!');
            return false;
        }

        if (!isset($data) || empty($data) || !is_array($data)) {
            $this->raiseError(get_class($pager) .'::getPageData() returned invalid data!');
            return false;
        }

        $this->avail_items = array_keys($data);
        $this->items = $data;

        if (!$this->setSessionVar("current_page", $current_page)) {
            $this->raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        if (!$this->setSessionVar("current_items_limit", $current_items_limit)) {
            $this->raiseError(get_class($session) .'::setVariable() returned false!');
            return false;
        }

        return parent::showList();

    }

    public function chainsList($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars('smarty.IB.item_list.index');

        if (!isset($index) || empty($index)) {
            $index = 0;
        }

        if (!isset($this->avail_items) || empty($this->avail_items)) {
            $repeat = false;
            return $content;
        }

        if ($index >= count($this->avail_items)) {
            $repeat = false;
            return $content;
        }

        $item_idx = $this->avail_items[$index];
        $item =  $this->items[$item_idx];

        $smarty->assign("item", $item);

        $index++;
        $smarty->assign('smarty.IB.item_list.index', $index);
        $repeat = true;

        return $content;
    }

    public function smartyTargetGroupSelectLists($params, &$smarty)
    {
        if (!array_key_exists('group', $params)) {
            $this->raiseError(__METHOD__ .'(), missing "group" parameter!');
            $repeat = false;
            return;
        }

        global $db;

        /* either "used" or "unused" */
        $group = $params['group'];

        if (isset($params['idx']) && is_numeric($params['idx'])) {
            $idx = $params['idx'];
        } else {
            $idx = 0;
        }

        switch ($group) {
            case 'unused':
                $sql = "SELECT
                        t.target_idx,
                        t.target_name
                    FROM
                        TABLEPREFIXtargets t
                    LEFT JOIN
                        TABLEPREFIXassign_targets_to_targets atg
                    ON
                        t.target_idx=atg.atg_target_idx
                    WHERE
                        atg.atg_group_idx NOT LIKE ?
                    OR
                        ISNULL(atg.atg_group_idx)
                    ORDER BY
                        t.target_name ASC";
                break;
            case 'used':
                $sql = "SELECT
                        t.target_idx,
                        t.target_name
                    FROM
                        TABLEPREFIXassign_targets_to_targets atg
                    LEFT JOIN
                        TABLEPREFIXtargets t
                    ON
                        t.target_idx = atg.atg_target_idx
                    WHERE
                        atg_group_idx LIKE ?
                    ORDER BY
                        t.target_name ASC";
                break;
        }

        $sth = $db->db_prepare($sql);

        $db->db_execute($sth, array(
            $idx
        ));

        $string = "";
        while ($row = $sth->fetch()) {
            $string.= "<option value=\"". $row->target_idx ."\">". $row->target_name ."</option>";
        }

        $db->freeStatement($sth);
        return $string;
    }

    public function showEdit($id, $guid)
    {
        global $tmpl;

        $tmpl->registerPlugin(
            "function",
            "target_group_select_list",
            array(&$this, "smartyTargetGroupSelectLists"),
            false
        );

        try {
            $item = new \MasterShaper\Models\ChainModel(array(
                'idx' => $id,
                'guid' => $guid
            ));
        } catch (\Exception $e) {
            $this->raiseError(__METHOD__ .'(), failed to load ChainModel!', false, $e);
            return false;
        }

        $tmpl->registerPlugin(
            "block",
            "pipe_list",
            array(&$this, "smarty_pipe_list"),
            false
        );

        $tmpl->assign('chain', $item);
        return parent::showEdit($id, $guid);
    }

    public function smarty_pipe_list($params, $content, &$smarty, &$repeat)
    {
        $index = $smarty->getTemplateVars('smarty.IB.pipe_list.index');
        if (!$index) {
            $index = 0;
        }

        if ($index < count($this->avail_pipes)) {

            $pipe_idx = $this->avail_pipes[$index];
            $pipe =  $this->pipes[$pipe_idx];

            // check if pipes original service level got overruled
            if (isset($pipe->apc_sl_idx) && !empty($pipe->apc_sl_idx)) {
                $pipe->sl_in_use = $pipe->apc_sl_idx;
            } else {
                // no override
                $pipe->sl_in_use = $pipe->pipe_sl_idx;
            }

            $smarty->assign('pipe', $pipe);

            $index++;
            $smarty->assign('smarty.IB.pipe_list.index', $index);
            $repeat = true;
        } else {
            $repeat =  false;
        }

        return $content;
    }
}

// vim: set filetype=php expandtab softtabstop=4 tabstop=4 shiftwidth=4:
