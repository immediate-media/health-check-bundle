<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\HealthCheckBundle\Tests\Unit\Controller;

use IM\Fabric\Bundle\HealthCheckBundle\Enum\DatabaseType;
use IM\Fabric\Bundle\HealthCheckBundle\Tests\__Mock\TestableHealthCheckController;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;

class HealthCheckControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    // ===== Tests for getAppVersion() =====

    public function testGetAppVersionReturnsFormattedString(): void
    {
        $controller = $this->createTestableController();
        $controller->setParameter('app.version', 'abc123');
        $controller->setParameter('app.build_start_time', '1611915490663');

        $result = $controller->publicGetAppVersion();

        $this->assertSame('abc123_1611915490663', $result);
    }

    public function testGetAppVersionReturnsNullWhenVersionEmpty(): void
    {
        $controller = $this->createTestableController();
        $controller->setParameter('app.version', '');
        $controller->setParameter('app.build_start_time', '1611915490663');

        $result = $controller->publicGetAppVersion();

        $this->assertNull($result);
    }

    public function testGetAppVersionWithZeroBuildStartTime(): void
    {
        $controller = $this->createTestableController();
        $controller->setParameter('app.version', 'v1.0.0');
        $controller->setParameter('app.build_start_time', '0');

        $result = $controller->publicGetAppVersion();

        $this->assertSame('v1.0.0_0', $result);
    }

    // ===== Tests for getLastCommitTimeForHumans() =====

    public function testGetLastCommitTimeForHumansWithValidDate(): void
    {
        $controller = $this->createTestableController();
        $controller->setParameter('app.last_commit_date', '2021-01-29T10:09:02+00:00');

        $result = $controller->publicGetLastCommitTimeForHumans();

        $this->assertSame('2021-01-29T10:09:02+00:00', $result);
    }

    public function testGetLastCommitTimeForHumansWithMalformedDate(): void
    {
        $controller = $this->createTestableController();
        $controller->setParameter('app.last_commit_date', 'not-a-valid-date');

        $result = $controller->publicGetLastCommitTimeForHumans();

        $this->assertNull($result);
    }

    public function testGetLastCommitTimeForHumansWithEmptyString(): void
    {
        $controller = $this->createTestableController();
        $controller->setParameter('app.last_commit_date', '');

        $result = $controller->publicGetLastCommitTimeForHumans();

        $this->assertNull($result);
    }

    // ===== Tests for getBuildTimeForHumans() =====

    public function testGetBuildTimeForHumansWithMilliseconds(): void
    {
        $controller = $this->createTestableController();
        $controller->setParameter('app.build_start_time', '1611915490663');

        $result = $controller->publicGetBuildTimeForHumans();

        // Verify it returns a valid ISO 8601 date
        $expectedTimestamp = 1611915490; // Milliseconds divided by 1000
        $expectedDate = date('c', $expectedTimestamp);
        $this->assertSame($expectedDate, $result);
    }

    public function testGetBuildTimeForHumansWithSeconds(): void
    {
        // 10-digit timestamp (seconds format) - should auto-convert to milliseconds
        $controller = $this->createTestableController();
        $controller->setParameter('app.build_start_time', '1611915490');

        $result = $controller->publicGetBuildTimeForHumans();

        // Should auto-detect seconds and convert to milliseconds
        $expectedDate = date('c', 1611915490);
        $this->assertSame($expectedDate, $result);
    }

    public function testGetBuildTimeForHumansWithZeroTimestamp(): void
    {
        $controller = $this->createTestableController();
        $controller->setParameter('app.build_start_time', '0');

        $result = $controller->publicGetBuildTimeForHumans();

        // Zero is falsy, so the method returns null
        $this->assertNull($result);
    }

    public function testGetBuildTimeForHumansWithEmptyString(): void
    {
        $controller = $this->createTestableController();
        $controller->setParameter('app.build_start_time', '');

        $result = $controller->publicGetBuildTimeForHumans();

        $this->assertNull($result);
    }

    public function testGetBuildTimeForHumansWithNegativeTimestamp(): void
    {
        $controller = $this->createTestableController();
        $controller->setParameter('app.build_start_time', '-1');

        $result = $controller->publicGetBuildTimeForHumans();

        // -1 seconds → intdiv(-1000, 1000) = -1 → valid Unix timestamp (1 second before epoch)
        $this->assertSame(date('c', -1), $result);
    }

    // ===== Tests for testDatabaseConnection() =====

    public function testDatabaseConnectionWithDoctrineRegistry(): void
    {
        $connection = Mockery::mock();
        $connection->expects('connect')->once()->andReturn(true);

        // Use namedMock with the exact class string to match ::class comparison
        // This avoids requiring doctrine/doctrine-bundle as a hard dependency
        $manager = Mockery::namedMock(
            DatabaseType::DOCTRINE->value,
            ManagerRegistry::class
        );
        $manager->expects('getConnection')->once()->andReturn($connection);

        $controller = new TestableHealthCheckController($manager);

        $result = $controller->publicTestDatabaseConnection();

        $this->assertTrue($result);
    }

    public function testDatabaseConnectionWithMongoDBRegistry(): void
    {
        $connection = Mockery::mock();
        $connection->expects('listDatabaseNames')
            ->once()
            ->andReturn(['db1', 'db2']); // Returns iterable array

        // Use namedMock with the exact class string to match ::class comparison
        // This avoids requiring doctrine/mongodb-odm-bundle as a hard dependency
        $manager = Mockery::namedMock(
            DatabaseType::MONGODB->value,
            ManagerRegistry::class
        );
        $manager->expects('getConnection')->once()->andReturn($connection);

        $controller = new TestableHealthCheckController($manager);

        $result = $controller->publicTestDatabaseConnection();

        $this->assertTrue($result);
    }

    public function testDatabaseConnectionWithNullManager(): void
    {
        $controller = new TestableHealthCheckController();

        $result = $controller->publicTestDatabaseConnection();

        $this->assertFalse($result);
    }

    public function testDatabaseConnectionWithUnknownRegistryClass(): void
    {
        // Create a mock of ManagerRegistry but not one of the known types
        $manager = Mockery::mock(ManagerRegistry::class);

        $controller = new TestableHealthCheckController($manager);

        $result = $controller->publicTestDatabaseConnection();

        // Unknown types should match the default case and return false
        $this->assertFalse($result);
    }

    public function testGetReturnsDatabaseErrorResponseOnConnectionFailure(): void
    {
        $connection = Mockery::mock();
        $connection->expects('connect')
            ->once()
            ->andThrow(new RuntimeException('Connection refused'));

        $manager = Mockery::namedMock(
            DatabaseType::DOCTRINE->value,
            ManagerRegistry::class
        );
        $manager->expects('getConnection')->once()->andReturn($connection);

        $controller = new TestableHealthCheckController($manager);

        $response = $controller->get();

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());

        $data = json_decode((string)$response->getContent(), true);

        $this->assertFalse($data['database']);
        $this->assertSame('Connection refused', $data['databaseError']);
    }

    // ===== Helper methods =====

    private function createTestableController(?ManagerRegistry $manager = null): TestableHealthCheckController
    {
        return new TestableHealthCheckController($manager);
    }
}
