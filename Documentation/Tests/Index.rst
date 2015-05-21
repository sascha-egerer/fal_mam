.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _developer:

Tests
=====

Functional Tests
----------------

In order to run the functional test you need to execute these steps:

1. execute "composer install" in the typo3 root directory to set up phpunit
2. run "php ./bin/phpunit --colors -v -c ./typo3/sysext/core/Build/FunctionalTests.xml ./typo3conf/ext/fal_mam/Tests/Functional/" to execute the fal_mam functional tests


If you encounter an error similar to the following, just try again this is a simple issue with removing
temporary directories of the functional test:

Functional tests fail because of:**
Crossmedia\FalMam\Tests\Unit\Functional\Repository\EventQueueHandlerTest::successfullEventsShouldBeFinnished
TYPO3\CMS\Core\Tests\Exception: Can not remove folder: /.../typo3temp/functional-7d4c727

