<?php

namespace App\Proxy;

/**
 * Class Proxy.
 */
class Proxy
{
    /**
     * @var string
     */
    private $ip;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $protocol;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @return string|null
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return Proxy
     */
    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @param int $port
     *
     * @return Proxy
     */
    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @param string $protocol
     *
     * @return Proxy
     */
    public function setProtocol(string $protocol): self
    {
        $this->protocol = strtolower($protocol);

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return Proxy
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->protocol.'://'.$this->getIp().':'.$this->getPort();
    }
}
