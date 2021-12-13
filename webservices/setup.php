<?php
/**
 * @version $Id: setup.php 478 2021-10-01 22:39 $
 -------------------------------------------------------------------------
 LICENSE

 This file is part of Webservices plugin for GLPI.

 Webservices is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Webservices is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU Affero General Public License for more details.

 You should have received a copy of the GNU Affero General Public License
 along with Webservices. If not, see <http://www.gnu.org/licenses/>.

 @package   Webservices
 @author    Noe MANAPO
 
 --------------------------------------------------------------------------
 */

// Init the hooks of the plugins -Needed
function plugin_init_webservices() {
   global $PLUGIN_HOOKS, $CFG_GLPI, $WEBSERVICE_LINKED_OBJECTS;

   Plugin::registerClass('PluginWebservicesClient');

   $PLUGIN_HOOKS['csrf_compliant']['webservices'] = true;

   if (Session::haveright("config", UPDATE)) {
      $PLUGIN_HOOKS["menu_toadd"]['webservices'] = ['config'  => 'PluginWebservicesClient'];
   }
   $PLUGIN_HOOKS['webservices']['webservices'] = 'plugin_webservices_registerMethods';

   //Store objects that can be retrieved when querying another object
   $WEBSERVICE_LINKED_OBJECTS
      = ['with_infocom'          => ['help'           => 'bool, optional',
                                     'itemtype'       => 'Infocom',
                                     'allowed_types'  => $CFG_GLPI['infocom_types'],
                                     'class'          => 'PluginWebservicesMethodInventaire'],

         'with_phone'            => ['help'           => 'bool, optional (Computer only)',
                                     'itemtype'       => 'Phone',
                                     'allowed_types'  => ['Computer'],
                                     'class'          => 'PluginWebservicesMethodInventaire'],

         'with_printer'          => ['help'           => 'bool', 'optional (Computer only)',
                                     'itemtype'       => 'Printer',
                                     'allowed_types'  => ['Computer'],
                                     'class'          => 'PluginWebservicesMethodInventaire'],

         'with_monitor'          => ['help'           => 'bool', 'optional (Computer only)',
                                     'itemtype'       => 'Monitor',
                                     'allowed_types'  => ['Computer'],
                                     'class'          => 'PluginWebservicesMethodInventaire'],

         'with_peripheral'       => ['help'           => 'bool', 'optional (Computer only)',
                                     'itemtype'       => 'Peripheral',
                                     'allowed_types'  => ['Computer'],
                                     'class'          => 'PluginWebservicesMethodInventaire'],

         'with_document'         => ['help'           => 'bool', 'optional',
                                     'itemtype'       => 'Document',
                                     'allowed_types'  => plugin_webservices_getDocumentItemtypes(),
                                     'class'          => 'PluginWebservicesMethodInventaire'],

         'with_ticket'           => ['help'           => 'bool', 'optional',
                                     'itemtype'       => 'Ticket',
                                     'allowed_types'  => plugin_webservices_getTicketItemtypes(),
                                     'class'          => 'PluginWebservicesMethodHelpdesk'],

         'with_tickettask'       => ['help'           => 'bool', 'optional (Ticket only)',
                                     'itemtype'       => 'TicketTask',
                                     'allowed_types'  => ['Ticket'],
                                     'class'          => 'PluginWebservicesMethodHelpdesk'],

         'with_ticketfollowup'   => ['help'           => 'bool', 'optional (Ticket only)',
                                     'itemtype'       => 'ITILFollowup',
                                     'allowed_types'  => ['Ticket'],
                                     'class'          => 'PluginWebservicesMethodHelpdesk'],

         'with_ticketvalidation' => ['help'           => 'bool', 'optional (Ticket only)',
                                     'itemtype'       => 'TicketValidation',
                                     'allowed_types'  => ['Ticket'],
                                     'class'          => 'PluginWebservicesMethodHelpdesk'],

         'with_reservation'      => ['help'           => 'bool',
                                     'itemtype'       => 'Reservation',
                                     'allowed_types'  => $CFG_GLPI['reservation_types'],
                                     'class'          => 'PluginWebservicesMethodInventaire'],

         'with_software'         => ['help'           => 'bool',
                                     'itemtype'       => 'Software',
                                     'allowed_types'  => ['Computer'],
                                     'class'          => 'PluginWebservicesMethodInventaire'],

         'with_softwareversion'  => ['help'           => 'bool',
                                     'itemtype'       => 'SoftwareVersion',
                                     'allowed_types'  => ['Software'],
                                     'class'          => 'PluginWebservicesMethodInventaire'],

         'with_softwarelicense'  => ['help'           => 'bool',
                                     'itemtype'       => 'SoftwareLicense',
                                     'allowed_types'  => ['Software'],
                                     'class'          => 'PluginWebservicesMethodInventaire'],

         'with_contract'         => ['help'           => 'bool',
                                     'itemtype'       => 'Contract',
                                     'allowed_types'  => $CFG_GLPI['contract_types'],
                                     'class'          => 'PluginWebservicesMethodInventaire']];
}


function plugin_version_webservices() {

   return ['name'           => __('Web Services', 'webservices'),
           'version'        => '3.0.0',
           'author'         => 'Remi Collet, Nelly Mahu-Lasson',
           'license'        => 'GPLv2+',
           'homepage'       => 'https://forge.glpi-project.org/projects/webservices',
           'minGlpiVersion' => '9.5',
           'requirements'   => ['glpi' => ['min' => '9.5',
                                           'max' => '9.6']]];
}


// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_webservices_check_prerequisites() {

   if (version_compare(GLPI_VERSION,'9.5','lt') || version_compare(GLPI_VERSION,'9.6','ge')) {
      echo "This plugin requires GLPI >= 9.5 and GLPI < 9.6";
   } else if (!extension_loaded("soap")) {
      echo "Incompatible PHP Installation. Requires module soap";
   } else if (!function_exists("xmlrpc_encode")) {
      echo "Incompatible PHP Installation. Requires module xmlrpc";
   } else if (!function_exists("json_encode")) {
      echo "Incompatible PHP Installation. Requires module json";
   } else {
      return true;
   }
   return false;
}


// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_webservices_check_config() {
   global $DB;

   return $DB->tableExists("glpi_plugin_webservices_clients");
}


function plugin_webservices_getDocumentItemtypes() {
   global $CFG_GLPI;

   return $CFG_GLPI['document_types'];
}


function plugin_webservices_getNetworkPortItemtypes() {
   global $CFG_GLPI;

   return $CFG_GLPI['networkport_types'];
}


function plugin_webservices_getTicketItemtypes() {
   global $CFG_GLPI;

   return $CFG_GLPI['ticket_types'];

}
