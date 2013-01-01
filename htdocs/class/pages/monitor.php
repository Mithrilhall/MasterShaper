<?php

/***************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher, unki@netshadow.at
 * All rights reserved
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 ***************************************************************************/

class Page_Monitor extends MASTERSHAPER_PAGE {

   private $total;
   private $names;
   private $colors;
   private $color_count;

   /**
    * Page_Monitor constructor
    *
    * Initialize the Page_Monitor class
    */
   public function __construct()
   {
      $this->rights = 'user_show_monitor';

      $this->total = Array();
      $this->names = Array();
      $this->colors = Array();
      $this->color_count = -1;

   } // __construct()

   /* interface output */
   public function showList()
   {
      global $tmpl, $page;

      if(isset($_POST['view']))
         $_SESSION['mode'] = $_POST['view'];

      if(isset($page->action))
         $_SESSION['mode'] = $page->action;

      switch($_SESSION['mode']) {
         case 'chains':
            $view = "Chains";
            break;
         case 'pipes':
            $view = "Pipes";
            break;
         case 'bandwidth':
            $view = "Bandwidth";
            break;
      }

      // pre-set some variables with default values, if not yet set.
      if(!isset($_SESSION['showif'])) 
         $_SESSION['showif'] = $this->getFirstInterface();
      if(!isset($_SESSION['mode']))
         $_SESSION['mode'] = 'bandwidth';
      if(!isset($_SESSION['graphmode']))
         $_SESSION['graphmode'] = 0;
      if(!isset($_SESSION['showchain']))
         $_SESSION['showchain'] = -1;
      if(!isset($_SESSION['scalemode']))
         $_SESSION['scalemode'] = "kbit";

      $image_loc.= "?uniqid=". mktime();
   
      $tmpl->assign('monitor', $_SESSION['mode']);
      $tmpl->assign('view', $view);
      $tmpl->assign('image_loc', $image_loc);
      if(isset($_SESSION['graphmode']))
         $tmpl->assign('graphmode', $_SESSION['graphmode']);
      if(isset($_SESSION['scalemode']))
         $tmpl->assign('scalemode', $_SESSION['scalemode']);

      $tmpl->register_function("interface_select_list", array(&$this, "smarty_interface_select_list"), false);
      $tmpl->register_function("chain_select_list", array(&$this, "smarty_chain_select_list"), false);

      return $tmpl->fetch("monitor.tpl");

   } // showList()

