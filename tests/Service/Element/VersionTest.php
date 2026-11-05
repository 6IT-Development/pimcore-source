<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license GPLv3 and PCL
 */

namespace Pimcore\Tests\Service\Element;

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Db;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Version;
use Pimcore\Model\Version\Adapter\DatabaseVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\DelegateVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\FileSystemVersionStorageAdapter;
use Pimcore\Model\Version\Adapter\VersionStorageAdapterInterface;
use RuntimeException;
use Pimcore\Tests\Support\Test\TestCase;
use Pimcore\Tests\Support\Util\TestHelper;

/**
 * Class VersionTest
 *
 * @package Pimcore\Tests\Service\Element
 *
 */
class VersionTest extends TestCase
{
    protected function createFileSystemStorageAdapter(): FileSystemVersionStorageAdapter
    {
        return new FileSystemVersionStorageAdapter();
    }

    protected function createDbStorageAdapter(): DatabaseVersionStorageAdapter
    {
        return new DatabaseVersionStorageAdapter(Db::get());
    }

    protected function createDelegateStorageAdapter(int $byteThreshold = 1000): DelegateVersionStorageAdapter
    {
        return new DelegateVersionStorageAdapter($byteThreshold, $this->createDbStorageAdapter(), $this->createFileSystemStorageAdapter());
    }

    protected function createStorageAdapter(string $storageType): VersionStorageAdapterInterface
    {
        return match ($storageType) {
            'fs' => $this->createFileSystemStorageAdapter(),
            'db' => $this->createDbStorageAdapter(),
        };
    }

    /**
     * @return array<string, array{string}>
     */
    public static function storageTypeProvider(): array
    {
        return [
            'fs' => ['fs'],
            'db' => ['db'],
        ];
    }

    protected function setStorageAdapter(VersionStorageAdapterInterface $adapter): void
    {
        $proxy = Pimcore::getContainer()->get(VersionStorageAdapterInterface::class);
        $proxy->setStorageAdapter($adapter);
    }

    protected function getVersionDataFromDb(int $id, string $cType, int $versionCount): array|bool
    {
        $query = 'select v.id, v.binaryFileId, v.binaryFileHash, v.storageType, vd.metaData, vd.binaryData from versions v
        left join
        versionsData vd on
        v.id = vd.id and v.cid = vd.cid and v.ctype = vd.ctype
        where v.cid = ? and v.ctype = ? and v.versionCount = ?';

        $db = Db::get();

        return $db->fetchAssociative($query, [$id, $cType, $versionCount]);
    }

    /**
     * @throws Exception
     */
    public function testDisable(): void
    {
        $this->setStorageAdapter($this->createFileSystemStorageAdapter());
        $savedObject = TestHelper::createEmptyObject();
        $objectId = $savedObject->getId();

        $query = "select count(*) from versions where cid = ? and ctype='object'";
        $db = Db::get();

        $initialCount = $db->fetchOne($query, [$objectId]);
        $this->assertEquals(1, $initialCount, 'initial count must be 1');

        $savedObject->save();
        $countAfterSave = $db->fetchOne($query, [$objectId]);
        $this->assertEquals(2, $countAfterSave, 'expected a new version');

        // disable versioning, version count should remain the same
        Version::disable();
        $savedObject->save();
        $countAfterSave = $db->fetchOne($query, [$objectId]);
        $this->assertEquals(2, $countAfterSave, "seems that Version::disable doesn't work");

        // enable versioning again
        Version::enable();
        $savedObject->save();
        $countAfterSave = $db->fetchOne($query, [$objectId]);
        $this->assertEquals(3, $countAfterSave, "seems that Version::enable doesn't work");
    }

    /**
     * Test for https://github.com/pimcore/pimcore/issues/4667
     */
    public function testCondense(): void
    {
        $this->setStorageAdapter($this->createFileSystemStorageAdapter());
        /** @var Unittest $savedObject */

        // create target object
        $randomText = TestHelper::generateRandomString(190);

        /** @var Unittest $targetObject */
        $targetObject = TestHelper::createEmptyObject();
        $targetObject->setInput($randomText);
        $targetObject->save();

        // create source object
        /** @var Unittest $sourceObject */
        $sourceObject = TestHelper::createEmptyObject();

        $sourceObject->setMultihref([$targetObject]);
        $sourceObject->save();

        $sourceObjectFromDb = Unittest::getById($sourceObject->getId(), ['force' => true]);

        $targetObjects = $sourceObject->getMultihref();
        $this->assertCount(1, $targetObjects, 'expected one target');

        $targetObject = $targetObjects[0];
        $this->assertEquals($randomText, $targetObject->getInput(), 'random text does not match');

        $latestVersion1 = $this->getNewestVersion($sourceObject->getId());
        $content = stream_get_contents($latestVersion1->getFileStream());
        $this->assertStringNotContainsString($randomText, $content, "random text shouldn't be there");

        $multihref = $sourceObjectFromDb->getMultihref();
        $this->assertCount(1, $multihref, 'expected 1 target element');
    }

