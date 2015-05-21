.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

Extension Configuration
-----------------------

Username (username)
^^^^^^^^^^^^^^^^^^^
This username is used to communicate with the MAM API. You will receive this from
your MAM Provider.


Password (password)
^^^^^^^^^^^^^^^^^^^

This password is used to communicate with the MAM API. You will receive this from
your MAM Provider.


Name of the Connector in the MAM Config (connector_name)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

This connector_name is used to communicate with the MAM API. You will receive this from
your MAM Provider.


Base for MAM API Communication (base_url)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

This is the url at which the mam api endpoint is located. You will receive this from your
MAM Provider


Customer name in MAM (customer)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Self defined name of this Instance to identify it towards the MAM API.


PID of a Sysfolder to Store configuration and event queue (storage_pid)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Specify the uid of a Sysfolder here that will be used as pid for configuration and
event queue items.


Local path for stored files (base_path)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Path where the local files received from MAM will be stored. This needs to be identical
to the MAM FileStorage Configuration


Remote shell path that needs to be removed from the received paths (mam_shell_path)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Here you can specify a shell path that will be stripped from the beginning of the received
filepaths.

Example (mam_shell_path = /usr/local/mam/wanzl/):
	/usr/local/mam/wanzl/data/foo.png => data/foo.png
