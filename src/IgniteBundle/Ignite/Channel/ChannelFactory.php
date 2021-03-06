<?php
/**
 * Created by PhpStorm.
 * User: Julian Raab
 * Date: 15.04.2018
 * Time: 15:40
 */

namespace JRemmurd\IgniteBundle\Ignite\Channel;


use JRemmurd\IgniteBundle\Constant\ChannelType;
use JRemmurd\IgniteBundle\Ignite\Channel\Encoder\ChannelSignatureEncoderInterface;
use JRemmurd\IgniteBundle\Ignite\Config;
use Psr\Container\ContainerInterface;

/**
 * Class ChannelFactory
 * @package JRemmurd\IgniteBundle\Ignite\Channel
 */
class ChannelFactory implements ChannelFactoryInterface
{
    /* @var Config $config */
    protected $config;

    /* @var ChannelSignatureEncoderInterface $channelNameEncoder */
    protected $channelNameEncoder;

    /* @var ContainerInterface $driverLocator */
    protected $driverLocator;

    /**
     * ChannelFactory constructor.
     * @param Config $config
     * @param ChannelSignatureEncoderInterface $channelNameEncoder
     * @param ContainerInterface $driverLocator
     */
    public function __construct(Config $config, ChannelSignatureEncoderInterface $channelNameEncoder, ContainerInterface $driverLocator)
    {
        $this->config = $config;
        $this->channelNameEncoder = $channelNameEncoder;
        $this->driverLocator = $driverLocator;
    }

    /**
     * @param string|null $name
     * @param string $signature
     * @param array $parameters
     * @param string $type
     * @return ChannelInterface|null
     * @throws \Exception
     */
    public function createByConfig(string $name = "", string $signature = "", $parameters = [], string $type = ""): ?ChannelInterface
    {
        if (!$name && $signature) {
            $name = $this->channelNameEncoder->decode($signature)["name"];
        } elseif (!$signature && ($name && $parameters)) {
            $signature = $this->channelNameEncoder->encode($name, $parameters);
        } elseif (!$signature && !$name) {
            throw new \Exception("Name or signature of channel must be provided.");
        }

        $signature = $signature ?: $name;

        if (!$type) {
            if ($this->config->isPresenceChannel($name)) {
                return new PresenceChannel($signature, $this->config, $this->driverLocator, $this->channelNameEncoder);
            } elseif ($this->config->isPrivateChannel($name)) {
                return new PrivateChannel($signature, $this->config, $this->driverLocator, $this->channelNameEncoder);
            } else {
                return new PublicChannel($signature, $this->config, $this->driverLocator, $this->channelNameEncoder);
            }
        } else if (!in_array($type, ChannelType::getAll())) {
            throw new \Exception("Invalid channel type [{$type}].");
        }

        switch ($type) {
            case ChannelType::PRESENCE:
                return new PresenceChannel($signature, $this->config, $this->driverLocator, $this->channelNameEncoder);
            case ChannelType::PRIVATE:
                return new PrivateChannel($signature, $this->config, $this->driverLocator, $this->channelNameEncoder);
            case ChannelType::PUBLIC:
            default:
                return new PublicChannel($signature, $this->config, $this->driverLocator, $this->channelNameEncoder);
        }
    }
}