   /**
    * return the id of the first locatable chain.
    *
    * @return int
    */
   private function getFirstChain()
   {
      global $ms, $db;

      // Get only chains which do not Ignore QoS and are active
      $chain = $db->db_fetchSingleRow("
         SELECT
            chain_idx
         FROM
            ". MYSQL_PREFIX ."chains
         WHERE
            chain_sl_idx NOT LIKE 0
         AND
            chain_active LIKE 'Y'
         AND
            chain_host_idx LIKE '". $ms->get_current_host_profile() ."'
         ORDER BY
            chain_position ASC
         LIMIT
            0,1
      ");

      if(isset($chain->chain_idx))
         return $chain->chain_idx;

      return 0;

   } // getFirstChain()

   /**
    * return the interface name of the first, active to-be-found interface
    *
    * @return string
    */
   private function getFirstInterface()
   {
      global $ms;

      $interfaces = $ms->getActiveInterfaces();

      if(($if = $interfaces->fetch()) === false)
         return false;

      return $if->if_name;

   } // getFirstInterface()

   /**
    * smarty function to generate a HTML select list of chains
    *
    * @return string
    */
   public function smarty_chain_select_list($params, &$smarty)
   {
      global $ms, $db;

      // list only chains which do not Ignore QoS and are active
      $chains = $db->db_query("
         SELECT
            chain_idx,
            chain_name
         FROM
            ". MYSQL_PREFIX ."chains
         WHERE 
            chain_sl_idx!='0'
         AND
            chain_active='Y'
         AND
            chain_fallback_idx<>'0'
         AND
            chain_host_idx LIKE '". $ms->get_current_host_profile() ."'
         ORDER BY
            chain_position ASC
      ");

      while($chain = $chains->fetch()) {
         $string.= "<option value=\"". $chain->chain_idx ."\">". $chain->chain_name ."</option>\n";
      }

      return $string;

   } // smarty_chain_select_list

   /**
    * smarty function to generate a HTML select list of interfaces
    *
    * @return string
    */
   public function smarty_interface_select_list($params, &$smarty)
   {
      global $ms;

      $interfaces = $ms->getActiveInterfaces();
      $if_select = "";

      while($interface = $interfaces->fetch()) {

         $if_select.= "<option value=\"". $interface->if_name ."\"";
	 
         if($_SESSION['showif'] == $interface->if_name)
            $if_select.= " selected=\"selected\"";
	    
         $if_select.= ">". $interface->if_name ."</option>\n";

      }

      return $if_select;

   } // smarty_interface_select_list()

   /**
    * prepare the values-array for jqPlog
    *
    * @return mixed
    */
   public function get_jqplot_values()
   {
      global $ms, $db;

      /* ****************************** */
      /* graphmode                      */
      /*     0  Accumulated Lines       */
      /*     1  Lines                   */
      /*     2  Bars                    */
      /*     3  Pie plots               */
      /* ****************************** */

      if(isset($_POST['showif']))
         $_SESSION['showif'] = $_POST['showif'];
      if(isset($_POST['scalemode']))
         $_SESSION['scalemode'] = $_POST['scalemode'];
      if(isset($_POST['showchain']))
         $_SESSION['showchain'] = $_POST['showchain'];

      if(!isset($_SESSION['mode']) ||
         !isset($_SESSION['graphmode']) ||
         !isset($_SESSION['showchain']) ||
         !isset($_SESSION['scalemode'])) {
         return _("some necessary variables are not set. stopping here.");
      }

      /* time settings */
      $time_now  = mktime();
      $time_past = mktime() - 300;

      $sth = $db->db_prepare("
         SELECT
            stat_time,
            stat_data
         FROM
            ". MYSQL_PREFIX ."stats
         WHERE 
            stat_time >= ?
         AND
            stat_time <= ?
         AND
            stat_host_idx LIKE ?
         ORDER BY
            stat_time ASC
      ");

      $db->db_execute($sth, array(
         $time_past,
         $time_now,
         $ms->get_current_host_profile(),
      ));

      switch($_SESSION['mode']) {
         /* chain- & pipe-view */
         default:
            $tc_match = $_SESSION['showif'] ."_";
            break;

         /* bandwidth-view */
         case 'bandwidth':
            $tc_match = "_1:1\$";
            break;
      }

      while($row = $sth->fetch()) {

         if(!($stat = $this->extract_tc_stat($row->stat_data, $tc_match)))
            continue;

         $tc_ids = array_keys($stat);

         foreach($tc_ids as $tc_id) {

            if(!isset($bigdata[$row->stat_time]))
               $bigdata[$row->stat_time] = Array();

            $bigdata[$row->stat_time][$tc_id] = $stat[$tc_id];
         }
      }

      $db->db_sth_free($sth);

      /* $bigdata now contains data like
       *
       * [1275741024] => Array
       *   (
       *       [ipsec0] => 13747
       *       [eth1] => 33792
       *       [ipsec1] => 394
       *       [eth0] => 86509
       *   )
       *
       *  [1275741025] => Array
       *   (
       *       [ipsec0] => 7201
       *       [eth1] => 1266
       *       [ipsec1] => 0
       *       [eth0] => 19934
       *   )
       */

      /* If we have no data here, maybe the tc_collector is not running. Stop. */
      if(!isset($bigdata)) {
         return json_encode(array('error' => 'tc_collector.pl is inactive!'));
      }
	
      /* prepare graph arrays and fill up with data */
      $timestamps = array_keys($bigdata);

      /* loop through all recorded timestamps */
      foreach($timestamps as $timestamp) {

         // ignore empty data
         if(!isset($bigdata[$timestamp]))
            continue;

         // ignore empty data
         if(!is_array($bigdata[$timestamp]) || empty($bigdata[$timestamp]))
            continue;

         $tc_ids = array_keys($bigdata[$timestamp]);

         // loop through all found tc-id's
         foreach($tc_ids as $tc_id) {

            // new tc-id? prepare arrays for it
            if(!isset($plot_array[$tc_id]))
               $plot_array[$tc_id] = array();
            if(!isset($time_array[$tc_id]))
               $time_array[$tc_id] = array();

            // first data found we do not consider
            if(!isset($last_bw[$tc_id])) {
               $last_bw[$tc_id] = $bigdata[$timestamp][$tc_id];
               continue;
            }

            // store difference between previously and currently transfered data
            if(isset($last_bw[$tc_id]))
               $bw = $bigdata[$timestamp][$tc_id] - $last_bw[$tc_id];

            if($bw < 0)
               $bw = 0;

            $bw = $this->convert_to_bandwidth($bw);

            array_push($plot_array[$tc_id], $bw);
            /* jqPlot wants timestamp in milliseconds, so we are multipling with 1000 */
            array_push($time_array[$tc_id], array(($timestamp*1000), $bw));

            $last_bw[$tc_id] = $bigdata[$timestamp][$tc_id];
         }
      }

      /* What shell we graph? */
      switch($_SESSION['mode']) {

         /**
          * Drawing pipes
          */
         case 'pipes':

            switch($_SESSION['graphmode']) {

               /* lines with filled areas */
               case 0:
               /* lines */
               case 1:

                  foreach($tc_ids as $tc_id) {

                     /* don't draw tc-id's that are zero */
                     if(array_sum($plot_array[$tc_id]) <= 0)
                        continue;

                     if(!$this->isPipe($tc_id, $_SESSION['showif'], $_SESSION['showchain']))
                        continue;

                     array_push($this->colors, $this->get_color());
                     array_push($this->names, $this->findName($tc_id, $_SESSION['showif']));
                     array_push($this->total, $time_array[$tc_id]);
                  }
                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC, $this->names, $this->colors);
                  break;

               /* bars */
               case 2:
               /* pies */
               case 3:

                  foreach($tc_ids as $tc_id) {

                     if(!$this->isPipe($tc_id, $_SESSION['showif'], $_SESSION['showchain']))
                        continue;

                     $bps = round(array_sum($plot_array[$tc_id])/count($plot_array[$tc_id]), 0);

                     /* skip if out-of-range */
                     if($bps <= 0)
                        continue;

                     if($_SESSION['graphmode'] == 3)
                        $name = $this->findName($tc_id, $_SESSION['showif']) ." (". $bps ." ". $this->get_scale_mode($_SESSION['scalemode']) .")";
                     else 
                        $name = $this->findName($tc_id, $_SESSION['showif']);

                     array_push($this->colors, $this->get_color());
                     array_push($this->names, $name);

                     if($_SESSION['graphmode'] == 2)
                        array_push($this->total, array($bps));

                     if($_SESSION['graphmode'] == 3)
                        array_push($this->total, array($name, $bps));
                  }

                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC, $this->names, $this->colors);
                  break;
            }
            break;

         case 'chains':

            /**
             * Drawing chains
             */
            switch($_SESSION['graphmode']) {
               /* lines with filled areas */
               case 0:
               /* lines */
               case 1:
                  $counter = 0;
                  foreach($tc_ids as $tc_id) {

                     if(!$this->isChain($tc_id, $_SESSION['showif']) || preg_match("/1:.*00/", $tc_id))
                        continue;

                     /* if chain's bandwidth usage is zero, ignore it */
                     if($plot_array[$tc_id] <= 0)
                        continue;

                     /* do not return more than 15 chains */
                     if($counter > 15)
                        continue;

                     array_push($this->colors, $this->get_color());
                     array_push($this->names, $this->findName($tc_id, $_SESSION['showif']));
                     array_push($this->total, $time_array[$tc_id]);
                     $counter++;
                  }
                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC, $this->names, $this->colors);
                  break;

               /* bars */
               case 2:
               /* pies */
               case 3:

                  foreach($tc_ids as $tc_id) {

                     // is not a chain and not fallback
                     if(!$this->isChain($tc_id, $_SESSION['showif']) || preg_match("/1:.*00/", $tc_id))
                        continue;

                     $bps = round(array_sum($plot_array[$tc_id])/count($plot_array[$tc_id]), 0);

                     if($bps <= 0 || preg_match("/1:.*00/", $tc_id))
                        continue;

                     if($_SESSION['graphmode'] == 3)
                        $name = $this->findName($tc_id, $_SESSION['showif']) ." (". $bps ." ". $this->get_scale_mode($_SESSION['scalemode']) .")";
                     else
                        $name = $this->findName($tc_id, $_SESSION['showif']);

                     array_push($this->colors, $this->get_color());
                     array_push($this->names, $name);

                     if($_SESSION['graphmode'] == 2)
                        array_push($this->total, $bps);

                     if($_SESSION['graphmode'] == 3)
                        array_push($this->total, array($name, $bps));
                  }

                  /* sort so the most bandwidth consuming is on first place */
                  array_multisort($this->total, SORT_DESC | SORT_NUMERIC, $this->names, $this->colors);
                  break;

            }

            break;
	 
         case "bandwidth":

            // no data available for that interface? break out...
            if(!isset($time_array[$_SESSION['showif']]))
               break;
            if(!is_array($time_array[$_SESSION['showif']]))
               break;
            if(empty($time_array[$_SESSION['showif']]))
               break;

            $this->total = array($time_array[$_SESSION['showif']]);
            break;
      }

      $json_obj = Array(
         'time_end'       => strftime("%H:%M:%S", $time_now),
         'interface'      => $_SESSION['showif'],
         'scalemode'      => $this->get_scale_mode($_SESSION['scalemode']),
         'graphmode'      => $_SESSION['graphmode'],
         'data'           => json_encode($this->total)
      );

      if(isset($this->names) && !empty($this->names))
         $json_obj['names'] = json_encode($this->names);
      if(isset($this->colors) && !empty($this->colors))
         $json_obj['colors'] = json_encode($this->colors);

      return(json_encode($json_obj));

   } // get_jqplot_values()

