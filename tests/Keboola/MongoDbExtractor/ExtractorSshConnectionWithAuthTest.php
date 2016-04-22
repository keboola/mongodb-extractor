<?php

namespace Keboola\MongoDbExtractor;

use Keboola\Test\ExtractorTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class ExtractorSshConnectionWithAuthTest extends ExtractorTestCase
{
    /** @var Filesystem */
    private $fs;

    protected $path = '/tmp/extractor-ssh-auth';

    protected function setUp()
    {
        $this->fs = new Filesystem;
        $this->fs->remove($this->path);
        $this->fs->mkdir($this->path);

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->fs->remove($this->path);

        $process = new Process('pgrep ssh | xargs kill');
        $process->mustRun();
    }

    protected function getConfig()
    {
        $config = <<<YAML
parameters:
  db:
    host: 127.0.0.1
    port: 27017
    user: user
    password: user
    ssh:
      enabled: true
      sshHost: mongodb-auth
      sshPort: 22
      user: root
      localPort: 27017
      remoteHost: 127.0.0.1
      remotePort: 27017
      keys:
        public: ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQCvzAADEmS+fqf2YoClcSQJOAhkS5O5AFV18vLpMb8gVuI7Hjb/XPhsK9uX3mkTTRc6DlvCrl9gfILQ53YrmfmzEIq8FWW4i+R8ZaI4gchh4+QYuvMO7Q2Rgz+WhZsknGLQy2TJ5TkHvtZwagaUxYYmOFvZpwQwWIsQysL1jCYbGknLKJR39WM8rhs5Yk4Y3cMtLw4KGQ35WsFGrSrLuxajlnB8Ob+uMWvMwa8QRjE3adw3rZnjYIgzWiToQU9rDPkAZndUvPUDRJcCqnZw5iceDhPXtOv2b0W+bwrT3xxQVVTTVBnNF9om11hfitpSvJ2YBgTdLr7tvjh+RdW3Zl+t root@6eb3e87c2533
        private: |
          -----BEGIN RSA PRIVATE KEY-----
          MIIEpAIBAAKCAQEAr8wAAxJkvn6n9mKApXEkCTgIZEuTuQBVdfLy6TG/IFbiOx42
          /1z4bCvbl95pE00XOg5bwq5fYHyC0Od2K5n5sxCKvBVluIvkfGWiOIHIYePkGLrz
          Du0NkYM/loWbJJxi0MtkyeU5B77WcGoGlMWGJjhb2acEMFiLEMrC9YwmGxpJyyiU
          d/VjPK4bOWJOGN3DLS8OChkN+VrBRq0qy7sWo5ZwfDm/rjFrzMGvEEYxN2ncN62Z
          42CIM1ok6EFPawz5AGZ3VLz1A0SXAqp2cOYnHg4T17Tr9m9Fvm8K098cUFVU01QZ
          zRfaJtdYX4raUrydmAYE3S6+7b44fkXVt2ZfrQIDAQABAoIBAFvGnoL8CUhCCyHf
          ztWQOYXukML7icVdXUBUc2g2plcVxMmkPoYWXULrqpqgbC69YlDWyiTar8RJfGnf
          TJv6qJdJHYSPjylHLyOaU5Q4fQpN1PjsMJQsQZcj9AB7A8GbOyNR6+5TEvDuOjk5
          wPHOJPizF5CLVu5+ayt7D0jtv78Jnigx3urk2IlhxdnnaiO2pdPDdWROMaKmsRh+
          sClo982lM3/eTjAKNiqTFnTsX8eZ8OL2wg1cqX4C0BCRhs5E4TZq/biteHKbxnx8
          ibz6NqW5GDsE0wGBrnfXpXe+g2pqOn+0kBvQrWCK7kEYth7dpLO6Q19vJhyBL2iC
          dXtDRcECgYEA5YTHbyzkvOmTjfWGc2dCO7w4vVgK4n/UowZ49Qm9O9if5e7d+g0f
          g0G84um/OIRUB74QxioF9E9CPreKvLAbYR0cl743d/MYvN/WR88gPDqPt61tzUpf
          JqC/oLqUrif7MRTudgW/iOuXa+rnwUcXwzpWvQyT+4ttrF9HQK+/aakCgYEAxBR1
          Dib1B30Am997Ra62pf73iJ7BCuPvWfNoNQnrDMzPp4+DzU2vHD1uxO/9aUssRfXG
          3ryzV23H2lAggzLQE4+TK0vp8A0DGF87wC58Zirzn4zP4aTUdS5es0ud8HKfzuE6
          DJaRwfNun1AOakL/+dOJ9yhiGHXomojHy/PCMGUCgYEA1dmklOLIcXhU4nU9A/PX
          E59pYopRAf9HGWrjcrTTW5qYSX4J1304umya2PYgFEG/pcMjD/CBwcPDnnoXS33u
          1MpyJLS4LAwWJY2NszS6/UM3O1XdM+UyyOQICHMwKyDXfEDbep4aezG/0W5652wd
          KOsHfHfmvf6Ifo377rqR55kCgYAn/iAt5cY+Y8GXCUsEWHFKhCmKxQ6MoRb1ms7b
          Wo2Fi9Si0YPJgRnBQcpxAp4GNt3t2wZX8dcGcw67OXKYL+n+w176Cr7JRm4mL25p
          cVHQKNyN41OXK15mFDIekcLCAy8TLB8B6EgMbhFXDyYRiF7bXskaDzOK16m8sz9F
          Gw+1fQKBgQDZ7TrtZR2cuYutZMbkkM6HQHWSWjfU1azVJretjDzTJdqPUUJvGmLM
          OU6QjfNCKYTpx0Iy/zRarjLtuAhF0rbLbbxcdppMKgMrTHXypR+1jOeOawi3yplL
          3JfqZK0CHz6QZ+Uj2I3aMkcliDGk4VYDwl/boEuHVeFPM3lodS0y8w==
          -----END RSA PRIVATE KEY-----
YAML;
        return Yaml::parse($config);

    }
}
