<?php

namespace Plugin\ECCUBE4LineLoginIntegration\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * LineLoginIntegrationSetting
 *
 * @ORM\Table(name="plg_line_login_integration_setting")
 * @ORM\Entity(repositoryClass="Plugin\ECCUBE4LineLoginIntegration\Repository\LineLoginIntegrationSettingRepository")
 */
class LineLoginIntegrationSetting extends AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="line_channel_id", type="text")
     */
    private $line_channel_id;
    /**
     * @var string
     *
     * @ORM\Column(name="line_channel_secret", type="text")
     */
    private $line_channel_secret;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getLineChannelId()
    {
        return $this->line_channel_id;
    }

    public function setLineChannelId($line_channel_id)
    {
        $this->line_channel_id = $line_channel_id;
        return $this;
    }

    public function getLineChannelSecret()
    {
        return $this->line_channel_secret;
    }

    public function setLineChannelSecret($line_channel_secret)
    {
        $this->line_channel_secret = $line_channel_secret;
        return $this;
    }
}