   /**
    * splitup tc_collector string that is stored in the database
    *
    * @return array
    */
   private function extract_tc_stat($line, $limit_to = "")
   {  
      $data  = Array();
      $pairs = Array();
      $pairs = preg_split('/,/', $line);

      foreach($pairs as $pair) {

         list($key, $value) = preg_split('/=/', $pair);
         if(!preg_match("/". $limit_to ."/", $key))
            continue;

         $key = preg_replace("/". $limit_to ."/", "", $key);
         if($value >= 0)
            $data[$key] = $value;
         else
            $data[$key] = 0;
      }

      return $data;

   } // extract_tc_stat()

   /* returns pipe/chain name according tc_id */
   private function findName($id, $interface)
   {
      global $ms, $db;

      if(preg_match("/1:.*00/", $id)) {
         return "Fallback";
      }

      $tc_id = $db->db_fetchSingleRow("
         SELECT
            id_pipe_idx,
            id_chain_idx
         FROM
            ". MYSQL_PREFIX ."tc_ids
         WHERE
            id_tc_id='". $id ."'
         AND
            id_if='". $interface ."'
         AND
            id_host_idx LIKE '". $ms->get_current_host_profile() ."'
      ",true);

      if(!$tc_id)
         return "n/a";

      // for Pipes, id_pipe_idx must be set
      if(isset($tc_id->id_pipe_idx) and $tc_id->id_pipe_idx > 0) {

         $pipe = $db->db_fetchSingleRow("
            SELECT
               pipe_name
            FROM
               ". MYSQL_PREFIX ."pipes
            WHERE
               pipe_idx='". $tc_id->id_pipe_idx ."'
         ");

         return $pipe->pipe_name;
      }

      // for Chains, id_chain_idx must be set
      if(isset($tc_id->id_chain_idx) and $tc_id->id_chain_idx > 0) {

         $chain = $db->db_fetchSingleRow("
            SELECT
               chain_name
            FROM
               ". MYSQL_PREFIX ."chains
            WHERE
               chain_idx LIKE '". $tc_id->id_chain_idx ."'
            AND
               chain_host_idx LIKE '". $ms->get_current_host_profile() ."'
         ");

         return $chain->chain_name;
      }

      return $id;

   } // findName()

   /* check if tc_id is a pipe */
   private function isPipe($tc_id, $if, $chain)
   {
      global $ms, $db;

      $row = $db->db_fetchSingleRow("
         SELECT
            id_tc_id
         FROM
            ". MYSQL_PREFIX ."tc_ids
         WHERE
            id_if LIKE '". $if ."'
         AND
            id_chain_idx LIKE '". $chain ."'
         AND
            id_pipe_idx NOT LIKE 0
         AND
            id_tc_id LIKE '". $tc_id ."'
         AND
            id_host_idx LIKE '". $ms->get_current_host_profile() ."'
      ");

      if(!isset($row->id_tc_id))
         return false;

      return true;

   } // isPipe() 

   /* check if tc_id is a chain */
   private function isChain($tc_id, $if)
   {
      global $ms, $db;

      $row = $db->db_fetchSingleRow("
         SELECT
            id_tc_id
         FROM
            ". MYSQL_PREFIX ."tc_ids
         WHERE
            id_if LIKE '". $if ."'
         AND 
            id_tc_id LIKE '". $tc_id ."'
         AND
            id_pipe_idx LIKE 0
         AND
            id_host_idx LIKE '". $ms->get_current_host_profile() ."'
      ");

      if(!isset($row->id_tc_id))
         return false;

      return true;

   } // isChain()

   private function get_scale_mode($scalemode)
   {
      switch($scalemode) {
         case 'bit':
            return 'bps';
         case 'byte':
            return 'Bps';
         case 'kbit':
            return 'kbps';
         case 'kbyte':
            return 'kBps';
         case 'mbit':
            return 'Mbps';
         case 'mbyte':
            return 'MBps';
      }

   } // get_scale_mode()

   private function convert_to_bandwidth($bw)
   {
      if($bw == 0)
         return 0;

      // it is what we have already...
      if($_SESSION['scalemode'] == 'bit')
         return round($bw, 1);

      switch($_SESSION['scalemode']) {
         case 'byte':
            $bw = round($bw / 8, 1);
            break;
         default:
         case 'kbit':
            $bw = round($bw / 1024, 1);
            break;
         case 'kbyte':
            $bw = round($bw / (1024*8), 1);
            break;
         case 'mbit':
            $bw = round($bw / 1048576, 1);
            break;
         case 'mbyte':
            $bw = round($bw / (1048576*8), 1);
            break;
      }

      return $bw;

   } // convert_to_bandwidth()

   private function get_color()
   {
      $seriesColors = Array(
         "#4bb2c5",
         "#EAA228",
         "#c5b47f",
         "#579575",
         "#839557",
         "#958c12",
         "#953579",
         "#4b5de4",
         "#d8b83f",
         "#ff5800",
         "#0085cc",
         "#c747a3",
         "#cddf5a",
         "#FBD178",
         "#26B4E3",
         "#bd70c7"
      );

      if($this->color_count == count($seriesColors)-1)
         $this->color_count = -1;

      $this->color_count++;

      return $seriesColors[$this->color_count];

   } // get_color()

} // class Page_Monitor

$obj = new Page_Monitor;
$obj->handler();

?>
