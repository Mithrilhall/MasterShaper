<?php

/* *************************************************************************
 *
 * Copyright (c) by Andreas Unterkircher, unki@netshadow.at
 * All rights reserved
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  any later version.
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
 * *************************************************************************/

require_once "shaper.class.php";

class MASTERSHAPER_RPC {

   public function __construct()
   {
      session_start();

   } // __construct()

   function process_ajax_request()
   {
      require_once 'HTML/AJAX/Server.php';

      $server = new HTML_AJAX_Server();
      $server->handleRequest();

      $ms = new MASTERSHAPER;

      if(isset($_GET['action']) && (!is_string($_GET['action']))) {
         return;
      }

      switch($_GET['action']) {
   
         case 'get_page_title':
            print $ms->get_page_title();
            break;

         case 'get_main_menu':
            print $ms->get_main_menu();
            break;

         case 'get_content':
            print $ms->get_content();
            break;

         case 'check_login':
            print $ms->check_login();
            break;

         case 'logout':
            $ms->destroySession();
            break;

      }

   } // process_ajax_request();

}

$rpc = new MASTERSHAPER_RPC();
$rpc->process_ajax_request();

?>