    // Save a new object and check if the storagetype is set to fs
    public function testStorageAdapterTypeFS(): void
    {
        $this->setStorageAdapter($this->createFileSystemStorageAdapter());
        $object = TestHelper::createEmptyObject();

        $result = $this->getVersionDataFromDb($object->getId(), 'object', 1);

        $this->assertEquals('fs', $result['storageType'], 'expected storagetype fs, but ' . $result['storageType'] . ' was set.');
        $this->assertEmpty($result['binaryFileId'], 'binaryFileId must be empty.');
        $this->assertEmpty($result['binaryData'], 'metaData must be empty.');
        $this->assertEmpty($result['metaData'], 'metaData must be empty.');
    }

    // Save a new object and check if the storagetype is set to db
    public function testStorageAdapterDB(): void
    {
        $this->setStorageAdapter($this->createDbStorageAdapter());
        $object = TestHelper::createEmptyObject();

        $result = $this->getVersionDataFromDb($object->getId(), 'object', 1);

        $this->assertEquals('db', $result['storageType'], 'expected storagetype db, but ' . $result['storageType'] . ' was set.');
        $this->assertNotEmpty($result['metaData'], 'metaData must not be empty.');
        $this->assertEmpty($result['binaryFileId'], 'binaryFileId must be empty.');
        $this->assertEmpty($result['binaryData'], 'binaryData must be empty.');
    }

    // Size of metadata exceeds "byteThreshold". Therefore, the fallback adapter (fs) should be used.
    public function testStorageAdapterDelegate(): void
    {
        $this->setStorageAdapter($this->createDelegateStorageAdapter(10));
        $randomText = TestHelper::generateRandomString(100);
        $object = TestHelper::createEmptyObject();
        $object->setLastname($randomText);
        $object->save();

        $result = $this->getVersionDataFromDb($object->getId(), 'object', 1);

        $this->assertEquals('fs', $result['storageType'], 'expected storagetype fs, but ' . $result['storageType'] . ' was set.');
        $this->assertEmpty($result['binaryFileId'], 'binaryFileId must be empty.');
        $this->assertEmpty($result['binaryData'], 'metaData must be empty.');
        $this->assertEmpty($result['metaData'], 'metaData must be empty.');
    }

    public function testStorageAdapterFSWithBinaryFile(): void
    {
        $this->setStorageAdapter($this->createFileSystemStorageAdapter());
        $randomText = TestHelper::generateRandomString(100);
        $asset = TestHelper::createImageAsset('test_binary_file_id', $randomText);
        $cid = $asset->getId();

        $result = $this->getVersionDataFromDb($cid, 'asset', 1);
        $id1 = $result['id'];
        $binaryFileId1 = $result['binaryFileId'];
        $binaryFileHash1 = $result['binaryFileHash'];
        $storageType = $result['storageType'];

        $this->assertEquals('fs', $storageType, 'expected storagetype fs, but ' . $result['storageType'] . ' was set.');
        $this->assertEmpty($binaryFileId1, 'binaryFileId must be empty.');
        $this->assertNotEmpty($binaryFileHash1, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id1, 'id must not be empty');
        $asset->save();

        $result2 = $this->getVersionDataFromDb($cid, 'asset', 2);
        $id2 = $result2['id'];
        $binaryFileId2 = $result2['binaryFileId'];
        $binaryFileHash2 = $result2['binaryFileHash'];
        $storageType2 = $result2['storageType'];

        $this->assertEquals('fs', $storageType2, 'expected storagetype fs, but ' . $result['storageType'] . ' was set.');
        $this->assertEquals($id1, $binaryFileId2, 'binaryFileId must equal id on asset1');
        $this->assertNotEmpty($binaryFileHash2, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id2, 'id must not be empty');
    }

