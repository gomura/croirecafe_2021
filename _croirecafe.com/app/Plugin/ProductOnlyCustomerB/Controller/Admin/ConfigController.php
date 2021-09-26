<?php

namespace Plugin\ProductOnlyCustomerB\Controller\Admin;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{

    /**
     * @Route("/%eccube_admin_route%/product_only_customer_b/config", name="product_only_customer_b_admin_config")
     * @Template("@ProductOnlyCustomerB/admin/config.twig")
     */
    public function index(Request $request)
    {
        return ;
    }
}
