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

class Service_Level extends MsObject {

   /**
    * Service_Level constructor
    *
    * Initialize the Service_Level class
    */
   public function __construct($id = null)
   {
      parent::__construct($id, Array(
         'table_name' => 'service_levels',
         'col_name' => 'sl',
         'fields' => Array(
            'sl_idx' => 'integer',
            'sl_name' => 'text',
            'sl_htb_bw_in_rate' => 'text',
            'sl_htb_bw_in_ceil' => 'text',
            'sl_htb_bw_in_burst' => 'text',
            'sl_htb_bw_out_rate' => 'text',
            'sl_htb_bw_out_ceil' => 'text',
            'sl_htb_bw_out_burst' => 'text',
            'sl_htb_priority' => 'text',
            'sl_hfsc_in_umax' => 'text',
            'sl_hfsc_in_dmax' => 'text',
            'sl_hfsc_in_rate' => 'text',
            'sl_hfsc_in_ulrate' => 'text',
            'sl_hfsc_out_umax' => 'text',
            'sl_hfsc_out_dmax' => 'text',
            'sl_hfsc_out_rate' => 'text',
            'sl_hfsc_out_ulrate' => 'text',
            'sl_cbq_in_rate' => 'text',
            'sl_cbq_in_priority' => 'text',
            'sl_cbq_out_rate' => 'text',
            'sl_cbq_out_priority' => 'text',
            'sl_cbq_bounded' => 'text',
            'sl_qdisc' => 'text',
            'sl_netem_delay' => 'text',
            'sl_netem_jitter' => 'text',
            'sl_netem_random' => 'text',
            'sl_netem_distribution' => 'text',
            'sl_netem_loss' => 'text',
            'sl_netem_duplication' => 'text',
            'sl_netem_gap' => 'text',
            'sl_netem_reorder_percentage' => 'text',
            'sl_netem_reorder_correlation' => 'text',
            'sl_sfq_perturb' => 'text',
            'sl_sfq_quantum' => 'text',
            'sl_esfq_perturb' => 'text',
            'sl_esfq_limit' => 'text',
            'sl_esfq_depth' => 'text',
            'sl_esfq_divisor' => 'text',
            'sl_esfq_hash' => 'text',
         ),
      ));

      /* it seems a new service level gets created, preset some values */
      if(!isset($id) || empty($id)) {
         parent::init_fields(Array(
            'sl_sfq_perturb' => 10,
            'sl_sfq_quantum' => 1532,
         ));
      }

   } // __construct()

   public function swap_in_out()
   {
      $tmp = Array(
         'sl_htb_bw_in_rate' => $this->sl_htb_bw_in_rate,
         'sl_htb_bw_in_ceil' => $this->sl_htb_bw_in_ceil,
         'sl_htb_bw_in_burst' => $this->sl_htb_bw_in_burst,
         'sl_hfsc_in_umax' => $this->sl_hfsc_in_umax,
         'sl_hfsc_in_dmax' => $this->sl_hfsc_in_dmax,
         'sl_hfsc_in_rate' => $this->sl_hfsc_in_rate,
         'sl_hfsc_in_ulrate' => $this->sl_hfsc_in_ulrate,
         'sl_cbq_in_rate' => $this->sl_cbq_in_rate,
         'sl_cbq_in_priority' => $this->sl_cbq_in_priority,
      );

      $this->sl_htb_bw_in_rate = $this->sl_htb_bw_out_rate;
      $this->sl_htb_bw_in_ceil = $this->sl_htb_bw_out_ceil;
      $this->sl_htb_bw_in_burst = $this->sl_htb_bw_out_burst;
      $this->sl_hfsc_in_umax = $this->sl_hfsc_out_umax;
      $this->sl_hfsc_in_dmax = $this->sl_hfsc_out_dmax;
      $this->sl_hfsc_in_rate = $this->sl_hfsc_out_rate;
      $this->sl_hfsc_in_ulrate = $this->sl_hfsc_out_ulrate;
      $this->sl_cbq_in_rate = $this->sl_cbq_out_rate;
      $this->sl_cbq_in_priority = $this->sl_cbq_out_priority;

      $this->sl_htb_bw_out_rate = $tmp['sl_htb_bw_in_rate'];
      $this->sl_htb_bw_out_ceil = $tmp['sl_htb_bw_in_ceil'];
      $this->sl_htb_bw_out_burst = $tmp['sl_htb_bw_in_burst'];
      $this->sl_hfsc_out_umax = $tmp['sl_hfsc_in_umax'];
      $this->sl_hfsc_out_dmax = $tmp['sl_hfsc_in_dmax'];
      $this->sl_hfsc_out_rate = $tmp['sl_hfsc_in_rate'];
      $this->sl_hfsc_out_ulrate = $tmp['sl_hfsc_in_ulrate'];
      $this->sl_cbq_out_rate = $tmp['sl_cbq_in_rate'];
      $this->sl_cbq_out_priority = $tmp['sl_cbq_in_priority'];

      return true;

   } // swap_in_out()

} // class Service_Level

?>
