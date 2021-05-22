<?php

namespace Plugin\ProductOnlyCustomerB\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

/**
 * @Eccube\EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="only_customer", type="boolean", options={"default":false})
     */
    private $only_customer;

    /**
     * @return boolean
     */
    public function getOnlyCustomer()
    {
        return $this->only_customer;
    }

    /**
     * @param boolean $only_customer
     */
    public function setOnlyCustomer($only_customer)
    {
        $this->only_customer = $only_customer;
    }
}
