<?php

declare(strict_types=1);

namespace BrainAppeal\CampusEventsConnector\Import\Event;

/**
 * This event is called before the import process starts.
 *
 * This is mainly used to override the import options before the import starts.
 */
class BeforeImportStartedEvent extends AbstractImportEvent {}
