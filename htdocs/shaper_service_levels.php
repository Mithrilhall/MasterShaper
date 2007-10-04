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

class MASTERSHAPER_SERVICELEVELS {

   var $db;
   var $parent;
   var $tmpl;

   /* Class constructor */
   function MASTERSHAPER_SERVICELEVELS($parent)
   {
      $this->parent = &$parent;
      $this->db = &$parent->db;
      $this->tmpl = &$this->parent->tmpl;

   } // MASTERSHAPER_SERVICELEVELS()

   /* interface output */
   function show()
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_servicelevels")) {

         $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"service level icon\" />&nbsp;". _("Manage Service Levels"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      if(!isset($_GET['mode'])) {
         $_GET['mode'] = "show";
      }
      if(!isset($_GET['idx']) ||
         (isset($_GET['idx']) && !is_numeric($_GET['idx'])))
         $_GET['idx'] = 0;

      switch($_GET['mode']) {
         default:
         case 'show':
            $this->showList();
            break;
         case 'new':
         case 'edit':
            $this->showEdit($_GET['idx']);
            break;
      }
   
   } // show()

   /**
    * display all service levels
    */
   private function showList()
   {
      $this->avail_service_levels = Array();
      $this->service_levels = Array();

      $res_sl = $this->db->db_query("
         SELECT *
         FROM ". MYSQL_PREFIX ."service_levels
         ORDER BY sl_name ASC
      ");

      $cnt_sl = 0; 

      while($sl = $res_sl->fetchrow()) {
         $this->avail_service_levels[$cnt_sl] = $sl->sl_idx;
         $this->service_levels[$sl->sl_idx] = $sl;
         $cnt_sl++;
      }

      $this->tmpl->register_block("service_level_list", array(&$this, "smarty_sl_list"));
      $this->tmpl->show("service_levels_list.tpl");

   } // showList() 

   /**
    * display interface to create or edit service levels
    */
   function showEdit($idx)
   {
      /* If authentication is enabled, check permissions */
      if($this->parent->getOption("authentication") == "Y" &&
         !$this->parent->checkPermissions("user_manage_ports")) {

         $this->parent->printError("<img src=\"". ICON_HOME ."\" alt=\"home icon\" />&nbsp;". _("MasterShaper Ruleset Service Levels"), _("You do not have enough permissions to access this module!"));
         return 0;
      }

      if($idx != 0) {
         $sl = $this->db->db_fetchSingleRow("
            SELECT *
            FROM ". MYSQL_PREFIX ."service_levels
            WHERE
               sl_idx='". $idx ."'
         ");
      }
      else {
         /* preset values here */
         
      }

      if(!isset($_GET['classifier']))
         $this->tmpl->assign('classifier', $this->parent->getOption("classifier"));
      else
         $this->tmpl->assign('classifier', $_GET['classifier']);

      if(!isset($_GET['qdiscmode']))
         $this->tmpl->assign('qdiscmode', $sl->sl_qdisc);
      else
         $this->tmpl->assign('qdiscmode', $_GET['qdiscmode']);

      $this->tmpl->assign('sl_idx', $idx);
      $this->tmpl->assign('sl_name', $sl->sl_name);
      $this->tmpl->assign('sl_htb_bw_in_rate', $sl->sl_htb_bw_in_rate);
      $this->tmpl->assign('sl_htb_bw_out_rate', $sl->sl_htb_bw_out_rate);
      $this->tmpl->assign('sl_htb_priority', $this->parent->getPriorityName($sl->sl_htb_priority));
      $this->tmpl->assign('sl_hfsc_in_dmax', $sl->sl_hfsc_in_dmax);
      $this->tmpl->assign('sl_hfsc_in_rate', $sl->sl_hfsc_in_rate);
      $this->tmpl->assign('sl_hfsc_out_dmax', $sl->sl_hfsc_out_dmax);
      $this->tmpl->assign('sl_hfsc_out_rate', $sl->sl_hfsc_out_rate);
      $this->tmpl->assign('sl_cbq_in_rate', $sl->sl_cbq_in_rate);
      $this->tmpl->assign('sl_cbq_out_rate', $sl->sl_cbq_out_rate);
      $this->tmpl->assign('sl_cbq_in_priority', $sl->sl_cbq_in_priority);
      $this->tmpl->assign('sl_cbq_out_priority', $sl->sl_cbq_out_priority);
      $this->tmpl->show("service_levels_edit.tpl");

   } // showEdit()

   /**
    * template function which will be called from the target listing template
    */
   public function smarty_sl_list($params, $content, &$smarty, &$repeat)
   {
      $index = $this->tmpl->get_template_vars('smarty.IB.sl_list.index');
      if(!$index) {
         $index = 0;
      }

      if($index < count($this->avail_service_levels)) {

         $sl_idx = $this->avail_service_levels[$index];
         $sl =  $this->service_levels[$sl_idx];

         $this->tmpl->assign('classifier', $this->parent->getOption("classifier"));
         $this->tmpl->assign('sl_idx', $sl_idx);
         $this->tmpl->assign('sl_name', $sl->sl_name);
         $this->tmpl->assign('sl_htb_bw_in_rate', $sl->sl_htb_bw_in_rate);
         $this->tmpl->assign('sl_htb_bw_out_rate', $sl->sl_htb_bw_out_rate);
         $this->tmpl->assign('sl_htb_priority', $this->parent->getPriorityName($sl->sl_htb_priority));
         $this->tmpl->assign('sl_hfsc_in_dmax', $sl->sl_hfsc_in_dmax);
         $this->tmpl->assign('sl_hfsc_in_rate', $sl->sl_hfsc_in_rate);
         $this->tmpl->assign('sl_hfsc_out_dmax', $sl->sl_hfsc_out_dmax);
         $this->tmpl->assign('sl_hfsc_out_rate', $sl->sl_hfsc_out_rate);
         $this->tmpl->assign('sl_cbq_in_rate', $sl->sl_cbq_in_rate);
         $this->tmpl->assign('sl_cbq_out_rate', $sl->sl_cbq_out_rate);
         $this->tmpl->assign('sl_cbq_in_priority', $this->parent->getPriorityName($sl->sl_cbq_in_priority));
         $this->tmpl->assign('sl_cbq_out_priority', $this->parent->getPriorityName($sl->sl_cbq_out_priority));

         $index++;
         $this->tmpl->assign('smarty.IB.sl_list.index', $index);
         $repeat = true;
      }
      else {
         $repeat =  false;
      }

      return $content;

   } // smarty_sl_list

   public function edit()
   {

            if(!isset($_GET['saveit'])) {

               if(!isset($_GET['classifiermode'])) 
                  $_GET['classifiermode'] = $this->parent->getOption("classifier");

	       if(isset($_GET['new'])) {

		  if(!isset($_GET['qdiscmode']))
		     $_GET['qdiscmode'] = $this->parent->getOption("qdisc");
		  
		  $this->parent->startTable("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Create a new Service Level"));

		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;classifiermode=". $_GET['classifiermode'] ."&amp;saveit=1&amp;new=1";
		  $onchange_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;new=". $_GET['new'] ."&amp;qdiscmode=";

		  $current->sl_name = "";

               }
	       else {

		  $current = $this->db->db_fetchSingleRow("SELECT * FROM ". MYSQL_PREFIX ."service_levels WHERE sl_idx='". $_GET['idx'] ."'");

		  if(!isset($_GET['qdiscmode'])) 
		     $_GET['qdiscmode'] = $current->sl_qdisc;

		  $this->parent->startTable("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Modify Service Level") ." ". $this->parent->getClassVar($current, 'sl_name'));

		  $form_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;classifiermode=". $_GET['classifiermode'] ."&amp;saveit=1&amp;idx=". $_GET['idx'] ."&amp;namebefore=". urlencode($this->parent->getClassVar($current, 'sl_name'));

		  $onchange_url = $this->parent->self ."?mode=". $this->parent->mode ."&amp;screen=". $this->parent->screen ."&amp;idx=". $_GET['idx'] ."&amp;qdiscmode=";

               }


               if(!isset($_POST['sl_name']) || $_POST['sl_name'] == "") {

		  $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Service Level"), _("Please enter a service level name!"));
		  $error = 1;

               }

	       if(!$error && isset($_GET['new']) && $this->db->db_fetchSingleRow("SELECT sl_idx FROM ". MYSQL_PREFIX ."service_levels WHERE sl_name LIKE BINARY '". $_POST['sl_name'] ."'")) {

		  $this->parent->printError("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Manage Port"), _("A service level with that name already exists!"));
		  $error = 1;

	       }

	       if(!$error && !isset($_GET['new']) && $_GET['namebefore'] != $_POST['sl_name'] && $this->db->db_fetchSingleRow("SELECT sl_idx FROM ". MYSQL_PREFIX ."service_levels WHERE sl_name LIKE BINARY '". $_POST['sl_name'] ."'")) {

		  $this->parent->printError("<img src=\"". ICON_PORTS ."\" alt=\"port icon\" />&nbsp;". _("Manage Port"), _("A service level with that name already exists!"));
		  $error = 1;

	       }

	       if(!$error) {

                  switch($_GET['classifiermode']) {

                     case 'HTB':

                        if($_POST['sl_htb_priority'] == 0 && $_POST['sl_htb_bw_in_rate'] == "" && $_POST['sl_htb_bw_out_rate'] == "") {

                           $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Service Level"), _("A service level which ignores priority AND not specified inbound or outbound rate is not possible!"));
			   return 0;

                        }
                        else {
								
                           if($_POST['sl_htb_bw_in_rate'] != "" && !is_numeric($_POST['sl_htb_bw_in_rate']))
                              $is_numeric = 0;
		 	
                           if($_POST['sl_htb_bw_out_rate'] != "" && !is_numeric($_POST['sl_htb_bw_out_rate']))
                              $is_numeric = 0;
										
                           if($_POST['sl_htb_bw_in_ceil'] != "" && !is_numeric($_POST['sl_htb_bw_in_ceil']))
                              $is_numeric = 0;
			
                           if($_POST['sl_htb_bw_in_burst'] != "" && !is_numeric($_POST['sl_htb_bw_in_burst']))
                              $is_numeric = 0;
			
                           if($_POST['sl_htb_bw_out_ceil'] != "" && !is_numeric($_POST['sl_htb_bw_out_ceil']))
                              $is_numeric = 0;

                           if($_POST['sl_htb_bw_out_burst'] != "" && !is_numeric($_POST['sl_htb_bw_out_burst']))
                              $is_numeric = 0;
		
                        }
                        break;

                     case 'HFSC':
								
                        /* If umax is specifed, also umax is necessary */
			if(($_POST['sl_hfsc_in_umax'] != "" && $_POST['sl_hfsc_in_dmax'] == "") ||
			   ($_POST['sl_hfsc_out_umax'] != "" && $_POST['sl_hfsc_out_dmax'] == "")) {

			   $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Service Level"), _("Please enter a \"Max-Delay\" value if you have defined a \"Work-Unit\" value!"));
			   return 0;

                        }
			else {

			   if($_POST['sl_hfsc_in_umax'] != "" && !is_numeric($_POST['sl_hfsc_in_umax']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_in_dmax'] != "" && !is_numeric($_POST['sl_hfsc_in_dmax']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_in_rate'] != "" && !is_numeric($_POST['sl_hfsc_in_rate']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_in_ulrate'] != "" && !is_numeric($_POST['sl_hfsc_in_ulrate']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_out_umax'] != "" && !is_numeric($_POST['sl_hfsc_out_umax']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_out_dmax'] != "" && !is_numeric($_POST['sl_hfsc_out_dmax']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_out_rate'] != "" && !is_numeric($_POST['sl_hfsc_out_rate']))
			      $is_numeric = 0;

			   if($_POST['sl_hfsc_out_ulrate'] != "" && !is_numeric($_POST['sl_hfsc_out_ulrate']))
			      $is_numeric = 0;

                        }
			break;

                     case 'CBQ':

			if($_POST['sl_cbq_in_rate'] == "" || $_POST['sl_cbq_out_rate'] == "") {

			   $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Service Level"), _("Please enter a input and output rate!"));
			   return 0;

			}
			else {

			   if($_POST['sl_cbq_in_rate'] != "" && !is_numeric($_POST['sl_cbq_in_rate']))
			      $is_numeric = 0;

			   if($_POST['sl_cbq_out_rate'] != "" && !is_numeric($_POST['sl_cbq_out_rate']))
			      $is_numeric = 0;

			}

			break;

                  }
               }

	       if(!$error && !$is_numeric) {

		     $this->parent->printError("<img src=\"". ICON_SERVICELEVELS ."\" alt=\"servicelevel icon\" />&nbsp;". _("Service Level"), _("Please enter only numerical values for bandwidth parameters!"));
		     $error = 1;

	       }

	       if(!$error) {

		  if(isset($_GET['new'])) {

		     $this->db->db_query("INSERT INTO ". MYSQL_PREFIX ."service_levels (sl_name, sl_htb_bw_in_rate,"
			."sl_htb_bw_in_ceil, sl_htb_bw_in_burst, sl_htb_bw_out_rate, "
			."sl_htb_bw_out_ceil, sl_htb_bw_out_burst, sl_htb_priority, "
			."sl_hfsc_in_umax, sl_hfsc_in_dmax, sl_hfsc_in_rate, sl_hfsc_in_ulrate, "
			."sl_hfsc_out_umax, sl_hfsc_out_dmax, sl_hfsc_out_rate, sl_hfsc_out_ulrate, "
			."sl_cbq_in_rate, sl_cbq_in_priority, sl_cbq_out_rate, sl_cbq_out_priority, "
			."sl_cbq_bounded, sl_qdisc, sl_netem_delay, sl_netem_jitter, sl_netem_random, "
			."sl_netem_distribution, sl_netem_loss, sl_netem_duplication, "
			."sl_netem_gap, sl_netem_reorder_percentage, sl_netem_reorder_correlation, "
			."sl_esfq_perturb, sl_esfq_limit, sl_esfq_depth, sl_esfq_divisor, sl_esfq_hash) "
			."VALUES ('". $_POST['sl_name'] ."', "
			."'". $_POST['sl_htb_bw_in_rate'] ."', "
			."'". $_POST['sl_htb_bw_in_ceil'] ."', "
			."'". $_POST['sl_htb_bw_in_burst'] ."', "
			."'". $_POST['sl_htb_bw_out_rate'] ."', "
			."'". $_POST['sl_htb_bw_out_ceil'] ."', "
			."'". $_POST['sl_htb_bw_out_burst'] ."', "
			."'". $_POST['sl_htb_priority'] ."', "
			."'". $_POST['sl_hfsc_in_umax'] ."', "
			."'". $_POST['sl_hfsc_in_dmax'] ."', "
			."'". $_POST['sl_hfsc_in_rate'] ."', "
			."'". $_POST['sl_hfsc_in_ulrate'] ."', "
			."'". $_POST['sl_hfsc_out_umax'] ."', "
			."'". $_POST['sl_hfsc_out_dmax'] ."', "
			."'". $_POST['sl_hfsc_out_rate'] ."', "
			."'". $_POST['sl_hfsc_out_ulrate'] ."', "
			."'". $_POST['sl_cbq_in_rate'] ."', "
			."'". $_POST['sl_cbq_in_priority'] ."', "
			."'". $_POST['sl_cbq_out_rate'] ."', "
			."'". $_POST['sl_cbq_out_priority'] ."', "
			."'". $_POST['sl_cbq_bounded'] ."', "
			."'". $_POST['sl_qdisc'] ."', "
			."'". $_POST['sl_netem_delay'] ."', "
			."'". $_POST['sl_netem_jitter'] ."', "
			."'". $_POST['sl_netem_random'] ."', "
			."'". $_POST['sl_netem_distribution'] ."', "
			."'". $_POST['sl_netem_loss'] ."', "
			."'". $_POST['sl_netem_duplication'] ."', "
			."'". $_POST['sl_netem_gap'] ."', "
			."'". $_POST['sl_netem_reorder_percentage']."', "
			."'". $_POST['sl_netem_reorder_correlation'] ."', "
			."'". $_POST['sl_esfq_perturb'] ."', "
			."'". $_POST['sl_esfq_limit'] ."', "
			."'". $_POST['sl_esfq_depth'] ."', "
			."'". $_POST['sl_esfq_divisor'] ."', "
			."'". $_POST['sl_esfq_hash'] ."')");

		  }
		  else {

		     $this->db->db_query("UPDATE ". MYSQL_PREFIX ."service_levels SET "
			."sl_name='". $_POST['sl_name'] ."', "
			."sl_htb_bw_in_rate='". $_POST['sl_htb_bw_in_rate'] ."', "
			."sl_htb_bw_in_ceil='". $_POST['sl_htb_bw_in_ceil'] ."', "
			."sl_htb_bw_in_burst='". $_POST['sl_htb_bw_in_burst'] ."', "
			."sl_htb_bw_out_rate='". $_POST['sl_htb_bw_out_rate'] ."', "
			."sl_htb_bw_out_ceil='". $_POST['sl_htb_bw_out_ceil'] ."', "
			."sl_htb_bw_out_burst='". $_POST['sl_htb_bw_out_burst'] ."', "
			."sl_htb_priority='". $_POST['sl_htb_priority'] ."', "
			."sl_hfsc_in_umax='". $_POST['sl_hfsc_in_umax'] ."', "
			."sl_hfsc_in_dmax='". $_POST['sl_hfsc_in_dmax'] ."', "
			."sl_hfsc_in_rate='". $_POST['sl_hfsc_in_rate'] ."', "
			."sl_hfsc_in_ulrate='". $_POST['sl_hfsc_in_ulrate'] ."', "
			."sl_hfsc_out_umax='". $_POST['sl_hfsc_out_umax'] ."', "
			."sl_hfsc_out_dmax='". $_POST['sl_hfsc_out_dmax'] ."', "
			."sl_hfsc_out_rate='". $_POST['sl_hfsc_out_rate'] ."', "
			."sl_hfsc_out_ulrate='". $_POST['sl_hfsc_out_ulrate'] ."', "
			."sl_cbq_in_rate='". $_POST['sl_cbq_in_rate'] ."', "
			."sl_cbq_in_priority='". $_POST['sl_cbq_in_priority'] ."', "
			."sl_cbq_out_rate='". $_POST['sl_cbq_out_rate'] ."', "
			."sl_cbq_out_priority='". $_POST['sl_cbq_out_priority'] ."', "
			."sl_cbq_bounded='". $_POST['sl_cbq_bounded'] ."', "
			."sl_qdisc='". $_POST['sl_qdisc'] ."', "
			."sl_netem_delay='". $_POST['sl_netem_delay'] ."', "
			."sl_netem_jitter='". $_POST['sl_netem_jitter'] ."', "
			."sl_netem_random='". $_POST['sl_netem_random'] ."', "
			."sl_netem_distribution='". $_POST['sl_netem_distribution'] ."', "
			."sl_netem_loss='". $_POST['sl_netem_loss'] ."', "
			."sl_netem_duplication='". $_POST['sl_netem_duplication'] ."', "
			."sl_netem_gap='". $_POST['sl_netem_gap'] ."', "
			."sl_netem_reorder_percentage='". $_POST['sl_netem_reorder_percentage']."', "
			."sl_netem_reorder_correlation='". $_POST['sl_netem_reorder_correlation'] ."', "
			."sl_esfq_perturb='". $_POST['sl_esfq_perturb'] ."', "
			."sl_esfq_limit='". $_POST['sl_esfq_limit'] ."', "
			."sl_esfq_depth='". $_POST['sl_esfq_depth'] ."', "
			."sl_esfq_divisor='". $_POST['sl_esfq_divisor'] ."', "
			."sl_esfq_hash='". $_POST['sl_esfq_hash'] ."' "
			."WHERE sl_idx='". $_GET['idx'] ."'");

		  }

		  $this->parent->goBack();

	       }
	    }
   } // edit()

   public function delete()
   {
      $this->db->db_query("DELETE FROM ". MYSQL_PREFIX ."service_levels WHERE sl_idx='". $_GET['idx'] ."'");

   } // delete()

}

?>
