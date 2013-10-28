<?php
/**
 * Copyright (c) 2013 Yves Fischer <yvesf+oc@xapek.org>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
OCP\App::checkAppEnabled('alldav');

OCP\App::checkAppEnabled('calendar');
require 'calendar/appinfo/app.php';

OCP\App::checkAppEnabled('contacts');
require 'contacts/appinfo/app.php';

// only need authentication apps
$RUNTIME_APPTYPES=array('authentication');
OC_App::loadApps($RUNTIME_APPTYPES);

// principal backend
$principalBackend = new OC_Connector_Sabre_Principal();

// caldav backend
$caldavBackend    = new OC_Connector_Sabre_CalDAV();

// carddav backend
$addressbookbackends = array();
$addressbookbackends[] = new OCA\Contacts\Backend\Database(\OCP\User::getUser());
$carddavBackend = new OCA\Contacts\CardDAV\Backend(array('local', 'shared'));

// principals collection
$principalCollection = new Sabre_CalDAV_Principal_Collection($principalBackend);
$principalCollection->disableListing = true; // Disable listing

// caldav collection
$calendarCollection = new OC_Connector_Sabre_CalDAV_CalendarRoot($principalBackend, $caldavBackend);
$calendarCollection->disableListing = true; // Disable listening

// carddav collection
$addressBookCollection = new OCA\Contacts\CardDAV\AddressBookRoot($principalBackend, $carddavBackend);
$addressBookCollection->disableListing = true; // Disable listing

// routing
$nodes = array(
	$principalCollection,
	$calendarCollection,
	$addressBookCollection,
	);

// Fire up server
$server = new Sabre_DAV_Server($nodes);
$server->httpRequest = new OC_Connector_Sabre_Request();
$server->setBaseUri($baseuri);
// Add plugins
$server->addPlugin(new Sabre_DAV_Auth_Plugin(new OC_Connector_Sabre_Auth(), 'ownCloud'));
$server->addPlugin(new Sabre_CalDAV_Plugin());
$server->addPlugin(new Sabre_DAVACL_Plugin());
$server->addPlugin(new Sabre_DAV_Browser_Plugin(false)); // Show something in the Browser, but no upload
$server->addPlugin(new Sabre_CalDAV_ICSExportPlugin());
$server->addPlugin(new OCA\Contacts\CardDAV\Plugin());
$server->addPlugin(new Sabre_CardDAV_VCFExportPlugin());

if(defined('DEBUG') && DEBUG) {
	$server->debugExceptions = true;
}
// And off we go!
$server->exec();
