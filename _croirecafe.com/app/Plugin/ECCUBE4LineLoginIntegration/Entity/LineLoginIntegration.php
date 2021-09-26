<?php

namespace Plugin\ECCUBE4LineLoginIntegration\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * LineLoginIntegration
 *
 * @ORM\Table(name="plg_line_login_integration")
 * @ORM\Entity(repositoryClass="Plugin\ECCUBE4LineLoginIntegration\Repository\LineLoginIntegrationRepository")
 */
class LineLoginIntegration extends AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="customer_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $customer_id;

    /**
     * @var string
     *
     * @ORM\Column(name="line_user_id", type="text")
     */
    private $line_user_id;

    /**
     * @var \Eccube\Entity\Customer
     *
     */
    private $Customer;

    public function getCustomerId()
    {
        return $this->customer_id;
    }

    public function setCustomerId($customer_id)
    {
        $this->customer_id = $customer_id;
        return $this;
    }

    public function getLineUserId()
    {
        return $this->line_user_id;
    }

    public function setLineUserId($line_user_id)
    {
        $this->line_user_id = $line_user_id;
        return $this;
    }

    public function setCustomer(\Eccube\Entity\Customer $customer = null)
    {
        $this->Customer = $customer;
        return $this;
    }

    public function getCustomer()
    {
        return $this->Customer;
    }
}