    public function testStorageAdapterDBWithBinaryFile(): void
    {
        $this->setStorageAdapter($this->createDbStorageAdapter());
        $randomText = TestHelper::generateRandomString(100);
        $asset = TestHelper::createImageAsset('test_binary_file_id', $randomText);
        $cid = $asset->getId();

        $result = $this->getVersionDataFromDb($cid, 'asset', 1);
        $id1 = $result['id'];
        $binaryFileId1 = $result['binaryFileId'];
        $binaryFileHash1 = $result['binaryFileHash'];
        $binaryData1 = $result['binaryData'];
        $metaData1 = $result['metaData'];
        $storageType = $result['storageType'];

        $this->assertEquals('db', $storageType, 'expected storagetype db, but ' . $result['storageType'] . ' was set.');
        $this->assertEmpty($binaryFileId1, 'binaryFileId must be empty.');
        $this->assertNotEmpty($binaryFileHash1, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id1, 'id must not be empty');
        $this->assertNotEmpty($binaryData1, 'binaryData must not be empty');
        $this->assertNotEmpty($metaData1, 'metaData must not be empty');
        $asset->save();

        $result2 = $this->getVersionDataFromDb($cid, 'asset', 2);
        $id2 = $result2['id'];
        $binaryFileId2 = $result2['binaryFileId'];
        $binaryFileHash2 = $result2['binaryFileHash'];
        $binaryData2 = $result2['binaryData'];
        $metaData2 = $result2['metaData'];
        $storageType2 = $result2['storageType'];

        $this->assertEquals('db', $storageType2, 'expected storagetype db, but ' . $result['storageType'] . ' was set.');
        $this->assertEquals($id1, $binaryFileId2, 'binaryFileId must equal id on asset1');
        $this->assertNotEmpty($binaryFileHash2, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id2, 'id must not be empty');
        $this->assertNotEmpty($metaData2, 'metaData must not be empty');
        $this->assertEmpty($binaryData2, 'binaryData must be empty');
    }

    // Size of binary file exceeds "byteThreshold". Therefore, the fallback adapter (fs) should be used.
    public function testStorageAdapterDelegateWithBinaryFile(): void
    {
        $this->setStorageAdapter($this->createDelegateStorageAdapter(10));
        $randomText = TestHelper::generateRandomString(100);
        $asset = TestHelper::createImageAsset('test_binary_file_id', $randomText);
        $cid = $asset->getId();

        $result = $this->getVersionDataFromDb($cid, 'asset', 1);
        $id = $result['id'];
        $binaryFileHash = $result['binaryFileHash'];
        $storageType = $result['storageType'];

        $this->assertEquals('fs', $storageType, 'expected storagetype fs, but ' . $result['storageType'] . ' was set.');
        $this->assertNotEmpty($binaryFileHash, 'binaryFileHash must not be empty');
        $this->assertNotEmpty($id, 'id must not be empty');
    }

    /**
     * @dataProvider storageTypeProvider
     */
    public function testLoadObjectVersion(string $storageType): void
    {
        $this->setStorageAdapter($this->createStorageAdapter($storageType));
        $object = TestHelper::createEmptyObject();
        $object->setLastname('first');
        $object->save();
        $object->setLastname('second');
        $object->save();

        $data = $this->getVersion($object->getId(), 'object', 2)->getData();
        $this->assertInstanceOf(Unittest::class, $data);
        $this->assertSame('first', $data->getLastname());

        $data = $this->getVersion($object->getId(), 'object', 3)->getData();
        $this->assertInstanceOf(Unittest::class, $data);
        $this->assertSame('second', $data->getLastname());
    }

    /**
     * @dataProvider storageTypeProvider
     */
    public function testLoadAssetVersion(string $storageType): void
    {
        $this->setStorageAdapter($this->createStorageAdapter($storageType));
        $first = TestHelper::generateRandomString(100);
        $asset = TestHelper::createImageAsset('test_load_version', $first);
        $asset->setData(TestHelper::generateRandomString(100));
        $asset->save();

        $data = $this->getVersion($asset->getId(), 'asset', 1)->getData();
        $this->assertInstanceOf(Asset::class, $data);
        $this->assertSame($first, $data->getData(), 'the version must return its own binary, not the current file');
    }

