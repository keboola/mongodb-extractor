<?php

declare(strict_types=1);

namespace Keboola\MongoDbExtractor;

use InvalidArgumentException;
use League\Uri\Components\Query;
use League\Uri\Uri as LeagueUri;
use League\Uri\UriString;

class Uri
{
    private const HOSTS_PLACEHOLDER = 'uri_host_placeholder.hosts';

    private LeagueUri $uri;

    // User and password in connection string must be URL encoded,
    // otherwise an error occurs:
    // "User/password ... must not have unescaped chars. Percent-encode username and password according to RFC 3986."
    // LeagueUri only encodes critical characters, so we have to do it manually for it to work.
    private ?string $user;
    private ?string $password;

    // LeagueUri cannot parse multiple guests in one URI, so they are replaced in URI by a placeholder.
    private ?string $hostPart;

    public static function createFromString(string $str): self
    {
        // LeagueUri cannot parse multiple guests in one URI, so they are replaced in URI by a placeholder, see GROUP 2
        $hostPart = null;
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
        $str = preg_replace_callback($hostsRegexp, function (array $matches) use (&$hostPart) {
            $hostPart = $matches[2];
            // Use placeholder for host part:
            //   - host can contain multiple hosts separated by commas - not parsed by League/Uri
            //   - or host contains upper/lower case letters - undesirably converted to lowercase by League/Uri
            return $matches[1] . self::HOSTS_PLACEHOLDER . $matches[3];
        }, $str);

        // Parse URI
        $components = UriString::parse($str);

        // The name and password are processed separately
        $user = isset($components['user']) ? urldecode($components['user']) : null;
        $password = isset($components['pass']) ? urldecode($components['pass']) : null;
        unset($components['user']);
        unset($components['pass']);

        $uri = LeagueUri::createFromComponents($components);

        return new self($uri->withUserInfo(null), $user, $password, $hostPart);
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
            'host' => self::HOSTS_PLACEHOLDER,
            'port' => $port,
            'path' => '/' . urlencode($database),
            'query' => Query::createFromPairs($query)->getContent(),
        ]), $user, $password, $host);
    }

    private function __construct(LeagueUri $uri, ?string $user, ?string $password, ?string $hostPart)
    {
        $this->uri = LeagueUri::createFromString($uri);
        $this->user = $user;
        $this->password = $password;
        $this->hostPart = $hostPart;

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

        // Check hostPart, if present, URI must contains placeholder
        if ($this->hostPart && $uri->getHost() !== self::HOSTS_PLACEHOLDER) {
            throw new InvalidArgumentException(sprintf(
                'Unexpected host value: "%s", expected placeholder: "%s".',
                $uri->getHost(),
                self::HOSTS_PLACEHOLDER,
            ));
        }
    }

    public function __toString(): string
    {
        $authority = $this->uri->getAuthority();

        // Replace members hosts placeholder
        $authority = str_replace(self::HOSTS_PLACEHOLDER, $this->hostPart, $authority);

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
