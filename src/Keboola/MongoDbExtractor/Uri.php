<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use InvalidArgumentException;
use League\Uri\Components\Query;
use League\Uri\Uri as LeagueUri;
use League\Uri\UriString;

class Uri
{
    private const MEMBERS_HOSTS_PLACEHOLDER = 'members.hosts';

    private LeagueUri $uri;

    // User and password in connection string must be URL encoded,
    // otherwise an error occurs:
    // "User/password ... must not have unescaped chars. Percent-encode username and password according to RFC 3986."
    // LeagueUri only encodes critical characters, so we have to do it manually for it to work.
    private ?string $user;
    private ?string $password;

    // LeagueUri cannot parse multiple guests in one URI, so they are replaced in URI by a placeholder.
    private ?string $membersHosts;

    public static function createFromString(string $str): self
    {
        // LeagueUri cannot parse multiple guests in one URI, so they are replaced in URI by a placeholder, see GROUP 2
        $membersHosts = null;
        $hostsRegexp =
            '~' . // delimiter
            '^' . // start
            '(' . // GROUP 1 start
            '(?>[^://])+' . // protocol
            '://' . // separator
            '(?:[^:,@]+(?::[^:,@]+)?@)?' . // optional user:pass@
            ')' . // group 1 end
            '([^/]+)(/|$)' . // hosts, GROUP 2, eg: localhost,localhost:27018,localhost:27019/
            '~'; // delimiter
        $str = preg_replace_callback($hostsRegexp, function (array $matches) use (&$membersHosts) {
            $hostPart = $matches[2];

            // Use placeholder if host part of URI contains multiple hosts, separated by ,
            if (strpos($hostPart, ',') !== false) {
                $membersHosts = $hostPart;
                return $matches[1] . self::MEMBERS_HOSTS_PLACEHOLDER . $matches[3];
            }

            return $matches[0];
        }, $str);

        // Parse URI
        $components = UriString::parse($str);

        // The name and password are processed separately
        $user = isset($components['user']) ? urldecode($components['user']) : null;
        $password = isset($components['pass']) ? urldecode($components['pass']) : null;
        unset($components['user']);
        unset($components['pass']);

        $uri = LeagueUri::createFromComponents($components);

        return new self($uri->withUserInfo(null), $user, $password, $membersHosts);
    }

    public static function createFromParts(
        string $protocol,
        ?string $user,
        ?string $password,
        string $host,
        ?int $port,
        string $database,
        array $query = []
    ): self {
        return new self(LeagueUri::createFromComponents([
            'scheme' => $protocol,
            'host' => $host,
            'port' => $port,
            'path' => '/' . urlencode($database),
            'query' => Query::createFromPairs($query)->getContent(),
        ]), $user, $password, null);
    }

    private function __construct(LeagueUri $uri, ?string $user, ?string $password, ?string $membersHosts)
    {
        $this->uri = LeagueUri::createFromString($uri);
        $this->user = $user;
        $this->password = $password;
        $this->membersHosts = $membersHosts;

        // Check protocol
        if (!in_array($this->uri->getScheme(), ['mongodb', 'mongodb+srv'], true)) {
            throw new UserException('Connection URI must start with "mongodb://" or "mongodb+srv://".');
        }

        // User and password in connection string must be URL encoded,
        // LeagueUri only encodes critical characters, so we have to do it manually for it to work.
        if ($uri->getUserInfo() !== null) {
            throw new InvalidArgumentException(
                'The user and password must be specified separately, not as part of the URI.'
            );
        }

        // Check members hosts, if present, URI must contains placeholder
        if ($this->membersHosts && $uri->getHost() !== self::MEMBERS_HOSTS_PLACEHOLDER) {
            throw new InvalidArgumentException(sprintf(
                'Unexpected host value: "%s", expected placeholder: "%s".',
                $uri->getHost(),
                self::MEMBERS_HOSTS_PLACEHOLDER,
            ));
        }
    }

    public function __toString(): string
    {
        $authority = $this->uri->getAuthority();

        // Replace members hosts placeholder
        $authority = str_replace(self::MEMBERS_HOSTS_PLACEHOLDER, $this->membersHosts, $authority);

        // Percent-encode username and password according to RFC 3986
        if ($this->user) {
            $userInfo = urlencode($this->user);
            if ($this->password) {
                $userInfo .= ':' . urlencode($this->password);
            }
            $authority = $userInfo . '@' . $authority;
        }

        $query = $this->uri->getQuery() ? '?' . $this->uri->getQuery() : '';
        return $this->uri->getScheme() . '://' . $authority . $this->uri->getPath() . $query;
    }

    public function hasDatabase(): bool
    {
        return !empty($this->getDatabase());
    }

    public function getDatabase(): string
    {
        return urldecode(ltrim($this->uri->getPath(), '/'));
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        if ($this->hasPassword()) {
            throw new InvalidArgumentException('Password is already set.');
        }

        $this->password = $password;
    }

    public function getQuery(): Query
    {
        return Query::createFromUri($this->uri);
    }

    public function setQuery(Query $query): void
    {
        $this->uri = $this->uri->withQuery($query->getContent());
    }
}
