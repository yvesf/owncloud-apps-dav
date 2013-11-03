<?php
/**
 * Copyright (c) 2013 Yves Fischer <yvesf+oc@xapek.org>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
OCP\App::checkAppEnabled('dav');

$RUNTIME_APPTYPES = array('authentication', 'logging');
OC_App::loadApps($RUNTIME_APPTYPES);

// Initialize Server
$rootCollection = new Sabre_DAV_SimpleCollection('');
$server = new OC_Connector_Sabre_Server($rootCollection);
$server->httpRequest = new OC_Connector_Sabre_Request();
$server->setBaseUri($baseuri);
$server->addPlugin(new Sabre_DAV_Auth_Plugin(new OC_Connector_Sabre_Auth(), 'ownCloud'));
$server->addPlugin(new Sabre_DAVACL_Plugin());
$server->addPlugin(new Sabre_DAV_Browser_Plugin(false)); // Show something in the Browser, but no upload


// principals
$principalBackend = new OC_Connector_Sabre_Principal();
$principalCollection = new Sabre_CalDAV_Principal_Collection($principalBackend);
$principalCollection->disableListing = true; // Disable listing
$rootCollection->addChild($principalCollection);


if (OCP\App::isEnabled('calendar')) {
    require 'calendar/appinfo/app.php';
    // caldav backend
    $caldavBackend    = new OC_Connector_Sabre_CalDAV();
    $calendarCollection = new OC_Connector_Sabre_CalDAV_CalendarRoot($principalBackend, $caldavBackend);
    $calendarCollection->disableListing = true; // Disable listening
    $rootCollection->addChild($calendarCollection);
    $server->addPlugin(new Sabre_CalDAV_Plugin());
    $server->addPlugin(new Sabre_CalDAV_ICSExportPlugin());
}

if (OCP\App::isEnabled('contacts')) {
    require 'contacts/appinfo/app.php';
    $addressbookbackends = array();
    $addressbookbackends[] = new OCA\Contacts\Backend\Database(\OCP\User::getUser());
    $carddavBackend = new OCA\Contacts\CardDAV\Backend(array('local', 'shared'));
    $addressBookCollection = new OCA\Contacts\CardDAV\AddressBookRoot($principalBackend, $carddavBackend);
    $addressBookCollection->disableListing = true; // Disable listing

    $rootCollection->addChild($addressBookCollection);
    $server->addPlugin(new OCA\Contacts\CardDAV\Plugin());
    $server->addPlugin(new Sabre_CardDAV_VCFExportPlugin());
}

if(defined('DEBUG') && DEBUG) {
	$server->debugExceptions = true;
}

// And off we go!
$server->exec();
