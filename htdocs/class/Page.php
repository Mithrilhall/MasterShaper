<?php

/**
 * @package MasterShaper
 * @subpackage Pagej
 */

class Page {

   static private $instance;

   static function instance($page = null)
   {
      if(!self::$instance) {
         self::$instance = new Page($page);
      }
      return self::$instance;
   }

   private function __construct($page = null)
   {
      if(isset($page))
         $this->parse($page);

      $this->uri = $_SERVER['REQUEST_URI'];

   } // __construct()

   public function parse($page)
   {
      global $ms, $db, $rewriter;

      $sth = $db->db_prepare("
         SELECT
            *
         FROM
            ". MYSQL_PREFIX ."pages
         WHERE
            ? REGEXP page_uri_pattern
      ");

      $res = $db->db_execute($sth, array(
         $page,
      ));

      if($res->numRows() <= 0)
         return false;

      if(!$row = $res->fetchRow())
         return false;

      if(!isset($row->page_includefile))
         return false;

      $this->name = $row->page_name;
      $this->uri  = $row->page_uri;
      $this->uri_pattern = $row->page_uri_pattern;
      $this->includefile = $row->page_includefile;

      if(preg_match('/(.*)-([0-9]+)/', $rewriter->request)) {
         preg_match('/.*\/(.*)-([0-9]+)/', $rewriter->request, $parts);

         if(!$this->is_valid_action($parts[1]))
            $ms->throwError('Invalid action: '. $parts[1]);
         if(!$this->is_valid_id($parts[2]))
            $ms->throwError('Invalid id: '. $parts[2]);

         $this->action = $parts[1];
         $this->id = $parts[2];
      }
      elseif(preg_match('/.*\/.*\.html$/', $rewriter->request)) {
         preg_match('/.*\/(.*)\.html$/', $rewriter->request, $parts);
         if(!$this->is_valid_action($parts[1]))
            $ms->throwError('Invalid action: '. $parts[1]);

         $this->action = $parts[1];
      }

      return true;

   } // parse()

   /**
    * set a different page
    *
    * @param string $new_page
    */
   public function set_page($new_page)
   {
      $this->parse($new_page);      

   } // set_page()

   /**
    * checks if the requested action is valid
    *
    * @param string $action
    * @return bool
    */
   private function is_valid_action($action)
   {
      $valid_actions = array(
         'overview',
         'login',
         'logout',
         'show',
         'list',
         'new',
         'edit',
         'manage',
         'settings',
         'options',
         'rules',
         'others',
         'load',
         'load-debug',
         'unload',
         'about',
         'mode',
         'chains',
         'chains-jqPlot',
         'pipes',
         'pipes-jqPlot',
         'bandwidth',
         'bandwidth-jqPlot',
      );

      if(in_array($action, $valid_actions))
         return true;

      return false;

   } // is_valid_action()

   /**
    * checks if the submitted id is valid
    *
    * @param int $id
    * @return bool
    */
   private function is_valid_id($id)
   {
      $id = (int) $id;

      if(is_numeric($id))
         return true;

      return false;

   } // is_valid_id()

}
