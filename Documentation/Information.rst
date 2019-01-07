.. include:: Includes.txt

Information
==================

With brain_event_connector, you have the ability to import all events via cron-jobs from the CampusEventsAPI in your TYPO3 System as independent records, as well as documents and images.

Possibilities of configuration:

* API-URL
* API-Key
* PageID for the imported records
* Folder for the imported documents and images


Converters
-----------------


* TYPO3 Extension brain_event_convert2news => `EXT:news <https://extensions.typo3.org/extension/news/>`_
* TYPO3 Extension brain_event_convert2ttnews => `EXT:tt_news <https://extensions.typo3.org/extension/tt_news/>`_
* TYPO3 Extension brain_event_convert2ttnewscal => `EXT:ttnews_calendar <https://extensions.typo3.org/extension/ttnews_calendar/>`_

Converts the imported records into target extensions records.
The Conversation is defined in a configuration record in the respective target folder of the import.
In the future, there will be a filter/limitation feature.
You can define the position on which the content is displayed, by Fluid-Templates.
TYPO3 Admins can overwrite the Fluid-Templates.


Frontend-Extension
-----------------

This extension allows the individual display of the imported records. All files can be accessed.
Curretnly there is only a list view (detail view is untested), which redirects directly to Campus-Events.
In the future you can adjust the display of the view with Fluid-Templates.