    /**
     * @dataProvider storageTypeProvider
     */
    public function testDeleteVersionsSharingABinary(string $storageType): void
    {
        $adapter = $this->createStorageAdapter($storageType);
        $this->setStorageAdapter($adapter);
        $content = TestHelper::generateRandomString(100);
        $asset = TestHelper::createImageAsset('test_shared_binary', $content);
        $asset->save();

        $version1 = $this->getVersion($asset->getId(), 'asset', 1);
        $version2 = $this->getVersion($asset->getId(), 'asset', 2);
        $this->assertSame($version1->getId(), $version2->getBinaryFileId(), 'version 2 must reuse the binary of version 1');

        $version1->delete();
        $this->assertNull(Version::getById($version1->getId()));

        $data = Version::getById($version2->getId())->getData();
        $this->assertInstanceOf(Asset::class, $data);
        $this->assertSame($content, $data->getData(), 'the shared binary must be kept while a version uses it');

        $version2->delete();
        $this->assertNull($adapter->loadBinaryData($version2), 'the binary must be deleted with its last version');
        $this->assertNull($adapter->loadMetaData($version2));
    }

    /**
     * @dataProvider storageTypeProvider
     */
    public function testResaveVersionKeepsItsData(string $storageType): void
    {
        $this->setStorageAdapter($this->createStorageAdapter($storageType));
        $object = TestHelper::createEmptyObject();
        $object->setLastname('kept');
        $object->save();

        $version = $this->getVersion($object->getId(), 'object', 2);
        $version->setNote('a note');
        $version->setPublic(true);
        $version->save();

        $reloaded = Version::getById($version->getId());
        $this->assertSame('a note', $reloaded->getNote());
        $this->assertTrue($reloaded->isPublic());
        $data = $reloaded->getData();
        $this->assertInstanceOf(Unittest::class, $data);
        $this->assertSame('kept', $data->getLastname());
    }

    public function testWithoutVersioningRestoresTheStateAfterAnException(): void
    {
        try {
            Version::withoutVersioning(static function (): void {
                throw new RuntimeException('failed import');
            });
            $this->fail('the exception must be rethrown');
        } catch (RuntimeException) {
        }
        $this->assertTrue(Version::isEnabled(), 'versioning must be enabled again after the callback threw');

        Version::disable();
        Version::withoutVersioning(static fn () => null);
        $this->assertFalse(Version::isEnabled(), 'a disabled state of the caller must be kept');
    }

    public function testDelegateAdapterRejectsAdaptersOfTheSameType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DelegateVersionStorageAdapter(1000, $this->createDbStorageAdapter(), $this->createDbStorageAdapter());
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->needsDb()) {
            $this->setUpTestClasses();
        }
    }

    public function tearDown(): void
    {
        // the storage adapter and the versioning flag are process-wide
        $this->setStorageAdapter($this->createFileSystemStorageAdapter());
        Version::enable();

        parent::tearDown();

        $db = Db::get();
        $db->executeStatement('DROP TABLE IF EXISTS versionsData');
    }

    protected function needsDb(): bool
    {
        return true;
    }

    /**
     * Set up test classes before running tests
     */
    protected function setUpTestClasses(): void
    {
        //Create versionsData table. Needed for tests with DatabaseVersionStorageAdapter
        $db = Db::get();
        $db->executeStatement("CREATE TABLE IF NOT EXISTS `versionsData` (
                                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                  `cid` int(11) unsigned DEFAULT NULL,
                                  `ctype` enum('document','asset','object') DEFAULT NULL,
                                  `metaData` longblob DEFAULT NULL,
                                  `binaryData` longblob DEFAULT NULL,
                                  PRIMARY KEY (`id`)
                                )");
    }

    protected function getNewestVersion(int $id): Version
    {
        $list = new Version\Listing();
        $list->setCondition("ctype = 'object' and cid = ?", [$id]);
        $list->setLimit(1);
        $list->setOrderKey('id');
        $list->setOrder('DESC');
        $list = $list->load();

        return $list[0];
    }

    protected function getVersion(int $cid, string $cType, int $versionCount): Version
    {
        $row = $this->getVersionDataFromDb($cid, $cType, $versionCount);
        $this->assertIsArray($row, 'expected version ' . $versionCount . ' of ' . $cType . ' ' . $cid);

        $version = Version::getById((int)$row['id']);
        $this->assertInstanceOf(Version::class, $version);

        return $version;
    }
}
