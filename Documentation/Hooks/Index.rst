.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _developer:

Hooks
================

there are several hooks fired in the eventQueueHandler that you can hook into.
to register a class method for a hook you need to register the method for a specific
hook like this in your ext_localconf.php file:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXT']['fal_mam']['EventQueueHandler']['assetUpdated'][] = '\Crossmedia\Foo\Hooks\DummyHook->someMethod';


This class should look similar to this:

.. code-block:: php

	namespace Crossmedia\Foo\Hooks;

	class DummyHook {
		public function someMethod($parameters, $eventQueueHandler) {
			...
		}
	}


fileCreated
^^^^^^^^^^^

called after creating a new file on the filesystem

+----------------+---------------+---------------------------------+
| Parameter      | Data type     | Description                     |
+================+===============+=================================+
| $path          | string        | Path to the created file        |
+----------------+---------------+---------------------------------+


fileUpdated
^^^^^^^^^^^

called after creating a file was updated on the filesystem

+----------------+---------------+---------------------------------+
| Parameter      | Data type     | Description                     |
+================+===============+=================================+
| $fileObject    | object        | Object representing a file      |
+----------------+---------------+---------------------------------+


assetCreated
^^^^^^^^^^^^

called after creating a file was created in the database

+----------------+---------------+---------------------------------+
| Parameter      | Data type     | Description                     |
+================+===============+=================================+
| $path          | string        | Path to the related file        |
+----------------+---------------+---------------------------------+
| $fileObject    | object        | Object representing a file      |
+----------------+---------------+---------------------------------+


assetUpdated
^^^^^^^^^^^^

called after creating a file was updated in the database

+----------------+---------------+---------------------------------+
| Parameter      | Data type     | Description                     |
+================+===============+=================================+
| $path          | string        | Path to the related file        |
+----------------+---------------+---------------------------------+
| $fileObject    | object        | Object representing a file      |
+----------------+---------------+---------------------------------+


assetDeleted
^^^^^^^^^^^^

called after a file has been deleted

+----------------+---------------+---------------------------------+
| Parameter      | Data type     | Description                     |
+================+===============+=================================+
| $fileObject    | object        | Object representing a file      |
+----------------+---------------+---------------------------------+


mapMetadata
^^^^^^^^^^^

called after the default mapping took place.

+----------------+---------------+-----------------------------------------------------+
| Parameter      | Data type     | Description                                         |
+================+===============+=====================================================+
| $data          | array         | contains a array of fields that will be mapped      |
+----------------+---------------+-----------------------------------------------------+