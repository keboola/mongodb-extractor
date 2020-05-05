<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Keboola\SSHTunnel\SSHException;

class ExtractorSshConnectionTimeoutTest extends TestCase
{
    use CreateExtractorTrait;

    /** @var UriFactory */
    protected $uriFactory;

    /** @var ExportCommandFactory */
    protected $exportCommandFactory;

    /** @var string */
    protected $path = '/tmp/extractor-ssh-connection-timeout';

    /** @var Filesystem */
    private $fs;

    protected function setUp(): void
    {
        $this->uriFactory = new UriFactory();
        $this->exportCommandFactory = new ExportCommandFactory($this->uriFactory);

        $this->fs = new Filesystem;
        $this->fs->remove($this->path);
        $this->fs->mkdir($this->path);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->path);

        $process = new Process('pgrep ssh | xargs -r kill');
        $process->mustRun();
    }

    protected function getConfig(): array
    {
        // phpcs:disable Generic.Files.LineLength.MaxExceeded
        $config = <<<JSON
{
  "parameters": {
    "db": {
      "host": "this-host-does-not-matter",
      "port": 27017,
      "database": "test",
      "ssh": {
        "enabled": true,
        "sshHost": "some random host",
        "sshPort": 22,
        "user": "root",
        "keys": {
          "public": "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQCvzAADEmS+fqf2YoClcSQJOAhkS5O5AFV18vLpMb8gVuI7Hjb\/XPhsK9uX3mkTTRc6DlvCrl9gfILQ53YrmfmzEIq8FWW4i+R8ZaI4gchh4+QYuvMO7Q2Rgz+WhZsknGLQy2TJ5TkHvtZwagaUxYYmOFvZpwQwWIsQysL1jCYbGknLKJR39WM8rhs5Yk4Y3cMtLw4KGQ35WsFGrSrLuxajlnB8Ob+uMWvMwa8QRjE3adw3rZnjYIgzWiToQU9rDPkAZndUvPUDRJcCqnZw5iceDhPXtOv2b0W+bwrT3xxQVVTTVBnNF9om11hfitpSvJ2YBgTdLr7tvjh+RdW3Zl+t root@6eb3e87c2533",
          "#private": "-----BEGIN RSA PRIVATE KEY-----\\nMIIEpAIBAAKCAQEAr8wAAxJkvn6n9mKApXEkCTgIZEuTuQBVdfLy6TG\/IFbiOx42\\n\/1z4bCvbl95pE00XOg5bwq5fYHyC0Od2K5n5sxCKvBVluIvkfGWiOIHIYePkGLrz\\nDu0NkYM\/loWbJJxi0MtkyeU5B77WcGoGlMWGJjhb2acEMFiLEMrC9YwmGxpJyyiU\\nd\/VjPK4bOWJOGN3DLS8OChkN+VrBRq0qy7sWo5ZwfDm\/rjFrzMGvEEYxN2ncN62Z\\n42CIM1ok6EFPawz5AGZ3VLz1A0SXAqp2cOYnHg4T17Tr9m9Fvm8K098cUFVU01QZ\\nzRfaJtdYX4raUrydmAYE3S6+7b44fkXVt2ZfrQIDAQABAoIBAFvGnoL8CUhCCyHf\\nztWQOYXukML7icVdXUBUc2g2plcVxMmkPoYWXULrqpqgbC69YlDWyiTar8RJfGnf\\nTJv6qJdJHYSPjylHLyOaU5Q4fQpN1PjsMJQsQZcj9AB7A8GbOyNR6+5TEvDuOjk5\\nwPHOJPizF5CLVu5+ayt7D0jtv78Jnigx3urk2IlhxdnnaiO2pdPDdWROMaKmsRh+\\nsClo982lM3\/eTjAKNiqTFnTsX8eZ8OL2wg1cqX4C0BCRhs5E4TZq\/biteHKbxnx8\\nibz6NqW5GDsE0wGBrnfXpXe+g2pqOn+0kBvQrWCK7kEYth7dpLO6Q19vJhyBL2iC\\ndXtDRcECgYEA5YTHbyzkvOmTjfWGc2dCO7w4vVgK4n\/UowZ49Qm9O9if5e7d+g0f\\ng0G84um\/OIRUB74QxioF9E9CPreKvLAbYR0cl743d\/MYvN\/WR88gPDqPt61tzUpf\\nJqC\/oLqUrif7MRTudgW\/iOuXa+rnwUcXwzpWvQyT+4ttrF9HQK+\/aakCgYEAxBR1\\nDib1B30Am997Ra62pf73iJ7BCuPvWfNoNQnrDMzPp4+DzU2vHD1uxO\/9aUssRfXG\\n3ryzV23H2lAggzLQE4+TK0vp8A0DGF87wC58Zirzn4zP4aTUdS5es0ud8HKfzuE6\\nDJaRwfNun1AOakL\/+dOJ9yhiGHXomojHy\/PCMGUCgYEA1dmklOLIcXhU4nU9A\/PX\\nE59pYopRAf9HGWrjcrTTW5qYSX4J1304umya2PYgFEG\/pcMjD\/CBwcPDnnoXS33u\\n1MpyJLS4LAwWJY2NszS6\/UM3O1XdM+UyyOQICHMwKyDXfEDbep4aezG\/0W5652wd\\nKOsHfHfmvf6Ifo377rqR55kCgYAn\/iAt5cY+Y8GXCUsEWHFKhCmKxQ6MoRb1ms7b\\nWo2Fi9Si0YPJgRnBQcpxAp4GNt3t2wZX8dcGcw67OXKYL+n+w176Cr7JRm4mL25p\\ncVHQKNyN41OXK15mFDIekcLCAy8TLB8B6EgMbhFXDyYRiF7bXskaDzOK16m8sz9F\\nGw+1fQKBgQDZ7TrtZR2cuYutZMbkkM6HQHWSWjfU1azVJretjDzTJdqPUUJvGmLM\\nOU6QjfNCKYTpx0Iy\/zRarjLtuAhF0rbLbbxcdppMKgMrTHXypR+1jOeOawi3yplL\\n3JfqZK0CHz6QZ+Uj2I3aMkcliDGk4VYDwl\/boEuHVeFPM3lodS0y8w==\\n-----END RSA PRIVATE KEY-----\\n"
        }
      }
    },
    "exports": [
      {
        "name": "bakeries",
        "id": 123,
        "enabled": true,
        "collection": "restaurants",
        "incremental": true,
        "mapping": {
          "_id.\$oid": "id"
        },
        "mode": "mapping"
      }
    ]
  }
}
JSON;
        // phpcs:enable
        return (new JsonDecode(true))->decode($config, JsonEncoder::FORMAT);
    }

    public function testWrongConnection(): void
    {
        $this->expectException(SSHException::class);
        $this->expectExceptionMessage('Unable to create ssh tunnel');

        $this->createExtractor($this->getConfig()['parameters']);
        // we don't call extract, so it'll end with SshException for sure
    }
}
